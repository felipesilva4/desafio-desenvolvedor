<?php

namespace Database\Factories;

use App\Models\UploadHistoric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UploadHistoric>
 */
class UploadHistoricFactory extends Factory
{
    protected $model = UploadHistoric::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'hash' => hash('sha256', $this->faker->unique()->uuid()),
            'reference_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(UploadHistoric::STATUSES),
        ];
    }
}

