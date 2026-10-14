<?php

namespace Tests\Unit;

use App\Database\NeonPostgresConnector;
use Illuminate\Support\ConfigurationUrlParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class NeonPostgresConnectorTest extends TestCase
{
    public function test_neon_url_keeps_ssl_and_channel_binding_in_the_pdo_dsn(): void
    {
        $config = (new ConfigurationUrlParser)->parseConfiguration([
            'url' => 'postgresql://example:example@example.invalid/neondb?sslmode=require&channel_binding=require',
            'port' => '5432',
            'charset' => 'utf8',
            'sslmode' => 'prefer',
            'channel_binding' => 'prefer',
            'connect_timeout' => 10,
        ]);

        $dsn = (new ReflectionMethod(NeonPostgresConnector::class, 'getDsn'))->invoke(new NeonPostgresConnector, $config);

        $this->assertSame('pgsql', $config['driver']);
        $this->assertStringContainsString('host=example.invalid', $dsn);
        $this->assertStringContainsString("dbname='neondb'", $dsn);
        $this->assertStringContainsString(';port=5432', $dsn);
        $this->assertStringContainsString(';sslmode=require', $dsn);
        $this->assertStringContainsString(';channel_binding=require', $dsn);
        $this->assertStringContainsString(';connect_timeout=10', $dsn);
        $this->assertStringNotContainsString('password', $dsn);
    }

    public function test_an_invalid_channel_binding_option_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ReflectionMethod(NeonPostgresConnector::class, 'getDsn'))->invoke(new NeonPostgresConnector, [
            'host' => 'example.invalid',
            'database' => 'neondb',
            'channel_binding' => 'require;sslmode=disable',
        ]);
    }
}
