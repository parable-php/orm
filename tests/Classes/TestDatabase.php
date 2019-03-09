<?php declare(strict_types=1);

namespace Parable\Orm\Tests\Classes;

use Parable\Orm\Database;

class TestDatabase extends Database
{
    public function getConnectionWithoutTypeCheck()
    {
        return $this->connection;
    }

    public function buildMySQLConnectionValuesPublic(): array
    {
        return parent::buildMySQLConnectionValues();
    }

    public function resetQueryCount(): void
    {
        $this->queryCount = 0;
    }

    /**
     * @return int|null
     */
    public function getTestType(): ?int
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getTestHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getTestPort(): int
    {
        return $this->port;
    }

    /**
     * @return string|null
     */
    public function getTestUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @return string|null
     */
    public function getTestPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getTestDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    /**
     * @return string|null
     */
    public function getTestCharSet(): ?string
    {
        return $this->charSet;
    }

    /**
     * @return int
     */
    public function getTestErrorMode(): int
    {
        return $this->errorMode;
    }

    /**
     * @return bool
     */
    public function isSoftQuotingEnabled(): bool
    {
        return $this->softQuotingEnabled;
    }

    public function setFakeConnection(): void
    {
        $this->connection = 'fake';
    }
}
