<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content'    => 'required|string|max:5000',
            'type'       => 'sometimes|in:post,discussion,announcement',
            'visibility' => 'sometimes|in:public,connections,followers',
            'talent_id'  => 'sometimes|nullable|exists:talents,id',
        ];
    }
}
