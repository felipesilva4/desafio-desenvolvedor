<?php

namespace App\Console\Commands;

use App\Repositories\MongoRepositoryInterface;
use App\Services\Contracts\ImportServiceInterface;
use App\Services\Contracts\QueuesServiceInterface;
use Illuminate\Console\Command;
use Throwable;

class FileImportWorkerCommand extends Command
{
    protected $signature = 'file-imports:consume';

    protected $description = 'Consome mensagens da fila de uploads e inicia o processamento.';

    public function __construct(
        private readonly ImportServiceInterface $importService,
        private readonly MongoRepositoryInterface $mongoRepository,
        private readonly QueuesServiceInterface $queues,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $queue = (string) data_get(config('services.rabbitmq'), 'queue', 'file-imports');
        $this->queues->declareQueue($queue);

        try {
            $consumed = $this->queues->consumeOne($queue);

            if ($consumed !== null) {
                $payload = $consumed['payload'] ?? [];
                $deliveryTag = $consumed['deliveryTag'] ?? null;

                if (!isset($payload['upload_id'])) {
                    $this->warn('Mensagem inválida recebida na fila.');
                    if (is_string($deliveryTag)) $this->queues->reject($deliveryTag, false);
                    return self::SUCCESS;
                }

                $uploadId = (int) $payload['upload_id'];
                $this->info("Processando upload {$uploadId}...");

                try {
                    $documents = $this->importService->handle($uploadId);
                    $this->info('documents: ' . count($documents));
                    if ($documents !== []) {
                        $this->mongoRepository->insertMany($documents);
                    } else {
                        $this->info('Nenhum documento gerado para este upload.');
                    }

                    if (is_string($deliveryTag)) $this->queues->ack($deliveryTag);
                } catch (Throwable $exception) {
                    $this->error($exception->getMessage());
                    if (is_string($deliveryTag)) $this->queues->reject($deliveryTag, true);
                    return self::FAILURE;
                }
            } else {
                $this->info('Nenhuma mensagem encontrada na fila.');
            }
        } finally {
            $this->info('Ciclo de consumo finalizado.');
        }

        return self::SUCCESS;
    }
}

