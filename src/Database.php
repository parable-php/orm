<?php declare(strict_types=1);

namespace Parable\Orm;

use PDO;

class Database
{
    public const DATE_SQL = 'Y-m-d';
    public const TIME_SQL = 'H:i:s';
    public const DATETIME_SQL = self::DATE_SQL . ' ' . self::TIME_SQL;

    public const TYPE_MYSQL = 0;
    public const TYPE_SQLITE = 1;

    protected ?int $type = null;
    protected ?string $host = null;
    protected int $port = 3306;
    protected ?string $username = null;
    protected ?string $password = null;
    protected ?string $databaseName = null;
    protected ?string $charSet = null;
    protected int $errorMode = PDO::ERRMODE_SILENT;
    protected ?PDO $connection = null;
    protected int $queryCount = 0;
    protected ?string $lastQuery;

    /**
     * The connection class MUST extend PDO if not PDO itself
     */
    protected string $connectionClass = PDO::class;

    public function setConnectionClass(string $connectionClass): void
    {
        if (!is_subclass_of($connectionClass, PDO::class)) {
            throw new OrmException(sprintf(
                "Class %s does not extend PDO, which is required",
                $connectionClass
            ));
        }

        $this->connectionClass = $connectionClass;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setDatabaseName(string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }

    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    public function setCharSet(string $charSet): void
    {
        $this->charSet = $charSet;
    }

    public function getCharSet(): ?string
    {
        return $this->charSet;
    }

    public function setErrorMode(int $errorMode): void
    {
        if (!in_array(
            $errorMode,
            [PDO::ERRMODE_SILENT, PDO::ERRMODE_WARNING, PDO::ERRMODE_EXCEPTION],
            true
        )) {
            throw new OrmException(sprintf("Invalid error mode set: '%d'", $errorMode));
        }

        $this->errorMode = $errorMode;
    }

    public function getErrorMode(): int
    {
        return $this->errorMode;
    }

    public function isConnected(): bool
    {
        return $this->connection !== null;
    }

    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }

        $this->reconnect();
    }

    public function reconnect(): void
    {
        $this->disconnect();

        $this->connection = $this->createConnectionByType($this->type);
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }

    public function getConnection(): ?PDO
    {
        return $this->connection;
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function getLastQuery(): ?string
    {
        return $this->lastQuery;
    }

    protected function createConnectionByType(int $type): ?PDO
    {
        switch ($type) {
            case self::TYPE_MYSQL:
                return $this->createMySQLConnection();

            case self::TYPE_SQLITE:
                return $this->createSqliteConnection();
        }

        throw new OrmException(sprintf("Cannot create connection for invalid database type: '%d'", $type));
    }

    protected function createMySQLConnection(): PDO
    {
        if ($this->host === null) {
            throw new OrmException('MySQL requires a host.');
        }

        $connection = $this->createConnection(...$this->buildMySQLConnectionValues());

        $connection->setAttribute(PDO::ATTR_ERRMODE, $this->errorMode);

        return $connection;
    }

    protected function buildMySQLConnectionValues(): array
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s',
            $this->host,
            $this->port,
            $this->databaseName
        );

        if ($this->charSet !== null) {
            $dsn .= ';charset=' . $this->charSet;
        }

        return [$dsn, $this->username, $this->password];
    }

    protected function createSqliteConnection(): PDO
    {
        if ($this->databaseName === null) {
            throw new OrmException('Sqlite requires a database.');
        }

        if ($this->databaseName !== ':memory:' && !is_readable($this->databaseName)) {
            throw new OrmException(sprintf("Could not read Sqlite database: %s", $this->databaseName));
        }

        $dsn = sprintf('sqlite:%s', $this->databaseName);

        $connection = $this->createConnection($dsn);

        $connection->setAttribute(PDO::ATTR_ERRMODE, $this->errorMode);

        return $connection;
    }

    public function query(string $query): array
    {
        $this->lastQuery = $query;

        $this->connect();

        $pdoStatement = $this->connection->query($query, PDO::FETCH_ASSOC);

        if (!$pdoStatement) {
            throw new OrmException(sprintf(
                "Could not perform query: '%s', reason: %s: '%s'",
                $query,
                $this->connection->errorCode() ?? 'unknown',
                $this->connection->errorInfo()[2] ?? 'unknown'
            ));
        }

        $this->queryCount++;

        return $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function createConnection(...$parameters): PDO
    {
        $connectionClass = $this->connectionClass;

        return new $connectionClass(...$parameters);
    }

    /**
     * Prevent leaking password through print_r/var_dump.
     *
     * NOTE: var_export will output everything always. Keep this in mind.
     *
     * @see \Parable\Orm\Tests\DatabaseTest::testDebugInfoCannotPreventVarExport
     */
    public function __debugInfo(): array
    {
        $clone = clone $this;

        $clone->setPassword('****** (masked)');

        return (array)$clone;
    }
}
