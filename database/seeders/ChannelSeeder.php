<?php

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            ['name' => 'LinkedIn', 'slug' => 'linkedin'],
            ['name' => 'WhatsApp', 'slug' => 'whatsapp'],
            ['name' => 'E-mail', 'slug' => 'email'],
            ['name' => 'Telefone', 'slug' => 'telefone'],
            ['name' => 'Instagram', 'slug' => 'instagram'],
            ['name' => 'Facebook', 'slug' => 'facebook'],
            ['name' => 'Website', 'slug' => 'website'],
            ['name' => 'Landing Page', 'slug' => 'landing-page'],
            ['name' => 'Formulário', 'slug' => 'formulario'],
            ['name' => 'Presencial', 'slug' => 'presencial'],
            ['name' => 'Outro', 'slug' => 'outro'],
        ];

        foreach ($channels as $channel) {
            Channel::updateOrCreate(
                ['slug' => $channel['slug']],
                [
                    'name' => $channel['name'],
                    'active' => true,
                ],
            );
        }
    }
}
