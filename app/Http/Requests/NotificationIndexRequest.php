<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationIndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:0'],
            'size' => ['integer', 'min:1', 'max:100'],
        ];
    }

    /** ページ番号（0始まり） */
    public function page(): int
    {
        return (int) $this->input('page', 0);
    }

    /** 1ページあたりの件数 */
    public function size(): int
    {
        return (int) $this->input('size', 20);
    }
}
