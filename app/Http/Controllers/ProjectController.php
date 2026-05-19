<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Requests\Project\StoreProjectUpdateRequest;
use App\Services\ProjectService;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectUpdateResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    protected ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function index(Request $request): JsonResponse
    {
        $projects = $this->projectService->getProjects($request->user());

        return response()->json([
            'status' => 'success', 
            'data' => ProjectResource::collection($projects)
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->createProject($request->user(), $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Project created!',
            'data'    => new ProjectResource($project->load('student', 'mentor', 'talent')),
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $project = $this->projectService->getProject((int) $id);

        return response()->json([
            'status' => 'success', 
            'data' => new ProjectResource($project)
        ]);
    }

    public function update(UpdateProjectRequest $request, $id): JsonResponse
    {
        try {
            $project = $this->projectService->updateProject($request->user(), (int) $id, $request->validated());

            return response()->json([
                'status' => 'success', 
                'message' => 'Project updated', 
                'data' => new ProjectResource($project)
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function addUpdate(StoreProjectUpdateRequest $request, $id): JsonResponse
    {
        try {
            $update = $this->projectService->addProjectUpdate($request->user(), (int) $id, $request->validated());

            return response()->json([
                'status' => 'success', 
                'data' => new ProjectUpdateResource($update->load('author'))
            ], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $this->projectService->deleteProject($request->user(), (int) $id);

        return response()->json(['status' => 'success', 'message' => 'Project deleted']);
    }
}
