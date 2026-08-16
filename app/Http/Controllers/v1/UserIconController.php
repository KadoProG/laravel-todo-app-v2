<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserIconRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserIconController extends Controller
{
    /** 保存先ディレクトリ。ディスク直下が散らからないようにまとめる */
    private const DIRECTORY = 'user-icons';

    /**
     * ユーザーアイコンのアップロード
     *
     * multipart/form-data で `icon` を送る。既にアイコンがある場合は差し替える。
     */
    public function store(UpdateUserIconRequest $request, User $user): UserResource
    {
        $disk = $this->disk();

        // 保存に失敗したときに元のアイコンが消えないよう、削除は保存が済んでから行う。
        // ファイル名はランダムなので、新旧のパスが衝突することはない
        $path = $request->file('icon')->store(self::DIRECTORY, $disk);

        $this->deleteFile($user);

        $user->icon_path = $path;
        $user->save();

        return new UserResource($user);
    }

    /** ユーザーアイコンの削除 */
    public function destroy(Request $request, User $user): UserResource
    {
        $auth = $request->user();

        if ($auth === null || ! $auth->is($user)) {
            throw new AccessDeniedHttpException('アクセス権限がありません');
        }

        $this->deleteFile($user);

        $user->icon_path = null;
        $user->save();

        return new UserResource($user);
    }

    /**
     * ユーザーアイコンの配信
     *
     * 認証不要。`/api` 配下から返すことで、配信用のインフラを別に用意せずに済ませている。
     */
    public function show(User $user): StreamedResponse
    {
        $disk = Storage::disk($this->disk());

        if ($user->icon_path === null || ! $disk->exists($user->icon_path)) {
            throw new NotFoundHttpException('アイコンが設定されていません');
        }

        // URL はユーザーごとに固定で、更新時は icon_url の `?v=` が変わる。
        // そのためブラウザには長めにキャッシュさせてよい
        return $disk->response($user->icon_path, headers: [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function disk(): string
    {
        return config('filesystems.icons');
    }

    private function deleteFile(User $user): void
    {
        if ($user->icon_path === null) {
            return;
        }

        Storage::disk($this->disk())->delete($user->icon_path);
    }
}
