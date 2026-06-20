<?php

namespace App\Http\Controllers;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQConsumerController extends Controller
{
    public function consume()
    {
        try {
            $connection = new AMQPStreamConnection(
                'rabbitmq',
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

            // Cek jumlah pesan di antrian terlebih dahulu
            list(, $messageCount,) = $channel->queue_declare(
                'book_queue',
                true // passive: hanya cek, tidak buat ulang
            );

            if ($messageCount === 0) {
                $channel->close();
                $connection->close();

                return response()->json([
                    'status' => 'No Message',
                    'data' => null,
                    'message' => 'Antrian kosong. Silakan kirim pesan terlebih dahulu melalui /send-message'
                ]);
            }

            $messageData = null;

            $callback = function ($msg) use (&$messageData) {
                $messageData = json_decode($msg->body, true);
            };

            $channel->basic_consume(
                'book_queue',
                '',
                false,
                true,
                false,
                false,
                $callback
            );

            if ($channel->is_consuming()) {
                // Tambahkan timeout 5 detik agar tidak hang selamanya
                $channel->wait(null, false, 5);
            }

            $channel->close();
            $connection->close();

            return response()->json([
                'status' => 'Message Consumed',
                'data' => $messageData
            ]);
        } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
            return response()->json([
                'status' => 'Timeout',
                'data' => null,
                'message' => 'Tidak ada pesan dalam antrian. Silakan kirim pesan terlebih dahulu melalui /send-message'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
