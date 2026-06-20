<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class Fines
{
    public function __invoke($_, array $args)
    {
        $response = Http::get(env('FINE_API_URL', 'http://fine-api:8000') . '/fines');
        return $response->json('data');
    }
}
