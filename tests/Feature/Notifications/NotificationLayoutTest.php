<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\CommercialAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_notification_count_is_visible_in_authenticated_layout(): void
    {
        $user = User::factory()->create();

        $user->notify(new CommercialAlertNotification([
            'key' => 'test:layout:1',
            'type' => 'test',
            'title' => 'Alerta de teste',
            'message' => 'Conteúdo do alerta',
            'severity' => 'warning',
            'url' => route('notifications.index'),
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]));

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Notificações');
        $response->assertSee('1');
    }
}
