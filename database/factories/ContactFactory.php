<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name'    => $this->faker->name(),
            'phone'   => '+2010' . $this->faker->unique()->numerify('########'),
        ];
    }
}
