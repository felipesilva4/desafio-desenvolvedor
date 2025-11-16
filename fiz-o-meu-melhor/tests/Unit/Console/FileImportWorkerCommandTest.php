<?php

namespace Tests\Unit\Console;

use App\Repositories\MongoRepositoryInterface;
use App\Services\Contracts\ImportServiceInterface;
use Exception;
use Illuminate\Console\Command;
use Mockery;
use Mockery\MockInterface;
use App\Services\Contracts\QueuesServiceInterface;
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
        $mongoRepository = Mockery::mock(MongoRepositoryInterface::class);

        $queues = Mockery::mock(QueuesServiceInterface::class);
        $queues->shouldReceive('declareQueue')->once()->with('file-imports');
        $queues->shouldReceive('consumeOne')
            ->once()
            ->with('file-imports')
            ->andReturn(['payload' => ['upload_id' => 42], 'deliveryTag' => 'tag-123']);

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

        $queues->shouldReceive('ack')->once()->with('tag-123');

        $this->bindCommandDependencies($importService, $mongoRepository, $queues);

        $this->artisan('file-imports:consume')
            ->assertExitCode(Command::SUCCESS);
    }

    public function testHandleRejectsMessageWithoutUploadId(): void
    {
        $this->fakeRabbitConfig();

        $importService = Mockery::mock(ImportServiceInterface::class);
        $mongoRepository = Mockery::mock(MongoRepositoryInterface::class);

        $queues = Mockery::mock(QueuesServiceInterface::class);
        $queues->shouldReceive('declareQueue')->once()->with('file-imports');
        $queues->shouldReceive('consumeOne')
            ->once()
            ->with('file-imports')
            ->andReturn(['payload' => ['invalid' => true], 'deliveryTag' => 'tag-456']);
        $queues->shouldReceive('reject')->once()->with('tag-456', false);

        $importService->shouldNotReceive('handle');
        $mongoRepository->shouldNotReceive('insertMany');

        $this->bindCommandDependencies($importService, $mongoRepository, $queues);

        $this->artisan('file-imports:consume')
            ->assertExitCode(Command::SUCCESS);
    }

    public function testHandleRequeuesMessageWhenImportFails(): void
    {
        $this->fakeRabbitConfig();

        $importService = Mockery::mock(ImportServiceInterface::class);
        $mongoRepository = Mockery::mock(MongoRepositoryInterface::class);

        $queues = Mockery::mock(QueuesServiceInterface::class);
        $queues->shouldReceive('declareQueue')->once()->with('file-imports');
        $queues->shouldReceive('consumeOne')
            ->once()
            ->with('file-imports')
            ->andReturn(['payload' => ['upload_id' => 99], 'deliveryTag' => 'tag-789']);

        $importService
            ->shouldReceive('handle')
            ->once()
            ->with(99)
            ->andThrow(new Exception('fail'));

        $mongoRepository->shouldNotReceive('insertMany');

        $queues->shouldReceive('reject')->once()->with('tag-789', true);

        $this->bindCommandDependencies($importService, $mongoRepository, $queues);

        $this->artisan('file-imports:consume')
            ->assertExitCode(Command::FAILURE);
    }

    private function bindCommandDependencies(
        ImportServiceInterface|MockInterface $importService,
        MongoRepositoryInterface|MockInterface $mongoRepository,
        QueuesServiceInterface|MockInterface $queues,
    ): void {
        $this->app->instance(ImportServiceInterface::class, $importService);
        $this->app->instance(MongoRepositoryInterface::class, $mongoRepository);
        $this->app->instance(QueuesServiceInterface::class, $queues);
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

