<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Queue;

class QueueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Queue::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'job_class' => 'App\\Jobs\\' . ucfirst($this->faker->word()) . 'Job',
            'state_code' => Queue::STATE_CODE_INIT,
        ];
    }
}
