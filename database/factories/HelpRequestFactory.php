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
     * Sample titles per category (Ukrainian-focused for demo).
     */
    protected static array $sampleTitles = [
        'products' => [
            'Потрібна допомога з покупками',
            'Не можу дістатися до магазину',
            'Допоможіть купити продукти',
            'Потрібні продукти з АТБ',
            'Шукаю хто може купити хліб та молоко',
        ],
        'medicine' => [
            'Потрібні ліки з аптеки',
            'Шукаю ібупрофен, в аптеках поруч немає',
            'Потрібна допомога забрати рецепт',
            'Хто може занести ліки бабусі?',
        ],
        'transport' => [
            'Потрібно підвезти до лікарні',
            'Шукаю попутку в центр',
            'Потрібна допомога з перевезенням речей',
            'Хто їде до Борисполя?',
        ],
        'other' => [
            'Потрібна допомога з прибиранням',
            'Шукаю хто може вигуляти собаку',
            'Потрібна допомога з технікою',
            'Хто може допомогти з ремонтом?',
            'Потрібен дитячий візок на тиждень',
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

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->randomElement($titles),
            'description' => $this->faker->optional(0.7)->sentence(10),
            'category' => $category,
            'contact_type' => $this->faker->randomElement(['email', 'phone', 'telegram']),
            'contact_value' => $this->faker->randomElement([
                '+380' . $this->faker->numerify('#########'),
                '@' . $this->faker->userName(),
                $this->faker->safeEmail(),
            ]),
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
