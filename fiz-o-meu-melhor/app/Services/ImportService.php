<?php

namespace App\Services;

use App\Repositories\UploadHistoricRepositoryInterface;
use App\Services\Contracts\ImportServiceInterface;
use App\Services\Parsers\FileParserResolverInterface;
use Exception;

class ImportService implements ImportServiceInterface
{
    public function __construct(
        private readonly UploadHistoricRepositoryInterface $repository,
        private readonly FileParserResolverInterface $parserResolver,
    ) {
    }

    public function handle(int $uploadId): array
    {
        $upload = $this->repository->findById($uploadId);

        if ($upload === null) {
            throw new Exception('Upload não encontrado para importação.');
        }

        $storage = $upload->storage;
        $decoded = base64_decode($storage?->file_path ?? '', true);

        if ($decoded === false) {
            throw new Exception('Falha ao decodificar arquivo durante importação.');
        }

        $extension = strtolower(pathinfo((string) $upload->name, PATHINFO_EXTENSION));

        if ($extension === '') {
            throw new Exception('Não foi possível identificar o tipo do arquivo.');
        }

        $parser = $this->parserResolver->resolve($extension);
        $documents = $parser->parse($decoded);

        $uploadReference = $upload->hash ?? (string) $upload->id;

        foreach ($documents as &$document) {
            $document = $this->ensureUtf8($document);
            $document['upload_id'] = $uploadReference;
        }
        unset($document);

        return $documents;
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function ensureUtf8(array $document): array
    {
        foreach ($document as $key => $value) {
            if (is_string($value)) {
                $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true) ?: 'UTF-8';
                $document[$key] = mb_convert_encoding($value, 'UTF-8', $encoding);
            }
        }

        return $document;
    }
}
