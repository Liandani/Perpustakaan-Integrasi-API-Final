<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

Route::get('/', function () {
    return response()->json(['message' => 'API Gateway is running']);
});

// Proxy function
function proxyRequest(Request $request, $serviceUrl) {
    $method = strtolower($request->method());
    
    // For GET requests, we shouldn't send a JSON body.
    if ($method === 'get') {
        $response = Http::get($serviceUrl, $request->all());
    } else {
        $response = Http::$method($serviceUrl, $request->all());
    }

    $contentType = $response->header('Content-Type');
    return response($response->body(), $response->status())
        ->header('Content-Type', $contentType ? $contentType : 'application/json');
}

// User Service
Route::any('/users/{path?}', function (Request $request, $path = null) {
    $url = env('USER_API_URL', 'http://user-api:8000') . '/users' . ($path ? '/' . $path : '');
    return proxyRequest($request, $url);
})->where('path', '.*');

// Book Service
Route::any('/books/{path?}', function (Request $request, $path = null) {
    $url = env('BOOK_API_URL', 'http://book-api:8000') . '/books' . ($path ? '/' . $path : '');
    return proxyRequest($request, $url);
})->where('path', '.*');

// Loan Service
Route::any('/loans/{path?}', function (Request $request, $path = null) {
    $url = env('LOAN_API_URL', 'http://loan-api:8000') . '/loans' . ($path ? '/' . $path : '');
    return proxyRequest($request, $url);
})->where('path', '.*');

Route::any('/send-message', function (Request $request) {
    $url = env('LOAN_API_URL', 'http://loan-api:8000') . '/send-message';
    return proxyRequest($request, $url);
});

// Fine Service
Route::any('/fines/{path?}', function (Request $request, $path = null) {
    $url = env('FINE_API_URL', 'http://fine-api:8000') . '/fines' . ($path ? '/' . $path : '');
    return proxyRequest($request, $url);
})->where('path', '.*');

Route::any('/consume-message', function (Request $request) {
    $url = env('FINE_API_URL', 'http://fine-api:8000') . '/consume-message';
    return proxyRequest($request, $url);
});

// GraphQL Service
Route::any('/graphql', function (Request $request) {
    $url = env('GRAPHQL_SERVICE_URL', 'http://graphql-service:8000') . '/graphql';
    return proxyRequest($request, $url);
});

Route::any('/graphiql', function (Request $request) {
    $url = env('GRAPHQL_SERVICE_URL', 'http://graphql-service:8000') . '/graphiql';
    return proxyRequest($request, $url);
});
