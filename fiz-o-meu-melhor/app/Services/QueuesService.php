<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use JsonException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use App\Services\Contracts\QueuesServiceInterface;

class QueuesService implements QueuesServiceInterface
{
    private const DELIVERY_MODE_PERSISTENT = 2;

    private string $host;
    private int $port;
    private string $user;
    private string $password;
    private string $defaultQueue;
    private ?AMQPStreamConnection $activeConnection = null;
    private ?AMQPChannel $activeChannel = null;

    public function __construct()
    {
        $config = config('services.rabbitmq', []);

        $this->host = $config['host'] ?? 'rabbitmq';
        $this->port = (int) ($config['port'] ?? 5672);
        $this->user = $config['user'] ?? 'guest';
        $this->password = $config['password'] ?? 'guest';
        $this->defaultQueue = $config['queue'] ?? 'file-imports';
    }

    public function dispatchToDefault(array $payload): void
    {
        $this->publish($this->defaultQueue, $payload);
    }

    public function publish(string $queue, array $payload): void
    {
        try {
            [$connection, $channel] = $this->openChannel();
            $this->declareQueue($queue);

            $message = new AMQPMessage(
                json_encode($payload, JSON_THROW_ON_ERROR),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => self::DELIVERY_MODE_PERSISTENT,
                ]
            );

            $channel->basic_publish($message, '', $queue);
            $channel->close();
            $connection->close();
        } catch (AMQPIOException | AMQPRuntimeException | JsonException $exception) {
            Log::error('Falha ao publicar na fila.', [
                'queue' => $queue,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function declareQueue(string $queue): void
    {
        [$connection, $channel] = $this->openChannel();
        $channel->queue_declare($queue, false, true, false, false);
        $channel->close();
        $connection->close();
    }

    public function consumeOne(string $queue): ?array
    {
        [$connection, $channel] = $this->openChannel();
        $channel->queue_declare($queue, false, true, false, false);

        $message = $channel->basic_get($queue, false);

        if (! $message instanceof AMQPMessage) {
            $channel->close();
            $connection->close();
            return null;
        }

        $payload = json_decode($message->body, true) ?? [];
        $deliveryTag = $message->delivery_info['delivery_tag'] ?? null;

        $this->activeChannel = $channel;
        $this->activeConnection = $connection;

        return [
            'payload' => is_array($payload) ? $payload : [],
            'deliveryTag' => $deliveryTag,
        ];
    }

    public function ack(string $deliveryTag): void
    {
        if (! $this->activeChannel || ! $this->activeConnection) {
            throw new AMQPRuntimeException('No active channel for ACK. Call consumeOne first.');
        }
        $this->activeChannel->basic_ack($deliveryTag);
        $this->closeActive();
    }

    public function reject(string $deliveryTag, bool $requeue = true): void
    {
        if (! $this->activeChannel || ! $this->activeConnection) {
            throw new AMQPRuntimeException('No active channel for REJECT. Call consumeOne first.');
        }
        $this->activeChannel->basic_reject($deliveryTag, $requeue);
        $this->closeActive();
    }

    /**
     * @return array{0: AMQPStreamConnection, 1: \PhpAmqpLib\Channel\AMQPChannel}
     */
    private function openChannel(): array
    {
        $connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password
        );

        $channel = $connection->channel();
        return [$connection, $channel];
    }

    private function closeActive(): void
    {
        try {
            if ($this->activeChannel) {
                $this->activeChannel->close();
            }
        } finally {
            if ($this->activeConnection) {
                $this->activeConnection->close();
            }
            $this->activeChannel = null;
            $this->activeConnection = null;
        }
    }
}

