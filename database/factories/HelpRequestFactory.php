<?php

namespace Database\Factories;

use App\Models\HelpRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HelpRequest>
 */
class HelpRequestFactory extends Factory
{
    protected $model = HelpRequest::class;

    /**
     * Sample titles per category (English).
     */
    protected static array $sampleTitles = [
        'products' => [
            'Need help with grocery shopping',
            'Cannot get to the store',
            'Looking for someone to buy groceries',
            'Need items from the supermarket',
            'Need bread and milk delivery',
        ],
        'medicine' => [
            'Need medicine from pharmacy',
            'Looking for ibuprofen, local pharmacies out of stock',
            'Need help picking up prescription',
            'Can someone deliver medicine to my grandmother?',
        ],
        'transport' => [
            'Need a ride to the hospital',
            'Looking for carpool to downtown',
            'Need help moving some items',
            'Anyone going to the airport?',
        ],
        'other' => [
            'Need help with house cleaning',
            'Looking for someone to walk my dog',
            'Need help with computer setup',
            'Can anyone help with minor repairs?',
            'Need to borrow a stroller for a week',
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = $this->faker->randomElement(['products', 'medicine', 'transport', 'other']);
        $titles = static::$sampleTitles[$category] ?? static::$sampleTitles['other'];

        // Coordinates around Kyiv center (±0.05 degrees ≈ ~5km)
        $baseLat = 50.45;
        $baseLng = 30.52;

        $contactType = $this->faker->randomElement(['email', 'phone', 'telegram']);
        $contactValue = match ($contactType) {
            'email'    => $this->faker->safeEmail(),
            'phone'    => '+380' . $this->faker->numerify('#########'),
            'telegram' => '@' . $this->faker->userName(),
        };

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->randomElement($titles),
            'description' => $this->faker->optional(0.7)->sentence(10),
            'category' => $category,
            'contact_type' => $contactType,
            'contact_value' => $contactValue,
            'latitude' => $baseLat + ($this->faker->randomFloat(5, -0.05, 0.05)),
            'longitude' => $baseLng + ($this->faker->randomFloat(5, -0.05, 0.05)),
            'status' => $this->faker->randomElement(['open', 'open', 'open', 'in_progress']),
            'expires_at' => now()->addHours($this->faker->randomElement([1, 6, 12, 24, 48, 72, 168])),
        ];
    }

    /**
     * Indicate that this request is expired.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subHours(2),
            'status' => 'open',
        ]);
    }

    /**
     * Indicate that this request is fulfilled.
     */
    public function fulfilled(): static
    {
        return $this->state(fn () => [
            'status' => 'fulfilled',
        ]);
    }
}
