<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'is_public' => 'sometimes|boolean',
            'expired_at' => 'nullable|date',
            'description' => 'nullable|string',
            'is_done' => 'sometimes|boolean',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'required|integer|exists:users,id',
        ];
    }
}
