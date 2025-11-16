<?php

namespace App\Providers;

use App\Repositories\MongoRepository;
use App\Repositories\MongoRepositoryInterface;
use App\Repositories\UploadHistoricRepository;
use App\Repositories\UploadHistoricRepositoryInterface;
use App\Services\Contracts\FileImportServiceInterface;
use App\Services\Contracts\ImportServiceInterface;
use App\Services\Contracts\QueuesServiceInterface;
use App\Services\FileImportService;
use App\Services\ImportService;
use App\Services\Parsers\CsvFileParser;
use App\Services\Parsers\ExcelFileParser;
use App\Services\Parsers\FileParserResolver;
use App\Services\Parsers\FileParserResolverInterface;
use App\Services\QueuesService;
use Illuminate\Support\ServiceProvider;
use MongoDB\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UploadHistoricRepositoryInterface::class, UploadHistoricRepository::class);
        $this->app->singleton(Client::class, fn (): Client => new Client(config('mongo.uri')));
        $this->app->bind(MongoRepositoryInterface::class, MongoRepository::class);
        $this->app->bind(QueuesServiceInterface::class, QueuesService::class);
        $this->app->singleton(FileParserResolverInterface::class, function ($app) {
            return new FileParserResolver([
                $app->make(CsvFileParser::class),
                $app->make(ExcelFileParser::class),
            ]);
        });
        $this->app->bind(FileImportServiceInterface::class, FileImportService::class);
        $this->app->bind(ImportServiceInterface::class, ImportService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
