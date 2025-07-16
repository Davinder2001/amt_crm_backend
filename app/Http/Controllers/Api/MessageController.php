<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Cmgmyr\Messenger\Models\Thread;
use Cmgmyr\Messenger\Models\Message;
use App\Http\Controllers\Controller;
use Cmgmyr\Messenger\Models\Participant;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function sendMessageToUser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sender     = $request->user();
        $recipient  = User::find($id);

        if (!$recipient) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Recipient not found.',
            ], 404);
        }

        $thread = Thread::whereHas('participants', function ($q) use ($sender) {
            $q->where('user_id', $sender->id);
        })->whereHas('participants', function ($q) use ($id) {
            $q->where('user_id', $id);
        })->first();

        if (!$thread) {
            $thread = Thread::create([
                'subject' => $request->subject ?? 'New Conversation',
            ]);

            $thread->participants()->createMany([
                ['user_id' => $sender->id],
                ['user_id' => $id],
            ]);
        }

        $message = Message::create([
            'thread_id' => $thread->id,
            'user_id'   => $sender->id,
            'body'      => $request->message,
        ]);

        $thread->participants()
            ->where('user_id', $sender->id)
            ->update(['last_read' => now()]);

        return response()->json([
            'status' => 'success',
            'data'  => [
                'thread_id' => $thread->id,
                'message'   => $message,
            ],
        ], 201);
    }



    public function chats(Request $request)
    {
        $user               = $request->user();
        $participants       = Participant::where('user_id', $user->id)->get();
        $latestMessages     = $participants->map(function ($participant) use ($user) {
            $threadId           = $participant->thread_id;
            $latestMessage      = Message::where('thread_id', $threadId)->orderByDesc('updated_at')->first();
            $otherParticipant   = Participant::where('thread_id', $threadId)->where('user_id', '!=', $user->id)->with('user')->first();

            $user_is = 'sender';

            if ($user->id === $otherParticipant->user_id) {
                $user_is = 'reciver';
            }


            return [
                'thread_id'         => $threadId,
                'current_user_id'   => $user->id,
                'user_is'           => $user_is,
                'latest_message'    => $latestMessage,
                'other_participant' => $otherParticipant,
            ];
        });

        return response()->json([
            'latest_messages' => $latestMessages,
        ]);
    }


    public function getChatWithUser(Request $request, $id)
    {
        $authUser   = $request->user();
        $otherUser  = User::find($id);

        if (!$otherUser) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        $thread = Thread::whereHas('participants', function ($q) use ($authUser) {
            $q->where('user_id', $authUser->id);
        })->whereHas('participants', function ($q) use ($id) {
            $q->where('user_id', $id);
        })->first();

        if (!$thread) {
            return response()->json([
                'status' => 'error',
                'message' => 'No conversation found.',
            ], 404);
        }

        $authParticipant = Participant::where('thread_id', $thread->id)
            ->where('user_id', $authUser->id)
            ->first();

        $messages = Message::where('thread_id', $thread->id)
            ->orderBy('created_at', 'asc')
            ->with('user:id,name,email')
            ->get()
            ->map(function ($message) use ($authUser, $authParticipant) {
                return [
                    'id'         => $message->id,
                    'body'       => $message->body,
                    'sender_id'  => $message->user_id,
                    'sender'     => $message->user,
                    'sent_by_me' => $message->user_id === $authUser->id,
                    'read'       => $authParticipant && $authParticipant->last_read
                        ? $message->created_at <= $authParticipant->last_read
                        : false,
                    'created_at' => $message->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'status'    => 'success',
            'thread_id' => $thread->id,
            'messages'  => $messages,
        ]);
    }

    public function chatUsers(Request $request)
    {
        $authUserId = $request->user()->id;

        $users = User::with('roles')
            ->where('id', '!=', $authUserId)
            ->get()
            ->map(function ($user) {
                return [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'role' => $user->getRoleNames()->first(),
                ];
            });

        return response()->json($users);
    }


    public function deleteMessage(Request $request, $messageId)
    {
        $authUser = $request->user();

        $message = Message::find($messageId);

        if (!$message) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Message not found.',
            ], 404);
        }

        if ($message->user_id !== $authUser->id) {
            return response()->json([
                'status'  => 'forbidden',
                'message' => 'You are not allowed to delete this message.',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Message deleted successfully.',
        ]);
    }

    public function deleteAllChatsWithUser(Request $request, $id)
    {
        $authUser = $request->user();
        $otherUser = User::find($id);

        if (!$otherUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        $thread = Thread::whereHas('participants', function ($q) use ($authUser) {
            $q->where('user_id', $authUser->id);
        })->whereHas('participants', function ($q) use ($id) {
            $q->where('user_id', $id);
        })->first();

        if (!$thread) {
            return response()->json([
                'status' => 'error',
                'message' => 'No conversation found.',
            ], 404);
        }

        Message::where('thread_id', $thread->id)->delete();
        Participant::where('thread_id', $thread->id)->delete();
        $thread->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'All chats with the user have been deleted.',
        ]);
    }
}
