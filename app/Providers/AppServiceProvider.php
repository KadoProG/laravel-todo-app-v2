<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 絶対 URL の組み立てはリクエストではなく APP_URL を基準にする。
        // 本番は CloudFront → ALB → nginx と経由するため、リクエストから見えるホストや
        // プロトコルは実際の公開 URL と一致しない。公開先は 1 つに固定されているので、
        // ヘッダから復元せず設定値をそのまま使う。
        //
        // ホスト部だけを差し替える forceRootUrl は、スキーマをリクエスト側の値で
        // 上書きしてしまう（UrlGenerator::formatRoot）。forceScheme と揃えて指定する。
        URL::forceRootUrl(config('app.url'));
        URL::forceScheme(parse_url(config('app.url'), PHP_URL_SCHEME));

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
        JsonResource::withoutWrapping();
    }
}
