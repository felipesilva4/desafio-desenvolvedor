<?php

namespace Tests\Unit\Services;

use App\Services\QueuesService;
use Illuminate\Support\Facades\Log;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use Tests\TestCase;

class QueuesServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testDispatchToDefaultPublishesMessage(): void
    {
        config()->set('services.rabbitmq', [
            'host' => 'rabbit',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'queue' => 'file-imports',
        ]);

        $payload = ['upload_id' => 10];

        $channel = $this->mockConnection();

        $channel->shouldReceive('queue_declare')
            ->once()
            ->with('file-imports', false, true, false, false);

        $channel->shouldReceive('basic_publish')
            ->once()
            ->withArgs(function (AMQPMessage $message, string $exchange, string $routingKey) use ($payload) {
                $this->assertSame('', $exchange);
                $this->assertSame('file-imports', $routingKey);
                $this->assertSame($payload, json_decode($message->body, true, 512, JSON_THROW_ON_ERROR));
                $this->assertSame('application/json', $message->get('content_type'));
                $this->assertSame(2, $message->get('delivery_mode'));
                return true;
            });

        $service = new QueuesService();

        $service->dispatchToDefault($payload);
    }

    public function testPublishLogsAndThrowsWhenConnectionFails(): void
    {
        config()->set('services.rabbitmq', [
            'host' => 'rabbit',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'queue' => 'file-imports',
        ]);

        $exception = new AMQPRuntimeException('connection failed');

        $connection = Mockery::mock('overload:' . AMQPStreamConnection::class);
        $connection->shouldReceive('channel')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('Falha ao publicar na fila.', [
                'queue' => 'custom',
                'error' => 'connection failed',
            ]);

        $service = new QueuesService();

        $this->expectException(AMQPRuntimeException::class);

        $service->publish('custom', ['data' => 'value']);
    }

    private function mockConnection(): AMQPChannel
    {
        $channel = Mockery::mock(AMQPChannel::class);

        $connection = Mockery::mock('overload:' . AMQPStreamConnection::class);
        $connection->shouldReceive('channel')
            ->once()
            ->andReturn($channel);

        $channel->shouldReceive('close')
            ->once();

        $connection->shouldReceive('close')
            ->once();

        return $channel;
    }
}

