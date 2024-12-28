<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\EntryItem;
use App\Models\Ledger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EntryItem>
 */
class EntryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = EntryItem::class;
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid,
            'entry_id' => Entry::factory(),
            'ledger_id' => Ledger::factory(),
            'amount' => $this->faker->randomFloat(2, 0, 1000),
            'narration' => $this->faker->sentence,
            'dc' => $this->faker->randomElement(['D', 'C']),
            'reconciliation_date' => $this->faker->optional()->date,
        ];
    }
}
