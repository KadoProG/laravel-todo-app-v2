<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        if (! $task || ! $this->user()) {
            return false;
        }

        $userId = $this->user()->id;

        $assignedUserIds = $task->assignedUsers->pluck('id')->toArray(); // リレーションからIDだけ抽出

        return $task->created_user_id === $userId || in_array($userId, $assignedUserIds);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'is_done' => 'sometimes|boolean',
        ];
    }
}
