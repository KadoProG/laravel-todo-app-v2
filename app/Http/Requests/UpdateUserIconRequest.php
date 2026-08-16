<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserIconRequest extends FormRequest
{
    public function authorize(): bool
    {
        $auth = $this->user();
        $target = $this->route('user');

        return $auth !== null && $auth->is($target);
    }

    public function rules(): array
    {
        return [
            'icon' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ];
    }
}
