<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class Loan
{
    public function __invoke($_, array $args)
    {
        $id = $args['id'];
        $response = Http::get(env('LOAN_API_URL', 'http://loan-api:8000') . '/loans/' . $id);
        
        if ($response->failed() || $response->status() === 404) {
            return null;
        }
        
        return $response->json('data');
    }
}
