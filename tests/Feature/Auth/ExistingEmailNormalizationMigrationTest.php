<?php

namespace Tests\Feature\Auth;

use App\Actions\Users\NormalizeExistingUserEmailsAction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ExistingEmailNormalizationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_lowercases_existing_mixed_case_email(): void
    {
        $this->dropCanonicalConstraint();
        $userId = $this->insertLegacyUser('User@Example.com');

        app(NormalizeExistingUserEmailsAction::class)->execute();

        $this->assertDatabaseHas('users', ['id' => $userId, 'email' => 'user@example.com']);
    }

    public function test_migration_trims_existing_email(): void
    {
        $this->dropCanonicalConstraint();
        $userId = $this->insertLegacyUser('  spaced@example.com  ');

        app(NormalizeExistingUserEmailsAction::class)->execute();

        $this->assertDatabaseHas('users', ['id' => $userId, 'email' => 'spaced@example.com']);
    }

    public function test_preexisting_mixed_case_user_can_login_after_migration(): void
    {
        $this->dropCanonicalConstraint();
        $userId = $this->insertLegacyUser('Legacy@Example.com');
        app(NormalizeExistingUserEmailsAction::class)->execute();

        $this->post('/login', [
            'email' => 'LEGACY@example.COM',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs(User::findOrFail($userId));
    }

    public function test_canonical_collision_aborts_without_modifying_or_deleting_users(): void
    {
        $this->dropCanonicalConstraint();
        $firstId = $this->insertLegacyUser('Collision@Example.com');
        $secondId = $this->insertLegacyUser(' collision@example.com ');

        try {
            app(NormalizeExistingUserEmailsAction::class)->execute();
            $this->fail('A colisão canônica deveria abortar a normalização.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('collision@example.com', $exception->getMessage());
            $this->assertStringContainsString('Resolva manualmente', $exception->getMessage());
        }

        $this->assertSame(2, User::whereKey([$firstId, $secondId])->count());
        $this->assertDatabaseHas('users', ['id' => $firstId, 'email' => 'Collision@Example.com']);
        $this->assertDatabaseHas('users', ['id' => $secondId, 'email' => ' collision@example.com ']);
    }

    public function test_existing_unique_constraint_still_rejects_duplicate_canonical_email(): void
    {
        DB::table('users')->insert($this->legacyUserData('unique@example.com'));

        $this->expectException(QueryException::class);

        DB::table('users')->insert($this->legacyUserData('unique@example.com'));
    }

    public function test_database_constraint_rejects_direct_noncanonical_email(): void
    {
        $this->expectException(QueryException::class);

        DB::table('users')->insert($this->legacyUserData('NotCanonical@Example.com'));
    }

    private function dropCanonicalConstraint(): void
    {
        DB::statement(sprintf(
            'ALTER TABLE users DROP CONSTRAINT %s',
            NormalizeExistingUserEmailsAction::CONSTRAINT,
        ));
    }

    private function insertLegacyUser(string $email): int
    {
        return (int) DB::table('users')->insertGetId($this->legacyUserData($email));
    }

    /** @return array<string, mixed> */
    private function legacyUserData(string $email): array
    {
        return [
            'name' => 'Legacy User',
            'email' => $email,
            'password' => Hash::make('password'),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
