<?php

namespace Database\Seeders;

use App\Models\HelpRequest;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create demo users
        $users = User::factory(5)->create();

        // Create a known test user
        $testUser = User::factory()->create([
            'google_id' => '123456789',
            'name' => 'Demo User',
            'email' => 'demo@localhelp.test',
        ]);

        // Create 30 help requests spread across users
        foreach ($users as $user) {
            HelpRequest::factory(rand(3, 5))->create([
                'user_id' => $user->id,
            ]);
        }

        // Add a few requests for the demo user
        HelpRequest::factory(3)->create([
            'user_id' => $testUser->id,
        ]);

        // Add a couple of fulfilled requests
        HelpRequest::factory(2)->fulfilled()->create([
            'user_id' => $users->random()->id,
        ]);

        // Add a couple of expired requests (won't show in active queries)
        HelpRequest::factory(2)->expired()->create([
            'user_id' => $users->random()->id,
        ]);
    }
}
