<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class RootOrganizationSeeder extends Seeder
{
    /**
     * Seed the Root organization. Idempotent.
     * Root is the system-level org for canonical courses; only global admins manage it.
     */
    public function run(): void
    {
        $root = Organization::firstOrCreate(
            ['slug' => 'root'],
            [
                'name' => 'Root',
                'description' => 'System-level organization for canonical courses',
                'is_active' => true,
                'access_mode' => 'restricted',
                'is_root' => true,
            ]
        );

        if (!$root->is_root) {
            $root->update(['is_root' => true]);
        }

        $this->command->info("Root organization: {$root->name} (id: {$root->id})");
    }
}
