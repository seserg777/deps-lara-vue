<?php

namespace App\Support;

final class CatalogRemoteCredentials
{
    /**
     * @return array{email: string, password: string}
     */
    public static function fromRemoteApiKey(string $remote_api_key): array
    {
        if ($remote_api_key === '') {
            return ['email' => '', 'password' => ''];
        }

        $decoded = base64_decode($remote_api_key, true);
        if ($decoded === false || ! str_contains($decoded, ':')) {
            return ['email' => '', 'password' => ''];
        }

        [$email, $password] = explode(':', $decoded, 2);

        return ['email' => $email, 'password' => $password];
    }
}
