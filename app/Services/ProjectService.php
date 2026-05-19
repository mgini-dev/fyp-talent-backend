<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProjectService
{
    public function getProjects(User $me): Collection
    {
        if ($me->hasRole('Mentor')) {
            return Project::where('mentor_id', $me->id)
                ->orWhereHas('student', fn($q) => $q->where('id', $me->id))
                ->with(['student', 'mentor', 'talent'])
                ->withCount('updates')
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        return Project::where('student_id', $me->id)
            ->with(['student', 'mentor', 'talent'])
            ->withCount('updates')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function createProject(User $me, array $data): Project
    {
        return Project::create([
            'student_id'  => $me->id,
            'mentor_id'   => $data['mentor_id'] ?? null,
            'title'       => $data['title'],
            'description' => $data['description'],
            'talent_id'   => $data['talent_id'] ?? null,
            'status'      => 'draft',
        ]);
    }

    public function getProject(int $id): Project
    {
        return Project::with(['student', 'mentor', 'talent', 'updates.author'])
            ->findOrFail($id);
    }

    public function updateProject(User $me, int $id, array $data): Project
    {
        $project = Project::findOrFail($id);

        if ($project->student_id !== $me->id && $project->mentor_id !== $me->id) {
            throw new HttpException(403, 'Unauthorized');
        }

        $project->update(array_intersect_key($data, array_flip(['title', 'description', 'status', 'mentor_id', 'talent_id'])));

        return $project->fresh(['student', 'mentor', 'talent']);
    }

    public function addProjectUpdate(User $me, int $id, array $data): ProjectUpdate
    {
        $project = Project::findOrFail($id);

        if ($project->student_id !== $me->id && $project->mentor_id !== $me->id) {
            throw new HttpException(403, 'Not a project member');
        }

        $update = ProjectUpdate::create([
            'project_id' => $project->id,
            'user_id'    => $me->id,
            'content'    => $data['content'],
        ]);

        if ($project->status === 'draft') {
            $project->update(['status' => 'active']);
        }

        $targetUserId = ($me->id === $project->student_id) ? $project->mentor_id : $project->student_id;

        if ($targetUserId) {
            app(\App\Services\NotificationService::class)->createNotification(
                $targetUserId,
                'project_update',
                [
                    'title' => 'New Project Update',
                    'body' => "{$me->first_name} {$me->last_name} posted an update on project \"{$project->title}\".",
                    'sender_id' => $me->id,
                    'sender_name' => "{$me->first_name} {$me->last_name}",
                    'reference_id' => $project->id,
                ]
            );
        }

        return $update;
    }

    public function deleteProject(User $me, int $id): void
    {
        $project = Project::where('student_id', $me->id)->findOrFail($id);
        $project->delete();
    }
}
