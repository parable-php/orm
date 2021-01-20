<?php declare(strict_types=1);

namespace Parable\Orm\Tests;

use LogicException;
use Parable\Orm\Database;
use Parable\Orm\Exception;
use Parable\Orm\Transaction;
use PHPUnit\Framework\TestCase;
use Throwable;

class TransactionTest extends TestCase
{
    /** @var Database */
    private $database;

    /** @var Transaction */
    private $transaction;

    public function setUp(): void
    {
        parent::setUp();

        $this->database = new Database();
        $this->database->setType(Database::TYPE_SQLITE);
        $this->database->setDatabaseName(':memory:');

        $this->database->connect();

        $this->database->query("
            CREATE TABLE users (
              id INTEGER PRIMARY KEY,
              name TEXT NOT NULL
            );
        ");

        $this->transaction = new Transaction($this->database);
    }

    public function testTransactionStartWithSqlite(): void
    {
        self::assertFalse($this->transaction->isInTransaction());

        $this->transaction->begin();

        self::assertTrue($this->transaction->isInTransaction());
    }

    public function testTransactionStartIsAttemptedEvenIfMySQLNotAvailable(): void
    {
        $database = new Database();
        $database->setType(Database::TYPE_MYSQL);

        $transaction = new Transaction($database);

        try {
            $transaction->begin();
        } catch (Exception $exception) {
            // All good, we expect this
        }

        self::assertSame(
            'START TRANSACTION',
            $database->getLastQuery()
        );
    }

    public function testExceptionThrownWhenInvalidDatabaseTypeIsPassed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot start transaction for database type 5656');

        $database = new Database();
        $database->setType(5656);

        $transaction = new Transaction($database);

        $transaction->begin();
    }

    public function testTransactionHasEffectOnCommit(): void
    {
        self::assertFalse($this->transaction->isInTransaction());

        self::assertSame(0, $this->countUsers());

        $this->transaction->begin();

        $this->createUser();

        self::assertSame(1, $this->countUsers());

        $this->transaction->commit();

        self::assertSame(1, $this->countUsers());
    }

    public function testTransactionHasNoEffectOnRollback(): void
    {
        self::assertFalse($this->transaction->isInTransaction());

        self::assertSame(0, $this->countUsers());

        $this->transaction->begin();

        $this->createUser();

        self::assertSame(1, $this->countUsers());

        $this->transaction->rollback();

        self::assertSame(0, $this->countUsers());
    }

    public function testWithTransactionCommitsOnSuccess(): void
    {
        self::assertSame(0, $this->countUsers());

        $this->transaction->withTransaction(function () {
            $this->createUser();
        });

        self::assertSame(1, $this->countUsers());
    }

    public function testWithTransactionRollbacksOnFailure(): void
    {
        self::assertSame(0, $this->countUsers());

        try {
            $this->transaction->withTransaction(function () {
                $this->createUser();

                throw new Exception('Nope');
            });

            self::fail('Throwing exception should have halted withTransaction');
        } catch (Throwable $exception) {
            // don't care
        }

        self::assertSame(0, $this->countUsers());
    }

    public function testWithTransactionReThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nope');

        $this->transaction->withTransaction(function () {
            throw new LogicException('Nope');
        });
    }

    public function testWithTransactionReturnsCallableReturnValue(): void
    {
        $value = $this->transaction->withTransaction(function () {
            return 'test';
        });

        self::assertSame('test', $value);
    }

    public function testNestedTransactionsAreExplicitlyDisallowed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot start a transaction within a transaction');

        $this->transaction->begin();
        $this->transaction->begin();
    }

    public function testNestedTransactionsAreExplicitlyDisallowedThroughWithTransaction(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot start a transaction within a transaction');

        $this->transaction->withTransaction(function () {
            $this->transaction->withTransaction(function () {
                // Haha nope
            });
        });
    }

    public function testCannotCommitWithoutActiveTransaction(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot commit while not in a transaction');

        $this->transaction->commit();
    }

    public function testCannotRollbackWithoutActiveTransaction(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot rollback while not in a transaction');

        $this->transaction->rollback();
    }

    public function testRollbackIsCalledOnDestruct(): void
    {
        $this->transaction->begin();

        $this->createUser();

        $this->transaction->__destruct();

        self::assertSame(0, $this->countUsers());
    }

    private function createUser(): void
    {
        $this->database->query("INSERT INTO users (`name`) VALUES ('name')");
    }

    private function countUsers(): int
    {
        return (int)current($this->database->query('SELECT count(*) FROM users')[0]);
    }
}
