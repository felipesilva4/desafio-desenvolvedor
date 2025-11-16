<?php

namespace App\Services\Parsers;

interface FileParserResolverInterface
{
    public function resolve(string $extension): FileParserInterface;
}

