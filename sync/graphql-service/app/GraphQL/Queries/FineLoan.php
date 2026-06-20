<?php

namespace App\GraphQL\Queries;

use Illuminate\Support\Facades\Http;

final class FineLoan
{
    /**
     * @param  mixed  $parent
     * @param  array  $args
     */
    public function __invoke($parent, array $args)
    {
        $loanId = $parent['loan_id'] ?? null;
        if (!$loanId) {
            return null;
        }

        $response = Http::get(env('LOAN_API_URL', 'http://loan-api:8000') . '/loans/' . $loanId);
        
        if ($response->failed() || $response->status() === 404) {
            return null;
        }

        return $response->json('data');
    }
}
