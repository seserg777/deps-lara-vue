<?php

use App\Support\CatalogRemoteCredentials;

$credentials = CatalogRemoteCredentials::fromRemoteApiKey((string) env('REMOTE_API_KEY', ''));

return [

    'remote_base_url' => rtrim((string) env('REMOTE_API_URL', ''), '/ '),

    'remote_entry_path' => '/index.php?option=com_jshopping&controller=addon_api',

    'email' => $credentials['email'],

    'password' => $credentials['password'],

    'timeout' => (int) env('CATALOG_API_TIMEOUT', 30),

    'token_cache_ttl' => (int) env('CATALOG_API_TOKEN_CACHE_TTL', 3300),

    'tree_cache_ttl' => (int) env('CATALOG_API_TREE_CACHE_TTL', 300),

];
