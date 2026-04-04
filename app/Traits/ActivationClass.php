<?php
namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait ActivationClass
{
    public function is_local(): bool
    {
        return true;
    }

    public function getDomain(): string
    {
        return str_replace(["http://", "https://", "www."], "", url('/'));
    }

    public function getSystemAddonCacheKey(string|null $app = 'default'): string
    {
        return str_replace('-', '_', Str::slug('cache_system_addons_for_' . $app . '_' . $this->getDomain()));
    }

    public function getAddonsConfig(): array
    {
        $apps = ['admin_panel', 'provider_app', 'serviceman_app'];
        $appConfig = [];
        foreach ($apps as $app) {
            $appConfig[$app] = [
                "active" => "1",
                "name" => "Goya",
                "identifier" => "goya",
                "username" => "goya",
                "purchase_key" => "goya",
                "software_id" => "goya",
                "domain" => $this->getDomain(),
                "software_type" => $app == 'admin_panel' ? "product" : 'addon',
            ];
        }
        return $appConfig;
    }

    public function getCacheTimeoutByDays(int $days = 3): int
    {
        return 60 * 60 * 24 * $days;
    }

    public function getRequestConfig(string|null $username = null, string|null $purchaseKey = null, string|null $softwareId = null, string|null $softwareType = null, string|null $name = null, string|null $identifier = null): array
    {
        return [
            "active" => 1,
            "name" => "Goya",
            "identifier" => "goya",
            "username" => "goya",
            "purchase_key" => "goya",
            "software_id" => "goya",
            "domain" => $this->getDomain(),
            "software_type" => "product",
            "errors" => [],
        ];
    }


    public function checkActivationCache(string|null $app)
    {
        return true;
    }

    public function updateActivationConfig($app, $response): void
    {
        // No need to update file
    }
}
