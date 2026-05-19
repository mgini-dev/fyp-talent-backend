<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\Connection;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PostService
{
    public function getFeed(User $me, ?int $talentFilter = null): LengthAwarePaginator
    {
        // Get IDs of connections + followings + self
        $connectedIds = Connection::where('status', 'accepted')
            ->where(function ($q) use ($me) {
                $q->where('requester_id', $me->id)->orWhere('receiver_id', $me->id);
            })
            ->get()
            ->map(fn($c) => $c->requester_id === $me->id ? $c->receiver_id : $c->requester_id)
            ->all();

        $followingIds = Follow::where('follower_id', $me->id)->pluck('following_id')->all();

        $authorIds = array_unique(array_merge($connectedIds, $followingIds, [$me->id]));

        $posts = Post::whereIn('user_id', $authorIds)
            ->when($talentFilter, fn($q) => $q->where('talent_id', $talentFilter))
            ->with(['author.talents:id,name,category', 'talent'])
            ->withCount(['reactions as likes_count' => fn($q) => $q->where('type', 'like')])
            ->withCount(['reactions as dislikes_count' => fn($q) => $q->where('type', 'dislike')])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Append my reaction to each post
        $posts->getCollection()->transform(function ($post) use ($me) {
            $post->my_reaction = PostReaction::where(['post_id' => $post->id, 'user_id' => $me->id])->value('type');
            return $post;
        });

        return $posts;
    }

    public function createPost(User $me, array $data): Post
    {
        return Post::create([
            'user_id'    => $me->id,
            'content'    => $data['content'],
            'type'       => $data['type'] ?? 'post',
            'visibility' => $data['visibility'] ?? 'public',
            'talent_id'  => $data['talent_id'] ?? null,
        ]);
    }

    public function deletePost(User $me, int $id): void
    {
        $post = Post::where(['id' => $id, 'user_id' => $me->id])->firstOrFail();
        $post->delete();
    }

    public function reactToPost(User $me, int $postId, string $type): array
    {
        $post = Post::findOrFail($postId);
        $existing = PostReaction::where(['post_id' => $postId, 'user_id' => $me->id])->first();

        if ($existing) {
            if ($existing->type === $type) {
                $existing->delete();
                $myReaction = null;
            } else {
                $existing->update(['type' => $type]);
                $myReaction = $type;
            }
        } else {
            PostReaction::create(['post_id' => $postId, 'user_id' => $me->id, 'type' => $type]);
            $myReaction = $type;
        }

        if ($myReaction === 'like' && $post->user_id !== $me->id) {
            app(\App\Services\NotificationService::class)->createNotification(
                $post->user_id,
                'new_reaction',
                [
                    'title' => 'New Reaction on your Post',
                    'body' => "{$me->first_name} {$me->last_name} liked your post.",
                    'sender_id' => $me->id,
                    'sender_name' => "{$me->first_name} {$me->last_name}",
                    'reference_id' => $post->id,
                ]
            );
        }

        return [
            'likes'       => $post->reactions()->where('type', 'like')->count(),
            'dislikes'    => $post->reactions()->where('type', 'dislike')->count(),
            'my_reaction' => $myReaction,
        ];
    }

    public function getComments(int $postId): Collection
    {
        Post::findOrFail($postId);

        return PostComment::where('post_id', $postId)
            ->whereNull('parent_id')
            ->with(['author', 'replies.author'])
            ->orderBy('created_at')
            ->get();
    }

    public function addComment(User $me, int $postId, array $data): PostComment
    {
        $post = Post::findOrFail($postId);

        $comment = PostComment::create([
            'post_id'   => $postId,
            'user_id'   => $me->id,
            'content'   => $data['content'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        if ($post->user_id !== $me->id) {
            app(\App\Services\NotificationService::class)->createNotification(
                $post->user_id,
                'new_comment',
                [
                    'title' => 'New Comment on your Post',
                    'body' => "{$me->first_name} {$me->last_name} commented on your post.",
                    'sender_id' => $me->id,
                    'sender_name' => "{$me->first_name} {$me->last_name}",
                    'reference_id' => $post->id,
                ]
            );
        }

        return $comment;
    }

    public function deleteComment(User $me, int $commentId): void
    {
        $comment = PostComment::where(['id' => $commentId, 'user_id' => $me->id])->firstOrFail();
        $comment->delete();
    }

    public function getUserPosts(int $userId): LengthAwarePaginator
    {
        return Post::where('user_id', $userId)
            ->with(['author', 'talent'])
            ->withCount(['reactions as likes_count' => fn($q) => $q->where('type', 'like')])
            ->withCount(['reactions as dislikes_count' => fn($q) => $q->where('type', 'dislike')])
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }
}
