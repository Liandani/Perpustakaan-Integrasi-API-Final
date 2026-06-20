<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class Book
{
    public function __invoke($_, array $args)
    {
        $id = $args['id'];
        $response = Http::get(env('BOOK_API_URL', 'http://book-api:8000') . '/books/' . $id);
        
        if ($response->failed() || $response->status() === 404) {
            return null;
        }
        
        return $response->json();
    }
}
