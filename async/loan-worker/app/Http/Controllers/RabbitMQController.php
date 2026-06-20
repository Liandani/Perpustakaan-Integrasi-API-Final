<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQController extends Controller
{
    public function send()
    {
        $userId = request()->input('userId') ?? request()->query('userId');
        $bookId = request()->input('bookId') ?? request()->query('bookId');

        $client = new Client([
            'http_errors' => false,
            'connect_timeout' => 5,
            'timeout' => 10,
        ]);

        try {
            $userApiUrl = env('USER_API_URL', 'http://user-api:8000');
            $bookApiUrl = env('BOOK_API_URL', 'http://book-api:8000');

            // Fetch User (Latest if not specified)
            if (!$userId) {
                $userResponse = $client->get($userApiUrl . '/users');
                if ($userResponse->getStatusCode() == 200) {
                    $users = json_decode($userResponse->getBody()->getContents(), true);
                    if (!empty($users)) {
                        $user = end($users);
                        $userId = $user['id'];
                    } else {
                        $user = ['id' => 1, 'name' => 'Mock User'];
                        $userId = 1;
                    }
                } else {
                    $user = ['id' => 1, 'name' => 'Mock User'];
                    $userId = 1;
                }
            } else {
                $userResponse = $client->get($userApiUrl . '/users/' . $userId);
                $user = $userResponse->getStatusCode() == 200 ? json_decode($userResponse->getBody()->getContents(), true) : ['id' => (int)$userId, 'name' => 'Mock User'];
            }

            // Fetch Book (Latest if not specified)
            if (!$bookId) {
                $bookResponse = $client->get($bookApiUrl . '/books');
                if ($bookResponse->getStatusCode() == 200) {
                    $books = json_decode($bookResponse->getBody()->getContents(), true);
                    if (!empty($books)) {
                        $book = end($books);
                        $bookId = $book['id'];
                    } else {
                        $book = ['id' => 3, 'title' => 'Mock Book'];
                        $bookId = 3;
                    }
                } else {
                    $book = ['id' => 3, 'title' => 'Mock Book'];
                    $bookId = 3;
                }
            } else {
                $bookResponse = $client->get($bookApiUrl . '/books/' . $bookId);
                $book = $bookResponse->getStatusCode() == 200 ? json_decode($bookResponse->getBody()->getContents(), true) : ['id' => (int)$bookId, 'title' => 'Mock Book'];
            }

            // RabbitMQ Connection
            $rabbitHost = env('RABBITMQ_HOST', 'rabbitmq');
            $rabbitPort = (int) env('RABBITMQ_PORT', 5672);

            $connection = new AMQPStreamConnection(
                $rabbitHost,
                $rabbitPort,
                'guest',
                'guest'
            );


            $channel = $connection->channel();

            // Safer queue declaration for typical consumer workflow
            // queue name, durable, exclusive, auto_delete, nowait
            $channel->queue_declare(
                'book_queue',
                false,
                true,
                false,
                false
            );

            $data = [
                'user' => $user,
                'book' => $book,
                'message' => 'Book Borrowed Successfully',
                'meta' => [
                    'userId' => (int) $userId,
                    'bookId' => (int) $bookId,
                ],
            ];

            $msg = new AMQPMessage(
                json_encode($data),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => 2, // persistent
                ]
            );

            // Using default exchange '' (routing key must be queue name)
            $channel->basic_publish($msg, '', 'book_queue');

            $channel->close();
            $connection->close();

            return response()->json([
                'status' => 'Message Sent',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            // Return a meaningful error instead of a generic 500
            return response()->json([
                'status' => 'Error',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ], 500);
        }
    }
}

