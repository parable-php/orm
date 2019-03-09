<?php declare(strict_types=1);

namespace Parable\Orm\Tests;

use Parable\Orm\Database;
use Parable\Orm\Database\SqliteConnection;
use Parable\Orm\Exception;
use Parable\Orm\Tests\Classes\TestDatabase;
use PDO;

class DatabaseTest extends \PHPUnit\Framework\TestCase
{
    public function testSetTypeWithMySQL()
    {
        $database = new TestDatabase();

        $database->setType(Database::TYPE_MYSQL);

        self::assertSame(Database::TYPE_MYSQL, $database->getTestType());
    }

    public function testSetTypeWithSqlite()
    {
        $this->expectExceptionMessage('Sqlite requires a database.');
        $this->expectException(Exception::class);

        $database = new TestDatabase();

        $database->setType(Database::TYPE_SQLITE);

        $database->connect();
    }

    public function testSetTypeWithMySQLWithoutHost()
    {
        $this->expectExceptionMessage('MySQL requires a host.');
        $this->expectException(Exception::class);

        $database = new TestDatabase();

        $database->setType(Database::TYPE_MYSQL);

        $database->connect();
    }

    public function testSetTypeWithMySQLWithoutDatabase()
    {
        $this->expectExceptionMessage('MySQL requires a database name.');
        $this->expectException(Exception::class);

        $database = new TestDatabase();

        $database->setType(Database::TYPE_MYSQL);
        $database->setHost('host');

        $database->connect();
    }

    public function testSetAllValuesForMySQL()
    {
        $database = new TestDatabase();

        $database->setType(Database::TYPE_MYSQL);
        $database->setHost('127.0.0.1');
        $database->setPort(9001);
        $database->setDatabaseName('database');
        $database->setUsername('username');
        $database->setPassword('password');
        $database->setCharSet('utf8');
        $database->setErrorMode(PDO::ERRMODE_EXCEPTION);

        self::assertSame(Database::TYPE_MYSQL, $database->getTestType());
        self::assertSame('127.0.0.1', $database->getTestHost());
        self::assertSame(9001, $database->getTestPort());
        self::assertSame('database', $database->getTestDatabaseName());
        self::assertSame('username', $database->getTestUsername());
        self::assertSame('password', $database->getTestPassword());
        self::assertSame('utf8', $database->getTestCharSet());
        self::assertSame(PDO::ERRMODE_EXCEPTION, $database->getTestErrorMode());
    }

    public function testSetAllValuesForSqlite()
    {
        $database = new TestDatabase();

        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName('database.sqlite');
        $database->setErrorMode(PDO::ERRMODE_EXCEPTION);

        self::assertSame(Database::TYPE_SQLITE, $database->getTestType());
        self::assertSame('database.sqlite', $database->getTestDatabaseName());
        self::assertSame(PDO::ERRMODE_EXCEPTION, $database->getTestErrorMode());
    }

    public function testSqliteWithInvalidDatabase()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not read Sqlite database: database.sqlite");

        $database = new TestDatabase();

        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName('database.sqlite');
        $database->setErrorMode(PDO::ERRMODE_EXCEPTION);

        $database->connect();
    }

    public function testSetTypeWithInvalidType()
    {
        $this->expectExceptionMessage("Invalid database type: '999'");
        $this->expectException(Exception::class);

        $database = new TestDatabase();

        $database->setType(999);
    }

    public function testSetErrorModeWithInvalidValue()
    {
        $this->expectExceptionMessage("Invalid error mode set: '999'");
        $this->expectException(Exception::class);

        $database = new TestDatabase();

        $database->setErrorMode(999);
    }

    public function testSoftQuotingDisabledByDefault()
    {
        $database = new TestDatabase();

        self::assertFalse($database->isSoftQuotingEnabled());
    }

    public function testSoftQuotingCanBeSet()
    {
        $database = new TestDatabase();
        $database->enableSoftQuoting();

        self::assertTrue($database->isSoftQuotingEnabled());
    }

    public function testConnectWithSqlite()
    {
        $database = new TestDatabase();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        self::assertNull($database->getConnection());

        $database->connect();

        self::assertInstanceOf(SqliteConnection::class, $database->getConnection());
        self::assertInstanceOf(PDO::class, $database->getConnection());
    }

    public function testConnectWillNotDoAnythingIfConnectionNotNull()
    {
        $database = new TestDatabase();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        $database->connect();

        $connection1 = $database->getConnection();

        self::assertInstanceOf(SqliteConnection::class, $connection1);

        $database->connect();

        $connection2 = $database->getConnection();

        self::assertInstanceOf(SqliteConnection::class, $connection2);

        self::assertSame($connection1, $connection2);
    }

    public function testReconnectWillReconnectEvenIfConnected()
    {
        $database = new TestDatabase();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        $database->connect();

        $connection1 = $database->getConnection();

        self::assertInstanceOf(SqliteConnection::class, $connection1);

        $database->reconnect();

        $connection2 = $database->getConnection();

        self::assertInstanceOf(SqliteConnection::class, $connection2);

        self::assertNotSame($connection1, $connection2);
    }

    public function testDisconnectRemovesConnection()
    {
        $database = new TestDatabase();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        $database->connect();

        self::assertInstanceOf(SqliteConnection::class, $database->getConnection());

        $database->disconnect();

        self::assertNull($database->getConnection());
    }

    public function testBuildMySQLConnectionValuesWorks()
    {
        $database = new TestDatabase();

        $database->setType(Database::TYPE_MYSQL);
        $database->setHost('127.0.0.1');
        $database->setPort(9001);
        $database->setDatabaseName('database');
        $database->setUsername('username');
        $database->setPassword('password');
        $database->setCharSet('utf8');
        $database->setErrorMode(PDO::ERRMODE_EXCEPTION);

        self::assertSame(
            [
                'mysql:host=127.0.0.1;port=9001;dbname=database;charset=utf8',
                'username',
                'password'
            ],
            $database->buildMySQLConnectionValuesPublic()
        );
    }

    public function testQuery()
    {
        $database = new TestDatabase();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        $database->connect();

        $result = $database->query('SELECT 1+1');

        self::assertSame("2", $result[0]['1+1']);
    }

    public function testQueryConnectsIfNotAlreadyConnected()
    {
        $database = new TestDatabase();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        $result = $database->query('SELECT 1+1');

        self::assertSame("2", $result[0]['1+1']);
    }

    public function testQueryThrowsOnInvalidQuery()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not perform query: '1', reason: HY000: 'near \"1\": syntax error'");

        $database = new TestDatabase();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        $result = $database->query('1');

        self::assertSame("2", $result[0]['1+1']);
    }
}
