<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'is_public' => 'required|boolean',
            'description' => 'nullable|string',
            'expired_at' => 'nullable|date',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'required|integer|exists:users,id',
        ];
    }
}
