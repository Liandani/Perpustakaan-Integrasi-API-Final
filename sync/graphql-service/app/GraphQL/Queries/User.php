<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class User
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        $id = $args['id'];
        $response = Http::get(env('USER_API_URL', 'http://user-api:8000') . '/users/' . $id);
        
        if ($response->failed() || $response->status() === 404) {
            return null;
        }
        
        return $response->json();
    }
}
