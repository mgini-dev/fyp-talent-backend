<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\SendMessageRequest;
use App\Services\MessageService;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    protected MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function conversations(Request $request): JsonResponse
    {
        $connections = $this->messageService->getConversations($request->user());

        return response()->json([
            'status' => 'success', 
            'data' => ConversationResource::collection($connections)
        ]);
    }

    public function getMessages(Request $request, $connectionId): JsonResponse
    {
        $messages = $this->messageService->getMessages($request->user(), (int) $connectionId);

        // Formats the items inside the paginator using MessageResource
        $messages->setCollection(
            collect(MessageResource::collection($messages->getCollection()))
        );

        return response()->json([
            'status' => 'success', 
            'data' => $messages
        ]);
    }

    public function send(SendMessageRequest $request, $connectionId): JsonResponse
    {
        $message = $this->messageService->sendMessage(
            $request->user(), 
            (int) $connectionId, 
            $request->content
        );

        return response()->json([
            'status'  => 'success',
            'data'    => new MessageResource($message->load('sender:id,first_name,last_name,profile_photo_url')),
        ], 201);
    }

    public function totalUnread(Request $request): JsonResponse
    {
        $total = $this->messageService->getTotalUnread($request->user());

        return response()->json([
            'status' => 'success', 
            'data' => ['unread' => $total]
        ]);
    }
}
