<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class Fine
{
    public function __invoke($_, array $args)
    {
        $id = $args['id'];
        $response = Http::get(env('FINE_API_URL', 'http://fine-api:8000') . '/fines/' . $id);
        
        if ($response->failed() || $response->status() === 404) {
            return null;
        }
        
        return $response->json('data');
    }
}
