<?php

namespace Tests\Unit\Console;

use App\Repositories\MongoUploadRepositoryInterface;
use App\Services\Contracts\ImportServiceInterface;
use Exception;
use Illuminate\Console\Command;
use Mockery;
use Mockery\MockInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Tests\TestCase;

class FileImportWorkerCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testHandleProcessesValidMessageAndInsertsDocuments(): void
    {
        $this->fakeRabbitConfig();

        $importService = Mockery::mock(ImportServiceInterface::class);
        $mongoRepository = Mockery::mock(MongoUploadRepositoryInterface::class);

        $channel = $this->mockConnection();

        $channel->shouldReceive('queue_declare')
            ->once()
            ->with('file-imports', false, true, false, false);

        $message = new AMQPMessage(json_encode(['upload_id' => 42]));
        $message->delivery_info = ['delivery_tag' => 'tag-123'];

        $channel->shouldReceive('basic_get')
            ->once()
            ->with('file-imports', false)
            ->andReturn($message);

        $documents = [['foo' => 'bar']];

        $importService
            ->shouldReceive('handle')
            ->once()
            ->with(42)
            ->andReturn($documents);

        $mongoRepository
            ->shouldReceive('insertMany')
            ->once()
            ->with($documents);

        $channel->shouldReceive('basic_ack')
            ->once()
            ->with('tag-123');

        $this->bindCommandDependencies($importService, $mongoRepository);

        $this->artisan('file-imports:consume')
            ->assertExitCode(Command::SUCCESS);
    }

    public function testHandleRejectsMessageWithoutUploadId(): void
    {
        $this->fakeRabbitConfig();

        $importService = Mockery::mock(ImportServiceInterface::class);
        $mongoRepository = Mockery::mock(MongoUploadRepositoryInterface::class);

        $channel = $this->mockConnection();

        $channel->shouldReceive('queue_declare')
            ->once()
            ->with('file-imports', false, true, false, false);

        $message = new AMQPMessage(json_encode(['invalid' => true]));
        $message->delivery_info = ['delivery_tag' => 'tag-456'];

        $channel->shouldReceive('basic_get')
            ->once()
            ->with('file-imports', false)
            ->andReturn($message);

        $channel->shouldReceive('basic_reject')
            ->once()
            ->with('tag-456', false);

        $importService->shouldNotReceive('handle');
        $mongoRepository->shouldNotReceive('insertMany');

        $this->bindCommandDependencies($importService, $mongoRepository);

        $this->artisan('file-imports:consume')
            ->assertExitCode(Command::SUCCESS);
    }

    public function testHandleRequeuesMessageWhenImportFails(): void
    {
        $this->fakeRabbitConfig();

        $importService = Mockery::mock(ImportServiceInterface::class);
        $mongoRepository = Mockery::mock(MongoUploadRepositoryInterface::class);

        $channel = $this->mockConnection();

        $channel->shouldReceive('queue_declare')
            ->once()
            ->with('file-imports', false, true, false, false);

        $message = new AMQPMessage(json_encode(['upload_id' => 99]));
        $message->delivery_info = ['delivery_tag' => 'tag-789'];

        $channel->shouldReceive('basic_get')
            ->once()
            ->with('file-imports', false)
            ->andReturn($message);

        $importService
            ->shouldReceive('handle')
            ->once()
            ->with(99)
            ->andThrow(new Exception('fail'));

        $mongoRepository->shouldNotReceive('insertMany');

        $channel->shouldReceive('basic_reject')
            ->once()
            ->with('tag-789', true);

        $this->bindCommandDependencies($importService, $mongoRepository);

        $this->artisan('file-imports:consume')
            ->assertExitCode(Command::FAILURE);
    }

    private function mockConnection(): AMQPChannel|MockInterface
    {
        $channel = Mockery::mock(AMQPChannel::class);

        $connection = Mockery::mock('overload:' . AMQPStreamConnection::class);
        $connection->shouldReceive('channel')
            ->once()
            ->andReturn($channel);

        $connection->shouldReceive('close')
            ->once();

        $channel->shouldReceive('close')
            ->once();

        return $channel;
    }

    private function bindCommandDependencies(
        ImportServiceInterface|MockInterface $importService,
        MongoUploadRepositoryInterface|MockInterface $mongoRepository,
    ): void {
        $this->app->instance(ImportServiceInterface::class, $importService);
        $this->app->instance(MongoUploadRepositoryInterface::class, $mongoRepository);
    }

    private function fakeRabbitConfig(): void
    {
        config()->set('services.rabbitmq', [
            'host' => 'rabbit',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'queue' => 'file-imports',
        ]);
    }
}

