<?php

namespace App\Services\Parsers;

interface FileParserInterface
{
    public function supports(string $extension): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $contents): array;
}

