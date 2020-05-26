<?php declare(strict_types=1);

namespace Parable\Orm\Tests\Classes;

use PDO;

class DummyConnection extends PDO
{
    /** @var string */
    public $dsn;

    /** @var string|null */
    public $username;

    /** @var string|null */
    public $passwd;

    /** @var string|null */
    public $options;

    /** @var int[] */
    public $attributes = [];

    public function __construct($dsn, $username = null, $passwd = null, $options = null)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->passwd = $passwd;
        $this->options = $options;
    }

    public function setAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;
    }
}
