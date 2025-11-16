<?php

namespace App\Services\Contracts;

interface ImportServiceInterface
{
    /**
     * Processa o upload e retorna os documentos importados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function handle(int $uploadId): array;
}

