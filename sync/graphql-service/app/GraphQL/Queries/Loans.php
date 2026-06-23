<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class Loans
{
    public function __invoke($_, array $args)
    {
        $response = Http::get(env('LOAN_API_URL', 'http://loan-api:8000') . '/loans');
        return $response->json('data');
    }
}
