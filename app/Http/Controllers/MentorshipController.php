<?php

namespace App\Http\Controllers;

use App\Services\MentorshipService;
use App\Http\Resources\MentorshipResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MentorshipController extends Controller
{
    protected $mentorshipService;

    public function __construct(MentorshipService $mentorshipService)
    {
        $this->mentorshipService = $mentorshipService;
    }

    public function myMentorships(Request $request): JsonResponse
    {
        $data = $this->mentorshipService->getMentorshipsForUser($request->user());

        return response()->json([
            'status' => 'success', 
            'data' => [
                'as_mentee' => MentorshipResource::collection($data['as_mentee']),
                'as_mentor' => MentorshipResource::collection($data['as_mentor'])
            ]
        ]);
    }

    public function requestMentorship(Request $request): JsonResponse
    {
        $request->validate([
            'mentor_id' => 'required',
            'goals' => 'required|string|max:1000'
        ]);

        try {
            $mentorship = $this->mentorshipService->requestMentorship(
                $request->user(),
                $request->mentor_id,
                $request->goals
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Mentorship request sent successfully',
                'data' => new MentorshipResource($mentorship)
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function respondToRequest(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,rejected'
        ]);

        try {
            $mentorship = $this->mentorshipService->respondToRequest(
                $request->user(),
                $id,
                $request->status
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Mentorship request updated',
                'data' => new MentorshipResource($mentorship)
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
