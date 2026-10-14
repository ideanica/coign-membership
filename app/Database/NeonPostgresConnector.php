<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;
use InvalidArgumentException;

class NeonPostgresConnector extends PostgresConnector
{
    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);
        $channelBinding = $config['channel_binding'] ?? 'prefer';

        if (! in_array($channelBinding, ['disable', 'prefer', 'require'], true)) {
            throw new InvalidArgumentException('Invalid PostgreSQL channel binding setting.');
        }

        return $dsn.';channel_binding='.$channelBinding.';connect_timeout='.max(1, (int) ($config['connect_timeout'] ?? 10));
    }
}
