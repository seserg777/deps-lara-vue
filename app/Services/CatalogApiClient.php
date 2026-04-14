<?php

namespace App\Services;

use App\Exceptions\CatalogApiException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CatalogApiClient
{
    private const TOKEN_CACHE_KEY = 'catalog.remote_bearer_token';

    public function call(string $section, string $task, array $args = []): mixed
    {
        return $this->executeWithTokenRetry(function (string $token) use ($section, $task, $args): mixed {
            return $this->postAuthorized($token, $section, $task, $args);
        });
    }

    public function forgetToken(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
    }

    private function executeWithTokenRetry(callable $callback): mixed
    {
        try {
            return $callback($this->getBearerToken());
        } catch (CatalogApiException $e) {
            if ($this->shouldRefreshToken($e)) {
                $this->forgetToken();

                return $callback($this->getBearerToken());
            }
            throw $e;
        }
    }

    private function shouldRefreshToken(CatalogApiException $e): bool
    {
        if ($e->report !== null && str_contains(strtolower($e->report), 'token')) {
            return true;
        }

        return $e->code_api === 7 || $e->code_api === 8;
    }

    private function getBearerToken(): string
    {
        $ttl = max(60, (int) config('catalog.token_cache_ttl', 3300));

        return Cache::remember(self::TOKEN_CACHE_KEY, $ttl, function (): string {
            return $this->openConnection();
        });
    }

    private function openConnection(): string
    {
        $url = $this->endpointUrl();
        $email = config('catalog.email');
        $password = config('catalog.password');
        if ($url === '' || $email === '' || $password === '') {
            throw new CatalogApiException('Catalog API is not configured.');
        }

        try {
            $response = Http::timeout((int) config('catalog.timeout', 30))
                ->withBasicAuth($email, $password)
                ->asForm()
                ->post($url, [
                    'section' => 'connection',
                    'task' => 'open',
                    'format' => 'json',
                ]);
        } catch (RequestException $e) {
            throw new CatalogApiException('Catalog API connection failed: '.$e->getMessage());
        }

        if ($response->failed()) {
            throw new CatalogApiException('Catalog API HTTP error on connection: '.$response->status());
        }

        return $this->decodeSuccessResult($response->json(), 'connection open');
    }

    private function postAuthorized(string $token, string $section, string $task, array $args): mixed
    {
        $url = $this->endpointUrl();
        if ($url === '') {
            throw new CatalogApiException('Catalog API is not configured.');
        }

        $payload = [
            'section' => $section,
            'task' => $task,
            'format' => 'json',
        ];
        if ($args !== []) {
            $payload['args'] = $args;
        }

        try {
            $response = Http::timeout((int) config('catalog.timeout', 30))
                ->withToken($token)
                ->asForm()
                ->post($url, $payload);
        } catch (RequestException $e) {
            throw new CatalogApiException('Catalog API request failed: '.$e->getMessage());
        }

        if ($response->failed()) {
            throw new CatalogApiException('Catalog API HTTP error: '.$response->status());
        }

        return $this->decodeSuccessResult($response->json(), $section.'/'.$task);
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function decodeSuccessResult(?array $json, string $context): mixed
    {
        if ($json === null) {
            throw new CatalogApiException('Invalid JSON from catalog API ('.$context.').');
        }

        $status = $json['status'] ?? null;
        $code = isset($json['code']) ? (int) $json['code'] : null;
        $report = isset($json['report']) ? (string) $json['report'] : null;

        if ($status !== 'ok') {
            throw new CatalogApiException(
                'Catalog API error ('.$context.'): '.($report ?? 'unknown'),
                is_string($status) ? $status : null,
                $code,
                $report
            );
        }

        return $json['result'] ?? null;
    }

    private function endpointUrl(): string
    {
        $base = rtrim((string) config('catalog.remote_base_url', ''), '/');
        $path = (string) config('catalog.remote_entry_path', '/index.php?option=com_jshopping&controller=addon_api');

        return $base === '' ? '' : $base.$path;
    }
}
