<?php declare(strict_types=1);

namespace Parable\Orm\Tests\Classes;

use PDO;

class DummyConnection extends PDO
{
    public string $dsn;
    public ?string $username;
    public ?string $passwd;
    public ?string $options;

    /** @var int[] */
    public array $attributes = [];

    public function __construct($dsn, $username = null, $passwd = null, $options = null)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->passwd = $passwd;
        $this->options = $options;
    }

    public function setAttribute($attribute, $value): bool
    {
        $this->attributes[$attribute] = $value;

        return true;
    }
}
