<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class Users
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        $response = Http::get(env('USER_API_URL', 'http://user-api:8000') . '/users');
        return $response->json();
    }
}
