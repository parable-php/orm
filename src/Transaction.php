<?php declare(strict_types=1);

namespace Parable\Orm;

use Throwable;

class Transaction
{
    protected bool $inTransaction = false;

    public function __construct(
        protected Database $database
    ) {
    }

    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function begin(): void
    {
        if ($this->inTransaction === true) {
            throw new OrmException('Cannot start a transaction within a transaction');
        }

        if ($this->database->getType() === Database::TYPE_MYSQL) {
            $this->database->query('START TRANSACTION');
        } elseif ($this->database->getType() === Database::TYPE_SQLITE) {
            $this->database->query('BEGIN TRANSACTION');
        } else {
            throw new OrmException('Cannot start transaction for database type ' . $this->database->getType());
        }

        $this->inTransaction = true;
    }

    public function commit(): void
    {
        if ($this->inTransaction === false) {
            throw new OrmException('Cannot commit while not in a transaction');
        }

        $this->database->query('COMMIT');

        $this->inTransaction = false;
    }

    public function rollback(): void
    {
        if ($this->inTransaction === false) {
            throw new OrmException('Cannot rollback while not in a transaction');
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

            throw new OrmException($exception->getMessage(), (int)$exception->getCode(), $exception);
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
