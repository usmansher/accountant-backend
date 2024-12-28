<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\EntryType;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entry>
 */
class EntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Entry::class;
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'tag_id' => Tag::factory(),
            'entrytype_id' => EntryType::factory(),
            'number' => $this->faker->numberBetween(1, 100),
            'date' => $this->faker->date,
            'dr_total' => $this->faker->randomFloat(2, 0, 10000),
            'cr_total' => $this->faker->randomFloat(2, 0, 10000),
            'narration' => $this->faker->sentence,
        ];
    }
}
