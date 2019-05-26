<?php declare(strict_types=1);

namespace Parable\Orm;

use Parable\Orm\Database\MySQLConnection;
use Parable\Orm\Database\SqliteConnection;
use PDO;

class Database
{
    public const DATETIME_SQL = 'Y-m-d H:i:s';

    public const TYPE_MYSQL = 0;
    public const TYPE_SQLITE = 1;

    /**
     * @var int|null
     */
    protected $type;

    /**
     * @var string|null
     */
    protected $host;

    /**
     * @var int
     */
    protected $port = 3306;

    /**
     * @var string|null
     */
    protected $username;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * @var string|null
     */
    protected $databaseName;

    /**
     * @var string|null
     */
    protected $charSet;
    /**
     * @var int
     */
    protected $errorMode = PDO::ERRMODE_SILENT;

    /**
     * @var PDO|null
     */
    protected $connection;

    /**
     * @var int
     */
    protected $queryCount = 0;

    /**
     * @var string|null
     */
    protected $lastQuery;

    public function setType(int $type): void
    {
        if (!in_array($type, [self::TYPE_MYSQL, self::TYPE_SQLITE])) {
            throw new Exception(sprintf("Invalid database type: '%d'", $type));
        }

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
        if (!in_array($errorMode, [PDO::ERRMODE_SILENT, PDO::ERRMODE_WARNING, PDO::ERRMODE_EXCEPTION])) {
            throw new Exception(sprintf("Invalid error mode set: '%d'", $errorMode));
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

        switch ($this->type) {
            case self::TYPE_MYSQL:
                $this->connection = $this->createMySQLConnection();
                break;

            case self::TYPE_SQLITE:
                $this->connection = $this->createSqliteConnection();
                break;
        }
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

    protected function createMySQLConnection(): MySQLConnection
    {
        if ($this->host === null) {
            throw new Exception('MySQL requires a host.');
        }

        if ($this->databaseName === null) {
            throw new Exception('MySQL requires a database name.');
        }

        $connection = new MySQLConnection(...$this->buildMySQLConnectionValues());

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

    protected function createSqliteConnection(): SqliteConnection
    {
        if ($this->databaseName === null) {
            throw new Exception('Sqlite requires a database.');
        }
        if (!is_readable($this->databaseName) && $this->databaseName !== ':memory:') {
            throw new Exception(sprintf("Could not read Sqlite database: %s", $this->databaseName));
        }

        $dsn = sprintf('sqlite:%s', $this->databaseName);

        $connection = new SqliteConnection($dsn);

        $connection->setAttribute(PDO::ATTR_ERRMODE, $this->errorMode);

        return $connection;
    }

    public function query(string $query): array
    {
        $this->connect();

        $pdoStatement = $this->connection->query($query, PDO::FETCH_ASSOC);

        if (!$pdoStatement) {
            throw new Exception(sprintf(
                "Could not perform query: '%s', reason: %s: '%s'",
                $query,
                $this->connection->errorCode() ?? 'unknown',
                $this->connection->errorInfo()[2] ?? 'unknown'
            ));
        }

        $this->queryCount++;
        $this->lastQuery = $query;

        return $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Prevent leaking password through print_r/var_dump.
     *
     * NOTE: var_export will output everything always. Keep this in mind.
     *
     * @see \Parable\Orm\Tests\DatabaseTest::testDebugInfoCannotPreventVarExport
     */
    public function __debugInfo()
    {
        $clone = clone $this;

        $clone->setPassword('****** (masked)');

        return (array)$clone;
    }
}
