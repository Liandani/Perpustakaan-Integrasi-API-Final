<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class Books
{
    public function __invoke($_, array $args)
    {
        $response = Http::get(env('BOOK_API_URL', 'http://book-api:8000') . '/books');
        return $response->json();
    }
}
