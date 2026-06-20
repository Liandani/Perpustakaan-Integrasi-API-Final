<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        $userApiUrl = env('USER_API_URL', 'http://user-api:8000');
        $bookApiUrl = env('BOOK_API_URL', 'http://book-api:8000');

        // Fetch User (Latest if not specified)
        if (!$userId) {
            $userResponse = $client->get($userApiUrl . '/users');
            if ($userResponse->getStatusCode() == 200) {
                $users = json_decode($userResponse->getBody(), true);
                if (!empty($users)) {
                    $user = end($users);
                } else {
                    $user = ['id' => 1, 'name' => 'Mock User'];
                }
            } else {
                $user = ['id' => 1, 'name' => 'Mock User'];
            }
        } else {
            $userResponse = $client->get($userApiUrl . '/users/' . $userId);
            $user = $userResponse->getStatusCode() == 200 ? json_decode($userResponse->getBody(), true) : ['id' => (int)$userId, 'name' => 'Mock User'];
        }

        // Fetch Book (Latest if not specified)
        if (!$bookId) {
            $bookResponse = $client->get($bookApiUrl . '/books');
            if ($bookResponse->getStatusCode() == 200) {
                $books = json_decode($bookResponse->getBody(), true);
                if (!empty($books)) {
                    $book = end($books);
                } else {
                    $book = ['id' => 3, 'title' => 'Mock Book'];
                }
            } else {
                $book = ['id' => 3, 'title' => 'Mock Book'];
            }
        } else {
            $bookResponse = $client->get($bookApiUrl . '/books/' . $bookId);
            $book = $bookResponse->getStatusCode() == 200 ? json_decode($bookResponse->getBody(), true) : ['id' => (int)$bookId, 'title' => 'Mock Book'];
        }

        // RabbitMQ Connection
        $rabbitHost = env('RABBITMQ_HOST', 'rabbitmq');
        $connection = new AMQPStreamConnection(
            $rabbitHost,
            5672,
            'guest',
            'guest'
        );

        $channel = $connection->channel();

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
            'message' => 'Book Borrowed Successfully'
        ];

        $msg = new AMQPMessage(json_encode($data));

        $channel->basic_publish($msg, '', 'book_queue');

        $channel->close();
        $connection->close();

        return response()->json([
            'status' => 'Message Sent',
            'data' => $data
        ]);
    }
}
