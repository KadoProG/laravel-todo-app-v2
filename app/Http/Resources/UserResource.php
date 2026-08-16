<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static $wrap = 'user';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'icon_url' => $this->iconUrl(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * アイコン配信エンドポイントの URL。
     *
     * URL がユーザーごとに固定になるため、更新時刻をクエリに付けてブラウザキャッシュを無効化する。
     */
    private function iconUrl(): ?string
    {
        if ($this->icon_path === null) {
            return null;
        }

        return route('users.icon.show', [
            'user' => $this->id,
            'v' => $this->updated_at?->timestamp,
        ]);
    }
}
