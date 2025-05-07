<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Cmgmyr\Messenger\Models\Thread;
use Cmgmyr\Messenger\Models\Participant;
use Cmgmyr\Messenger\Models\Message;
use App\Models\User;

class MessageController extends Controller
{
    public function getConversations(Request $request)
    {
        return response()->json(
            $request->user()->threads()->latest('updated_at')->get(),
            200
        );
    }

    public function getMessages($id)
    {
        $thread   = Thread::findOrFail($id);
        $messages = $thread->messages()->with('user')->get();

        return response()->json($messages, 200);
    }

    public function createConversation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject'      => 'required|string',
            'recipient_id' => 'required|exists:users,id',
            'message'      => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if thread already exists between these users
        $existingThreadId = Thread::whereHas('participants', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->whereHas('participants', function ($q) use ($request) {
            $q->where('user_id', $request->recipient_id);
        })->pluck('id')->first();

        if ($existingThreadId) {
            return $this->sendMessage($request, $existingThreadId);
        }

        $thread = Thread::create(['subject' => $request->subject]);

        Message::create([
            'thread_id' => $thread->id,
            'user_id'   => $request->user()->id,
            'body'      => $request->message,
        ]);

        $thread->participants()->createMany([
            ['user_id' => $request->user()->id],
            ['user_id' => $request->recipient_id],
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $thread->load('participants'),
        ], 201);
    }

    public function sendMessage(Request $request, $id)
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

        $thread = Thread::findOrFail($id);

        $message = Message::create([
            'thread_id' => $thread->id,
            'user_id'   => $request->user()->id,
            'body'      => $request->message,
        ]);

        $thread->participants()
            ->where('user_id', $request->user()->id)
            ->update(['last_read' => now()]);

        return response()->json([
            'status' => 'success',
            'data'   => $message,
        ], 201);
    }

    public function getChatList(Request $request)
    {
        $userId     = $request->user()->id;

        $threadIds  = Participant::where('user_id', $userId)->pluck('thread_id');

        if ($threadIds->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No chats found',
                'data' => [],
            ]);
        }

        $chatMap = [];

        foreach ($threadIds as $threadId) {
            $thread = Thread::find($threadId);
            if (!$thread) continue;

            $lastMessage = $thread->messages()->latest('created_at')->first();
            if (!$lastMessage) continue;

            $senderId = $lastMessage->user_id;
            $sender   = User::find($senderId);

            $senderParticipant = Participant::where('thread_id', $threadId)
                ->where('user_id', $senderId)
                ->first();

            $receiverParticipant = $thread->participants()
                ->where('user_id', '!=', $userId)
                ->first();

            if (!$receiverParticipant) continue;

            $receiverId = $receiverParticipant->user_id;
            $receiver   = User::find($receiverId);

            $existing = $chatMap[$receiverId] ?? null;

            if (
                !$existing ||
                ($existing['last_message_time'] ?? null) < $lastMessage->created_at
            ) {
                $chatMap[$receiverId] = [
                    // 🔹 Legacy-style response
                    'user_id'      => $receiver->id ?? null,
                    'name'         => $receiver->name ?? 'Unknown',
                    'last_message' => $lastMessage->body,

                    // 🔹 Detailed nested info
                    'sender' => [
                        'id'        => $sender->id ?? null,
                        'name'      => $sender->name ?? 'Unknown',
                        'last_read' => optional($senderParticipant)->last_read,
                    ],
                    'receiver' => [
                        'id'        => $receiver->id ?? null,
                        'name'      => $receiver->name ?? 'Unknown',
                        'last_read' => $receiverParticipant->last_read,
                    ],

                    'last_message_time' => $lastMessage->created_at,
                ];
            }
        }

        $chatData = array_map(function ($chat) {
            unset($chat['last_message_time']);
            return $chat;
        }, array_values($chatMap));

        return response()->json([
            'status' => 'success',
            'data'   => $chatData,
        ]);
    }

    
    public function markAsRead(Request $request, $threadId)
    {
        $userId = $request->user()->id;
        $thread = Thread::findOrFail($threadId);

        $participant = Participant::where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->first();

        if (!$participant) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Participant not found in this thread.',
            ], 404);
        }

        $participant->last_read = now();
        $participant->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Last read timestamp updated.',
            'data'    => [
                'thread_id'  => $threadId,
                'user_id'    => $userId,
                'last_read'  => $participant->last_read,
            ]
        ]);
    }
}
