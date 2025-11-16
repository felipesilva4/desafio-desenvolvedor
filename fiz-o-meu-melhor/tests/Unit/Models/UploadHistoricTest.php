<?php

namespace Tests\Unit\Models;

use App\Models\UploadHistoric;
use Tests\TestCase;

class UploadHistoricTest extends TestCase
{
    public function testStatusConstants(): void
    {
        $this->assertEquals('WAITING', UploadHistoric::STATUS_WAITING);
        $this->assertEquals('PROCESSING', UploadHistoric::STATUS_PROCESSING);
        $this->assertEquals('PROCESSED', UploadHistoric::STATUS_PROCESSED);
        $this->assertEquals('ERROR', UploadHistoric::STATUS_ERROR);
    }

    public function testStatusesArrayContainsAllConstants(): void
    {
        $expectedStatuses = [
            UploadHistoric::STATUS_WAITING,
            UploadHistoric::STATUS_PROCESSING,
            UploadHistoric::STATUS_PROCESSED,
            UploadHistoric::STATUS_ERROR,
        ];

        $this->assertEquals($expectedStatuses, UploadHistoric::STATUSES);
    }

    public function testFillableAttributes(): void
    {
        $model = new UploadHistoric();

        $fillable = $model->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('hash', $fillable);
        $this->assertContains('reference_date', $fillable);
        $this->assertContains('status', $fillable);
    }

    public function testCasts(): void
    {
        $model = new UploadHistoric();

        $casts = $model->getCasts();

        $this->assertEquals('date', $casts['reference_date']);
    }

    public function testUsesHasFactoryTrait(): void
    {
        $model = new UploadHistoric();

        $traits = class_uses($model);
        $this->assertArrayHasKey('Illuminate\Database\Eloquent\Factories\HasFactory', $traits);
    }
}
