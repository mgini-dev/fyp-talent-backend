<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

class AttachTalentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'talent_id' => 'required|exists:talents,id',
            'proficiency' => 'required|string|in:Beginner,Intermediate,Expert',
            'portfolio_url' => 'nullable|url',
        ];
    }
}
