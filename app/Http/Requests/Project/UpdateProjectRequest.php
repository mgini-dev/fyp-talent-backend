<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status'      => 'sometimes|in:draft,active,completed,paused',
            'mentor_id'   => 'nullable|exists:users,id',
            'talent_id'   => 'sometimes|nullable|exists:talents,id',
        ];
    }
}
