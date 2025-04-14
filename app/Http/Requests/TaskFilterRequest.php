<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_public' => 'nullable|boolean',
            'is_done' => 'nullable|boolean',
            'expired_before' => 'nullable|date',
            'expired_after' => 'nullable|date',
            'created_user_id' => 'nullable|integer|exists:users,id',
            'created_user_ids' => 'nullable|array',
            'created_user_ids.*' => 'integer|exists:users,id',
            'assigned_user_id' => 'nullable|integer|exists:users,id',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'integer|exists:users,id',
            'sort_by' => 'nullable|in:title,expired_at,created_at,updated_at',
            'sort_order' => 'nullable|in:asc,desc',
        ];
    }

    public function validatedFilters(): array
    {
        return $this->only([
            'is_public',
            'is_done',
            'expired_before',
            'expired_after',
            'created_user_id',
            'created_user_ids',
            'assigned_user_id',
            'assigned_user_ids',
        ]);
    }
}
