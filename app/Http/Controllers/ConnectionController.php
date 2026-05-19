<?php

namespace App\Http\Controllers;

use App\Http\Requests\Connection\SendConnectionRequest;
use App\Http\Requests\Connection\RespondConnectionRequest;
use App\Services\ConnectionService;
use App\Http\Resources\ConnectionResource;
use App\Http\Resources\ConnectionIndexResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConnectionController extends Controller
{
    protected ConnectionService $connectionService;

    public function __construct(ConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    public function index(Request $request): JsonResponse
    {
        $connections = $this->connectionService->getConnections($request->user());

        return response()->json([
            'status' => 'success', 
            'data' => ConnectionIndexResource::collection($connections)
        ]);
    }

    public function pendingRequests(Request $request): JsonResponse
    {
        $requests = $this->connectionService->getPendingRequests($request->user());

        return response()->json([
            'status' => 'success', 
            'data' => ConnectionResource::collection($requests)
        ]);
    }

    public function request(SendConnectionRequest $request): JsonResponse
    {
        try {
            $conn = $this->connectionService->sendConnectionRequest(
                $request->user(), 
                (int) $request->receiver_id
            );

            return response()->json([
                'status' => 'success', 
                'message' => 'Connection request sent', 
                'data' => new ConnectionResource($conn->load('receiver'))
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function respond(RespondConnectionRequest $request, $id): JsonResponse
    {
        $conn = $this->connectionService->respondToRequest($request->user(), (int) $id, $request->action);

        return response()->json([
            'status'  => 'success',
            'message' => $request->action === 'accept' ? 'Connection accepted!' : 'Request declined.',
            'data'    => new ConnectionResource($conn),
        ]);
    }

    public function disconnect(Request $request, $userId): JsonResponse
    {
        $this->connectionService->disconnect($request->user(), (int) $userId);

        return response()->json(['status' => 'success', 'message' => 'Disconnected successfully']);
    }

    public function follow(Request $request, $userId): JsonResponse
    {
        try {
            $this->connectionService->follow($request->user(), (int) $userId);

            return response()->json(['status' => 'success', 'message' => 'Now following!']);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function unfollow(Request $request, $userId): JsonResponse
    {
        $this->connectionService->unfollow($request->user(), (int) $userId);

        return response()->json(['status' => 'success', 'message' => 'Unfollowed']);
    }

    public function myFollowers(Request $request): JsonResponse
    {
        $followers = $this->connectionService->getFollowers($request->user());

        return response()->json([
            'status' => 'success', 
            'data' => UserResource::collection($followers)
        ]);
    }

    public function discover(Request $request): JsonResponse
    {
        $result = $this->connectionService->discover($request->user());

        return response()->json([
            'status' => 'success',
            'data'   => [
                'recommended_mentors' => UserResource::collection($result['recommended_mentors']),
                'recommended_peers'   => UserResource::collection($result['recommended_peers']),
            ]
        ]);
    }
}
