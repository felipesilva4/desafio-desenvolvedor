<?php

namespace App\Services\Parsers;

use Exception;

class FileParserResolver implements FileParserResolverInterface
{
    /**
     * @param iterable<FileParserInterface> $parsers
     */
    public function __construct(private readonly iterable $parsers)
    {
    }

    public function resolve(string $extension): FileParserInterface
    {
        $extension = strtolower($extension);

        foreach ($this->parsers as $parser) {
            if ($parser->supports($extension)) {
                return $parser;
            }
        }

        throw new Exception(sprintf('Nenhum parser disponível para a extensão "%s".', $extension));
    }
}

