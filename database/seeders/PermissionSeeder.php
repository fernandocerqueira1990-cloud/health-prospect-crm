<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::query()
            ->where('slug', 'imports.execute')
            ->delete();

        foreach (Permission::SLUGS as $slug) {
            Permission::updateOrCreate(['slug' => $slug], [
                'name' => Str::headline(str_replace('.', ' ', $slug)),
                'description' => 'Permite '.str_replace('.', ' ', $slug).'.',
            ]);
        }
    }
}
