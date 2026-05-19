<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTalentLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => "required|string|max:255|unique:talents,name,{$id}",
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
