<?php

namespace App\Http\Controllers;

use App\Http\Requests\Talent\AttachTalentRequest;
use App\Http\Requests\Talent\StoreTalentLookupRequest;
use App\Http\Requests\Talent\UpdateTalentLookupRequest;
use App\Services\TalentService;
use App\Http\Resources\TalentResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TalentController extends Controller
{
    protected TalentService $talentService;

    public function __construct(TalentService $talentService)
    {
        $this->talentService = $talentService;
    }

    public function index(): JsonResponse
    {
        $talents = $this->talentService->getTalents();

        return response()->json([
            'status' => 'success', 
            'data' => TalentResource::collection($talents)
        ]);
    }

    public function attachTalent(AttachTalentRequest $request): JsonResponse
    {
        try {
            $user = $this->talentService->attachTalent($request->user(), $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Talent added to your profile successfully',
                'data' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function removeTalent(Request $request, $talent_id): JsonResponse
    {
        $this->talentService->removeTalent($request->user(), (int) $talent_id);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Talent removed from your profile'
        ]);
    }

    public function directory(Request $request): JsonResponse
    {
        $users = $this->talentService->getDirectory($request->user());

        return response()->json([
            'status' => 'success', 
            'data' => UserResource::collection($users)
        ]);
    }

    public function storeLookup(StoreTalentLookupRequest $request): JsonResponse
    {
        try {
            $talent = $this->talentService->storeLookup($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Talent lookup added successfully',
                'data' => new TalentResource($talent)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateLookup(UpdateTalentLookupRequest $request, $id): JsonResponse
    {
        try {
            $talent = $this->talentService->updateLookup((int) $id, $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Talent lookup updated successfully',
                'data' => new TalentResource($talent)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyLookup($id): JsonResponse
    {
        try {
            $this->talentService->deleteLookup((int) $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Talent lookup deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
