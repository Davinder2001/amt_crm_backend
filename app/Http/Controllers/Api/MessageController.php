<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Cmgmyr\Messenger\Models\Thread;
use Cmgmyr\Messenger\Models\Participant;
use App\Http\Resources\ChatUserResource;
use Cmgmyr\Messenger\Models\Message;


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

        $thread     = Thread::findOrFail($id);
        $messages   = $thread->messages()->with('user')->get();
        return response()->json($messages, 200);
    }


    public function createConversation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject'       => 'required|string',
            'recipient_id'  => 'required|exists:users,id',
            'message'       => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'errors' => $validator->errors(),
            ], 422);
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
            'status'    => 'success',
            'data'      => $thread->load('participants'),
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
        $userId = $request->user()->id;
    
        $participants = Participant::with(['thread.messages' => function ($query) {
            $query->latest('created_at');
            }, 'thread.participants.user'])
            ->where('user_id', $userId)
            ->get();
    
        $chatData = [];
    
        foreach ($participants as $participant) {

            $conversation   = $participant->thread;
            $lastMessage    = $conversation->messages->first();
            $lastRead       = $participant->last_read;

            $unreadCount = $conversation->messages
                ->where('user_id', '!=', $userId)
                ->when($lastRead, fn($msgs) => $msgs->filter(fn($m) => $m->created_at > $lastRead))
                ->count();
    
            $chatData[] = new ChatUserResource((object)[
                'last_message'       => $lastMessage?->body ?? null,
                'last_message_time'  => $lastMessage?->created_at ?? null,
                'is_read'            => $lastRead && $lastMessage && $lastMessage->created_at <= $lastRead,
                'unread_count'       => $unreadCount,
            ]);
        }
    
        return response()->json([
            'status' => 'success',
            'data'   => $chatData,
        ]);
    }
}
