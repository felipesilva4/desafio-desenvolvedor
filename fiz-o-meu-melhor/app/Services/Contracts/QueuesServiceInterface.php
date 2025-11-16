<?php

namespace App\Services\Contracts;

interface QueuesServiceInterface
{
    public function dispatchToDefault(array $payload): void;

    public function publish(string $queue, array $payload): void;

    /**
     * Declara a fila (idempotente).
     */
    public function declareQueue(string $queue): void;

    /**
     * Consome uma única mensagem da fila ou retorna null se não houver.
     * Retorna um array com 'payload' (array) e 'deliveryTag' (string|null).
     *
     * @return array{payload: array<string,mixed>, deliveryTag: string|null}|null
     */
    public function consumeOne(string $queue): ?array;

    /**
     * Dá ACK na mensagem processada com sucesso.
     */
    public function ack(string $deliveryTag): void;

    /**
     * Rejeita a mensagem com opção de reenfileirar.
     */
    public function reject(string $deliveryTag, bool $requeue = true): void;
}

