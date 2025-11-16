<?php

namespace App\Exceptions;

use RuntimeException;

class FileAlreadyImportedException extends RuntimeException
{
    public function __construct(string $message = 'O arquivo já foi enviado anteriormente.')
    {
        parent::__construct($message);
    }
}

