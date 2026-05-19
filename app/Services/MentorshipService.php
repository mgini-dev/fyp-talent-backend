<?php

namespace App\Services;

use App\Models\Mentorship;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MentorshipService
{
    public function getMentorshipsForUser(User $user)
    {
        $asMentee = Mentorship::with('mentor')->where('mentee_id', $user->id)->get();
        $asMentor = Mentorship::with('mentee')->where('mentor_id', $user->id)->get();

        return [
            'as_mentee' => $asMentee,
            'as_mentor' => $asMentor
        ];
    }

    public function requestMentorship(User $mentee, $mentorId, $goals)
    {
        $mentor = User::findOrFail($mentorId);
        if (!$mentor->hasRole('Mentor') && !$mentor->hasRole('Admin')) {
            throw new \InvalidArgumentException('Selected user is not a Mentor', 400);
        }

        $existing = Mentorship::where('mentor_id', $mentor->id)
            ->where('mentee_id', $mentee->id)
            ->whereIn('status', ['pending', 'active'])
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException('You already have an active or pending request with this mentor', 400);
        }

        try {
            DB::beginTransaction();

            $mentorship = Mentorship::create([
                'mentor_id' => $mentor->id,
                'mentee_id' => $mentee->id,
                'status' => 'pending',
                'goals' => $goals
            ]);

            DB::commit();

            app(\App\Services\NotificationService::class)->createNotification(
                $mentor->id,
                'mentorship_request',
                [
                    'title' => 'New Mentorship Request',
                    'body' => "{$mentee->first_name} {$mentee->last_name} requested mentorship.",
                    'sender_id' => $mentee->id,
                    'sender_name' => "{$mentee->first_name} {$mentee->last_name}",
                    'reference_id' => $mentorship->id,
                ]
            );

            return $mentorship;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function respondToRequest(User $user, $id, $status)
    {
        $mentorship = Mentorship::findOrFail($id);
        
        if ($mentorship->mentor_id !== $user->id && !$user->hasRole('Admin')) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Unauthorized', null, 403);
        }

        try {
            DB::beginTransaction();

            $mentorship->update([
                'status' => $status,
                'start_date' => $status === 'active' ? now() : null
            ]);

            DB::commit();

            $title = $status === 'active' ? 'Mentorship Request Approved' : 'Mentorship Request Declined';
            $body = $status === 'active' 
                ? "{$user->first_name} {$user->last_name} accepted your mentorship request." 
                : "{$user->first_name} {$user->last_name} declined your mentorship request.";

            app(\App\Services\NotificationService::class)->createNotification(
                $mentorship->mentee_id,
                'mentorship_response',
                [
                    'title' => $title,
                    'body' => $body,
                    'sender_id' => $user->id,
                    'sender_name' => "{$user->first_name} {$user->last_name}",
                    'reference_id' => $mentorship->id,
                    'status' => $status,
                ]
            );

            return $mentorship;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
