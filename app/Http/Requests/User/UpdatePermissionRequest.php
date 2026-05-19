<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name'         => "required|string|unique:permissions,name,{$id}|max:255",
            'display_name' => 'required|string|max:255',
            'module'       => 'required|string|max:255',
            'description'  => 'nullable|string',
        ];
    }
}
