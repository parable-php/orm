<?php declare(strict_types=1);

namespace Parable\Orm;

use Throwable;

class Transaction
{
    /** @var Database */
    private $database;

    /** @var bool */
    private $inTransaction = false;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function begin(): void
    {
        if ($this->inTransaction === true) {
            throw new Exception('Cannot start a transaction within a transaction');
        }

        if ($this->database->getType() === Database::TYPE_MYSQL) {
            $this->database->query('START TRANSACTION');
        } elseif ($this->database->getType() === Database::TYPE_SQLITE) {
            $this->database->query('BEGIN TRANSACTION');
        } else {
            throw new Exception('Cannot start transaction for database type ' . $this->database->getType());
        }

        $this->inTransaction = true;
    }

    public function commit(): void
    {
        if ($this->inTransaction === false) {
            throw new Exception('Cannot commit while not in a transaction');
        }

        $this->database->query('COMMIT');

        $this->inTransaction = false;
    }

    public function rollback(): void
    {
        if ($this->inTransaction === false) {
            throw new Exception('Cannot rollback while not in a transaction');
        }

        $this->database->query('ROLLBACK');

        $this->inTransaction = false;
    }

    public function withTransaction(callable $callable)
    {
        $this->begin();

        try {
            $returnValue = $callable();
        } catch (Throwable $exception) {
            $this->rollback();

            throw new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->commit();

        return $returnValue;
    }

    public function __destruct()
    {
        if ($this->inTransaction === true) {
            $this->rollback();
        }
    }
}
