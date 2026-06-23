<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class LoanBook
{
    /**
     * @param  mixed  $parent
     * @param  array  $args
     */
    public function __invoke($parent, array $args)
    {
        $bookId = $parent['book_id'] ?? null;
        if (!$bookId) {
            return null;
        }

        $response = Http::get(env('BOOK_API_URL', 'http://book-api:8000') . '/books/' . $bookId);
        
        if ($response->failed() || $response->status() === 404) {
            return null;
        }

        return $response->json('data');
    }
}
