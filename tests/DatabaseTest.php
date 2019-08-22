<?php declare(strict_types=1);

namespace Parable\Orm\Tests;

use Parable\Orm\Database;
use Parable\Orm\Database\SqliteConnection;
use Parable\Orm\Exception;
use PDO;

class DatabaseTest extends \PHPUnit\Framework\TestCase
{
    public function testSetTypeWithMySQL()
    {
        $database = new Database();

        $database->setType(Database::TYPE_MYSQL);

        self::assertSame(Database::TYPE_MYSQL, $database->getType());
    }

    public function testSetTypeWithSqlite()
    {
        $this->expectExceptionMessage('Sqlite requires a database.');
        $this->expectException(Exception::class);

        $database = new Database();

        $database->setType(Database::TYPE_SQLITE);

        $database->connect();
    }

    public function testSetTypeWithMySQLWithoutHost()
    {
        $this->expectExceptionMessage('MySQL requires a host.');
        $this->expectException(Exception::class);

        $database = new Database();

        $database->setType(Database::TYPE_MYSQL);

        $database->connect();
    }

    public function testSetTypeWithMySQLWithoutDatabase()
    {
        $this->expectExceptionMessage('MySQL requires a database name.');
        $this->expectException(Exception::class);

        $database = new Database();

        $database->setType(Database::TYPE_MYSQL);
        $database->setHost('host');

        $database->connect();
    }

    public function testSetAllValuesForMySQL()
    {
        $database = new Database();

        $database->setType(Database::TYPE_MYSQL);
        $database->setHost('127.0.0.1');
        $database->setPort(9001);
        $database->setDatabaseName('database');
        $database->setUsername('username');
        $database->setPassword('password');
        $database->setCharSet('utf8');
        $database->setErrorMode(PDO::ERRMODE_EXCEPTION);

        self::assertSame(Database::TYPE_MYSQL, $database->getType());
        self::assertSame('127.0.0.1', $database->getHost());
        self::assertSame(9001, $database->getPort());
        self::assertSame('database', $database->getDatabaseName());
        self::assertSame('username', $database->getUsername());
        self::assertSame('password', $database->getPassword());
        self::assertSame('utf8', $database->getCharSet());
        self::assertSame(PDO::ERRMODE_EXCEPTION, $database->getErrorMode());
    }

    public function testSetAllValuesForSqlite()
    {
        $database = new Database();

        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName('database.sqlite');
        $database->setErrorMode(PDO::ERRMODE_EXCEPTION);

        self::assertSame(Database::TYPE_SQLITE, $database->getType());
        self::assertSame('database.sqlite', $database->getDatabaseName());
        self::assertSame(PDO::ERRMODE_EXCEPTION, $database->getErrorMode());
    }

    public function testSqliteWithInvalidDatabase()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not read Sqlite database: database.sqlite");

        $database = new Database();

        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName('database.sqlite');
        $database->setErrorMode(PDO::ERRMODE_EXCEPTION);

        $database->connect();
    }

    public function testSetTypeWithInvalidType()
    {
        $this->expectExceptionMessage("Invalid database type: '999'");
        $this->expectException(Exception::class);

        $database = new Database();

        $database->setType(999);
    }

    public function testSetErrorModeWithInvalidValue()
    {
        $this->expectExceptionMessage("Invalid error mode set: '999'");
        $this->expectException(Exception::class);

        $database = new Database();

        $database->setErrorMode(999);
    }

    public function testConnectWithSqlite()
    {
        $database = new Database();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        self::assertNull($database->getConnection());

        $database->connect();

        self::assertInstanceOf(SqliteConnection::class, $database->getConnection());
        self::assertInstanceOf(PDO::class, $database->getConnection());
    }

    public function testConnectWillNotDoAnythingIfConnectionNotNull()
    {
        $database = new Database();
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
        $database = new Database();
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
        $database = new Database();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        $database->connect();

        self::assertInstanceOf(SqliteConnection::class, $database->getConnection());

        $database->disconnect();

        self::assertNull($database->getConnection());
    }

    public function testQuery()
    {
        $database = new Database();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        $database->connect();

        $result = $database->query('SELECT 1+1');

        self::assertSame("2", $result[0]['1+1']);
    }

    public function testQueryConnectsIfNotAlreadyConnected()
    {
        $database = new Database();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        $result = $database->query('SELECT 1+1');

        self::assertSame("2", $result[0]['1+1']);
    }

    public function testQueryThrowsOnInvalidQuery()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not perform query: '1', reason: HY000: 'near \"1\": syntax error'");

        $database = new Database();
        $database->setType(Database::TYPE_SQLITE);
        $database->setDatabaseName(':memory:');

        $result = $database->query('1');

        self::assertSame("2", $result[0]['1+1']);
    }

    public function testDebugInfoLeadsToNothingForPrintr()
    {
        $database = new Database();

        $database->setType(Database::TYPE_MYSQL);
        $database->setPassword('test_password');

        self::assertNotContains('test_password', print_r($database, true));
        self::assertContains('****** (masked)', print_r($database, true));
    }

    /**
     * This test is here so that it won't come as a surprise that a
     * var_exported database instance will contain the password.
     */
    public function testDebugInfoCannotPreventVarExport()
    {
        $database = new Database();

        $database->setType(Database::TYPE_MYSQL);
        $database->setPassword('test_password');

        // Sadly we cannot prevent var_export from doing so :(
        ob_start();

        var_export($database);

        $varexportedContent = ob_get_clean();

        self::assertContains('test_password', $varexportedContent);
        self::assertNotContains('****** (masked)', $varexportedContent);
    }
}
