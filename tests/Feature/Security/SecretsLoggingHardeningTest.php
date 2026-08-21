<?php

namespace Tests\Feature\Security;

use App\Logging\SanitizeLogRecords;
use App\Models\AuditLog;
use App\Services\AuditService;
use App\Support\LogSanitizer;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use RuntimeException;
use Tests\TestCase;

class SecretsLoggingHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_processor_redacts_secrets_payloads_and_log_injection(): void
    {
        $handler = new TestHandler;
        $logger = new Logger(new MonologLogger('security-test', [$handler]));
        (new SanitizeLogRecords(new LogSanitizer))($logger);

        $logger->error("Attempt\r\nFORGED password=visible Bearer raw-token JSON={\"password\":\"json forbidden with spaces\"} secret='quoted forbidden with spaces'", [
            'Authorization' => 'Bearer forbidden',
            'Cookie' => 'laravel_session=session-forbidden',
            'session_id' => 'session-id-forbidden',
            'api_key' => 'api-key-forbidden',
            'accessToken' => 'camel-access-forbidden',
            'clientSecret' => 'camel-secret-forbidden',
            'sessionId' => 'camel-session-forbidden',
            'apiKey' => 'camel-api-forbidden',
            'original_data' => ['company' => 'Commercial payload forbidden'],
            'exception' => new RuntimeException("DB_PASSWORD=database-forbidden\nforged"),
        ]);

        $record = $handler->getRecords()[0];
        $serialized = json_encode([$record->message, $record->context], JSON_THROW_ON_ERROR);

        foreach (['visible', 'raw-token', 'forbidden', 'json forbidden with spaces', 'quoted forbidden with spaces', 'Commercial payload forbidden', "\n", "\r"] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $serialized);
        }

        $this->assertStringContainsString('[REDACTED]', $serialized);
        $this->assertStringContainsString(RuntimeException::class, $serialized);
        $this->assertStringNotContainsString(base_path(), $serialized);
    }

    public function test_audit_metadata_neutralizes_control_characters_and_omits_import_payloads(): void
    {
        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '192.0.2.20',
            'HTTP_USER_AGENT' => "Browser\r\nFORGED event",
        ]);

        app(AuditService::class)->record('safe_action', after: [
            'original_data' => ['name' => 'Commercial payload forbidden'],
            'dedup_data' => ['token' => 'forbidden'],
            'execution_data' => ['password' => 'forbidden'],
        ], request: $request);

        $audit = AuditLog::query()->sole();
        $this->assertSame([], $audit->after);
        $this->assertSame('Browser FORGED event', $audit->user_agent);
    }

    public function test_production_error_response_does_not_expose_exception_details(): void
    {
        config(['app.debug' => false, 'app.env' => 'production']);
        $exception = new RuntimeException(
            'SQLSTATE credential=database-forbidden path=/srv/private/app.php',
        );
        $request = Request::create('/api/_security/production-error', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
        $response = app(ExceptionHandler::class)->render($request, $exception);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['message' => 'Server Error'], json_decode((string) $response->getContent(), true));
        $this->assertStringNotContainsString('database-forbidden', (string) $response->getContent());
        $this->assertStringNotContainsString('/srv/private/app.php', (string) $response->getContent());
        $this->assertStringNotContainsString('SQLSTATE', (string) $response->getContent());
    }

    public function test_production_logging_recommendations_are_safe_and_rotated(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        $this->assertIsString($example);
        $this->assertStringContainsString('# APP_DEBUG=false', $example);
        $this->assertStringContainsString('# LOG_STACK=daily', $example);
        $this->assertStringContainsString('# LOG_LEVEL=warning', $example);
        $this->assertStringContainsString('LOG_DAILY_DAYS=14', $example);
        $loggingConfig = file_get_contents(config_path('logging.php'));
        $this->assertIsString($loggingConfig);
        $this->assertStringContainsString("env('LOG_STACK', 'daily')", $loggingConfig);
    }
}
