<?php

declare(strict_types=1);

namespace BEAR\RateLimit\Throttle;

use Override;
use PDO;
use Psr\Clock\ClockInterface;

final class SqliteThrottle implements ThrottleInterface
{
    private const CREATE_TABLE = 'CREATE TABLE IF NOT EXISTS throttle ('
        . 'throttle_key TEXT PRIMARY KEY, attempts INTEGER NOT NULL, expires_at INTEGER NOT NULL)';
    private const SELECT_ATTEMPTS = 'SELECT attempts FROM throttle WHERE throttle_key = :key AND expires_at > :now';
    private const COUNT_UP = 'INSERT INTO throttle (throttle_key, attempts, expires_at) VALUES (:key, 1, :expires_at) '
        . 'ON CONFLICT(throttle_key) DO UPDATE SET '
        . 'attempts = CASE WHEN expires_at > :now THEN attempts + 1 ELSE 1 END, '
        . 'expires_at = CASE WHEN expires_at > :now THEN expires_at ELSE excluded.expires_at END';

    private PDO|null $pdo = null;

    public function __construct(
        private readonly SqliteDsn $dsn,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Override]
    public function isExceeded(string $key, int $limit): bool
    {
        $statement = $this->pdo()->prepare(self::SELECT_ATTEMPTS);
        $statement->execute(['key' => $key, 'now' => $this->clock->now()->getTimestamp()]);
        /** @var int|false $attempts */
        $attempts = $statement->fetchColumn();

        return $attempts !== false && $attempts >= $limit;
    }

    #[Override]
    public function countUp(string $key, string $interval): void
    {
        $now = $this->clock->now();
        $statement = $this->pdo()->prepare(self::COUNT_UP);
        $statement->execute([
            'key' => $key,
            'expires_at' => $now->modify('+' . $interval)->getTimestamp(),
            'now' => $now->getTimestamp(),
        ]);
    }

    private function pdo(): PDO
    {
        return $this->pdo ??= $this->open();
    }

    private function open(): PDO
    {
        $pdo = new PDO($this->dsn->dsn, options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec(self::CREATE_TABLE);

        return $pdo;
    }
}
