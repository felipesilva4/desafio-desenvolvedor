<?php

namespace Database\Factories;

use App\Models\IssoDeviaSerUmS3;
use App\Models\UploadHistoric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssoDeviaSerUmS3>
 */
class IssoDeviaSerUmS3Factory extends Factory
{
    protected $model = IssoDeviaSerUmS3::class;

    public function definition(): array
    {
        return [
            'upload_historic_id' => UploadHistoric::factory(),
            'file_path' => base64_encode($this->faker->sentence()),
        ];
    }
}


