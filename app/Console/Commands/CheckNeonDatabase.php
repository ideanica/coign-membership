<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckNeonDatabase extends Command
{
    protected $signature = 'neon:check {--schema : Show public table and column names without reading member records}';

    protected $description = 'Verify the PostgreSQL connection and SSL without changing database contents';

    public function handle(): int
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'pgsql') {
            $this->error('Set DB_CONNECTION=pgsql and DB_URL in .env first.');

            return self::FAILURE;
        }

        try {
            $database = $connection->selectOne('select current_database() as name');
            $ssl = $connection->selectOne('select ssl, version from pg_stat_ssl where pid = pg_backend_pid()');

            $this->info('Connected to PostgreSQL database: '.$database->name);
            $this->line('Connection name: '.$connection->getName());
            $this->line('Client SSL mode: '.$connection->getConfig('sslmode'));
            $this->line('PostgreSQL backend SSL: '.($ssl?->ssl ? 'active ('.$ssl->version.')' : 'not reported as active (proxy/backend telemetry; not client TLS status)'));
            $this->line('Channel binding setting: '.$connection->getConfig('channel_binding'));

            if ($this->option('schema')) {
                $columns = $connection->select("select table_name, column_name, data_type from information_schema.columns where table_schema = 'public' order by table_name, ordinal_position");

                $this->table(['Table', 'Column', 'Type'], array_map(
                    fn (object $column): array => [$column->table_name, $column->column_name, $column->data_type],
                    $columns,
                ));

                if ($columns === []) {
                    $this->line('No public tables found.');
                }
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $secrets = array_filter([
                config('database.connections.pgsql.url'),
                $connection->getConfig('password'),
            ]);

            $this->error('Connection failed: '.str_replace($secrets, '[redacted]', $exception->getMessage()));

            return self::FAILURE;
        }
    }
}
