<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\FileAlreadyImportedException;
use RuntimeException;
use Tests\TestCase;

class FileAlreadyImportedExceptionTest extends TestCase
{
    public function testIsRuntimeException(): void
    {
        $exception = new FileAlreadyImportedException('Arquivo já importado');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testMessageIsPreserved(): void
    {
        $message = 'Arquivo já foi processado anteriormente';
        $exception = new FileAlreadyImportedException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testDefaultMessage(): void
    {
        $exception = new FileAlreadyImportedException();

        $this->assertSame('O arquivo já foi enviado anteriormente.', $exception->getMessage());
    }
}
