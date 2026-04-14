<?php

namespace Tests\Unit;

use App\Support\CatalogRemoteCredentials;
use PHPUnit\Framework\TestCase;

class CatalogRemoteCredentialsTest extends TestCase
{
    public function test_empty_key_returns_empty_strings(): void
    {
        $result = CatalogRemoteCredentials::fromRemoteApiKey('');

        $this->assertSame(['email' => '', 'password' => ''], $result);
    }

    public function test_decodes_base64_email_password(): void
    {
        $encoded = base64_encode('user@example.com:secret123');

        $result = CatalogRemoteCredentials::fromRemoteApiKey($encoded);

        $this->assertSame('user@example.com', $result['email']);
        $this->assertSame('secret123', $result['password']);
    }

    public function test_password_may_contain_colons(): void
    {
        $encoded = base64_encode('a@b.c:pass:word:with:colons');

        $result = CatalogRemoteCredentials::fromRemoteApiKey($encoded);

        $this->assertSame('a@b.c', $result['email']);
        $this->assertSame('pass:word:with:colons', $result['password']);
    }

    public function test_invalid_base64_returns_empty(): void
    {
        $result = CatalogRemoteCredentials::fromRemoteApiKey('not-valid-base64!!!');

        $this->assertSame(['email' => '', 'password' => ''], $result);
    }
}
