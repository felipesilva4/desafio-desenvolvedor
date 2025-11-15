<?php

namespace Tests\Unit\Services\Parsers;

use App\Services\Parsers\FileParserInterface;
use App\Services\Parsers\FileParserResolver;
use Exception;
use Mockery;
use Tests\TestCase;

class FileParserResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testResolveReturnsMatchingParser(): void
    {
        $parser1 = Mockery::mock(FileParserInterface::class);
        $parser1->shouldReceive('supports')
            ->once()
            ->with('csv')
            ->andReturn(false);

        $parser2 = Mockery::mock(FileParserInterface::class);
        $parser2->shouldReceive('supports')
            ->once()
            ->with('csv')
            ->andReturn(true);

        $resolver = new FileParserResolver([$parser1, $parser2]);

        $result = $resolver->resolve('csv');

        $this->assertSame($parser2, $result);
    }

    public function testResolveThrowsExceptionWhenNoParserSupportsExtension(): void
    {
        $parser = Mockery::mock(FileParserInterface::class);
        $parser->shouldReceive('supports')
            ->once()
            ->with('unknown')
            ->andReturn(false);

        $resolver = new FileParserResolver([$parser]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nenhum parser disponível para a extensão "unknown".');

        $resolver->resolve('unknown');
    }

    public function testResolveNormalizesExtensionToLowercase(): void
    {
        $parser = Mockery::mock(FileParserInterface::class);
        $parser->shouldReceive('supports')
            ->once()
            ->with('xlsx')
            ->andReturn(true);

        $resolver = new FileParserResolver([$parser]);

        $result = $resolver->resolve('XLSX');

        $this->assertSame($parser, $result);
    }

    public function testResolveWithEmptyParsersThrowsException(): void
    {
        $resolver = new FileParserResolver([]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nenhum parser disponível para a extensão "csv".');

        $resolver->resolve('csv');
    }
}
