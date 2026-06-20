<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class FineUser
{
    /**
     * @param  mixed  $parent
     * @param  array  $args
     */
    public function __invoke($parent, array $args)
    {
        $userId = $parent['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $response = Http::get(env('USER_API_URL', 'http://user-api:8000') . '/users/' . $userId);
        
        if ($response->failed() || $response->status() === 404) {
            return null;
        }

        return $response->json();
    }
}
