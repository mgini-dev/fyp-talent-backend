<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\ReactPostRequest;
use App\Http\Requests\Post\StoreCommentRequest;
use App\Services\PostService;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostCommentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    protected PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function feed(Request $request): JsonResponse
    {
        $talentFilter = $request->query('talent_id') ? (int) $request->query('talent_id') : null;
        $posts = $this->postService->getFeed($request->user(), $talentFilter);

        $resourceCollection = PostResource::collection($posts);

        return response()->json([
            'status' => 'success', 
            'data' => $resourceCollection->response()->getData(true)
        ]);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->postService->createPost($request->user(), $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Post published!',
            'data'    => new PostResource($post->load('author', 'talent')),
        ], 201);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $this->postService->deletePost($request->user(), (int) $id);

        return response()->json(['status' => 'success', 'message' => 'Post deleted']);
    }

    public function react(ReactPostRequest $request, $id): JsonResponse
    {
        $result = $this->postService->reactToPost($request->user(), (int) $id, $request->type);

        return response()->json([
            'status' => 'success',
            'data'   => $result,
        ]);
    }

    public function getComments($id): JsonResponse
    {
        $comments = $this->postService->getComments((int) $id);

        return response()->json([
            'status' => 'success', 
            'data' => PostCommentResource::collection($comments)
        ]);
    }

    public function addComment(StoreCommentRequest $request, $id): JsonResponse
    {
        $comment = $this->postService->addComment($request->user(), (int) $id, $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Comment added!',
            'data'    => new PostCommentResource($comment->load('author', 'replies.author')),
        ], 201);
    }

    public function deleteComment(Request $request, $commentId): JsonResponse
    {
        $this->postService->deleteComment($request->user(), (int) $commentId);

        return response()->json(['status' => 'success', 'message' => 'Comment deleted']);
    }

    public function userPosts(Request $request, $userId): JsonResponse
    {
        $posts = $this->postService->getUserPosts((int) $userId);

        $resourceCollection = PostResource::collection($posts);

        return response()->json([
            'status' => 'success', 
            'data' => $resourceCollection->response()->getData(true)
        ]);
    }
}
