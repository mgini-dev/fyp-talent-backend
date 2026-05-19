<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

class StoreTalentLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:talents,name|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
