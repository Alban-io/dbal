<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Mysqli;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Mysqli\Initializer\Charset;
use Doctrine\DBAL\Driver\Mysqli\Initializer\Options;
use Doctrine\DBAL\Driver\Mysqli\Initializer\Secure;

use function count;

final class Driver extends AbstractMySQLDriver
{
    /**
     * {@inheritdoc}
     *
     * @return MysqliConnection
     */
    public function connect(array $params): Connection
    {
        if (! empty($params['persistent'])) {
            if (! isset($params['host'])) {
                throw HostRequired::forPersistentConnection();
            }

            $host = 'p:' . $params['host'];
        } else {
            $host = $params['host'] ?? '';
        }

        $flags = 0;

        $preInitializers = $postInitializers = [];

        if (isset($params['driver_options'])) {
            $driverOptions = $params['driver_options'];

            if (isset($driverOptions[MysqliConnection::OPTION_FLAGS])) {
                $flags = $driverOptions[MysqliConnection::OPTION_FLAGS];
                unset($driverOptions[MysqliConnection::OPTION_FLAGS]);
            }

            $preInitializers = $this->withOptions($preInitializers, $driverOptions);
        }

        $preInitializers  = $this->withSecure($preInitializers, $params);
        $postInitializers = $this->withCharset($postInitializers, $params);

        return new MysqliConnection(
            $host,
            $params['user'] ?? '',
            $params['password'] ?? '',
            $params['dbname'] ?? '',
            $params['port'] ?? 0,
            $params['unix_socket'] ?? '',
            $flags,
            $preInitializers,
            $postInitializers
        );
    }

    /**
     * @param list<Initializer> $initializers
     * @param array<int,mixed>  $options
     *
     * @return list<Initializer>
     */
    private function withOptions(array $initializers, array $options): array
    {
        if (count($options) !== 0) {
            $initializers[] = new Options($options);
        }

        return $initializers;
    }

    /**
     * @param list<Initializer>   $initializers
     * @param array<string,mixed> $params
     *
     * @return list<Initializer>
     */
    private function withSecure(array $initializers, array $params): array
    {
        if (
            isset($params['ssl_key']) ||
            isset($params['ssl_cert']) ||
            isset($params['ssl_ca']) ||
            isset($params['ssl_capath']) ||
            isset($params['ssl_cipher'])
        ) {
            $initializers[] = new Secure(
                $params['ssl_key']    ?? null,
                $params['ssl_cert']   ?? null,
                $params['ssl_ca']     ?? null,
                $params['ssl_capath'] ?? null,
                $params['ssl_cipher'] ?? null
            );
        }

        return $initializers;
    }

    /**
     * @param list<Initializer>   $initializers
     * @param array<string,mixed> $params
     *
     * @return list<Initializer>
     */
    private function withCharset(array $initializers, array $params): array
    {
        if (isset($params['charset'])) {
            $initializers[] = new Charset($params['charset']);
        }

        return $initializers;
    }
}