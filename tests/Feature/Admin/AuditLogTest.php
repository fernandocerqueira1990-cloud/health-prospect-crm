<?php

namespace Tests\Feature\Admin;

use App\Actions\Users\AssignUserRolesAction;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_login_creates_audit_event(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'login_success', 'user_id' => $user->id]);
    }

    public function test_user_creation_and_update_create_events(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Auditada', 'email' => 'auditada@example.com', 'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123', 'active' => true, 'role_ids' => [],
        ]);
        $user = User::where('email', 'auditada@example.com')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Auditada Dois', 'email' => $user->email, 'active' => true, 'role_ids' => [],
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'user_created', 'auditable_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user_updated', 'auditable_id' => $user->id]);
    }

    public function test_role_change_creates_event(): void
    {
        $user = User::factory()->create();
        $role = Role::where('slug', 'sales_rep')->firstOrFail();

        app(AssignUserRolesAction::class)->execute($user, [$role->id]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'user_role_attached', 'auditable_id' => $user->id]);
    }

    public function test_sensitive_values_are_removed_recursively(): void
    {
        app(AuditService::class)->record('security_test', before: [
            'name' => 'Safe', 'password' => 'plain', 'nested' => ['token' => 'secret', 'value' => 'kept'],
        ], after: ['password_confirmation' => 'plain']);

        $log = AuditLog::where('action', 'security_test')->firstOrFail();
        $serialized = json_encode([$log->before, $log->after]);
        $this->assertStringNotContainsString('plain', $serialized);
        $this->assertStringNotContainsString('secret', $serialized);
        $this->assertStringContainsString('kept', $serialized);
    }

    public function test_user_without_permission_cannot_view_audit_log(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.audit.index'))->assertForbidden();
    }
}
