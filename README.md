# Parable ORM

[![Build Status](https://travis-ci.org/parable-php/orm.svg?branch=master)](https://travis-ci.org/parable-php/orm)
[![Latest Stable Version](https://poser.pugx.org/parable-php/orm/v/stable)](https://packagist.org/packages/parable-php/orm)
[![Latest Unstable Version](https://poser.pugx.org/parable-php/orm/v/unstable)](https://packagist.org/packages/parable-php/orm)
[![License](https://poser.pugx.org/parable-php/orm/license)](https://packagist.org/packages/parable-php/orm)

Parable ORM is a light-weight repository-pattern based ORM.

## Install

Php 7.1+ and [composer](https://getcomposer.org) are required.

```bash
$ composer require parable-php/orm
```

## Usage

Repositories are used to find, save and delete entities, which represent rows from your MySQL/Sqlite database. Parable ORM attempts to combine queries to be as efficient as possible.

Entities are relatively straight-forward PHP data objects, and are expected to offer setters and gettings for all values associated with that entity. Example:

```php
class Entity extends AbstractEntity {
    protected $id;
    protected $name;

    // We int cast because we know it is an int
    public function getId(): int {
        return (int)$this->id;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getName(): ?string {
        return $this->name;
    }
}
```

As you can see, the entity itself doesn't really need much. The Repository set up for this entity type, however, will contain some metadata so it knows how to handle them.

If you want to support automatic setting of a `created at` or `updated at` value, it's as simple as implementing either the `SupportsCreatedAt` or `SupportsUpdatedAt` interfaces. The repository will automatically pick up on it and attempt to call `markCreatedAt()` or `markUpdatedAt()`, leaving the specific property/column names up to you. Example:

```php
class Entity extends AbstractEntity implements SupportsCreatedAt {
    protected $id;
    protected $created_at;

    // We int cast because we know it is an int
    public function getId(): int {
        return (int)$this->id;
    }

    public function getCreatedAt(): ?DateTimeImmutable {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void {
        $this->created_at = $createdAt->format(Database::DATETIME_SQL);
    }

    // Only this method is defined on the interface
    public function markCreatedAt(): void {
        $this->setCreatedAt(new DateTimeImmutable());
    }
}
```

Here's the Repository to handle the above Entity:

```php
class EntityRepository extends AbstractRepository {
    public function getTableName(): string {
        return 'entity';
    }

    public function getPrimaryKey(): string {
        return 'id';
    }

    public function getEntityClass(): string {
        return Entity::class;
    }
}
```

As mentioned, both entities and repositories are intended to be as simple and straightforward as possible. Entities and Repositories use `parable-php/di`, meaning they can use constructor-based injected dependencies.

#### Basic Repository use

```php
$repository->findAll(); // returns AbstractEntity[]
```
```php
$repository->countAll(); // returns int
```
```php
$repository->find(23); // returns ?AbstractEntity
```

#### How values are returned from the database

Parable ORM includes `TypeCaster`, which attempts to cast values to the appropriate type. The default return type for it is `string`, but it also returns `DateTimeImmutable` values. It can interpret standard format `DATETIME`, `DATE` and `TIME` fields.

This means that any setter that has to do with a date, time or datetime value _must_ accept a `DateTimeImmutable` object, unless you call `TypeCaster::disable()` somewhere in your boot/plugin logic.

Since it's impossible to automatically decide whether a numerical string was supposed to be a string or an int, these are also kept as `string` values.

#### Condition-based repository use

For advanced (and possibly complex) where conditions, we use a `callable` which is given a properly set up `Query` object. This allows for fine-grained control with a very low barrier to do so.

```php
$repository->findBy(function (Query $query) {
    $query->where('name', '=', 'First-name');
    $query->whereNotNull('activated_at');
    $query->whereCallable(function (Query $query) {
        $query->where('test_value', '=', '1');
        $query->orWhere('test_value', '=', '4');
    });
});
```

This ends up building the following query:

```sql
SELECT * FROM `entity` 
WHERE (
    `entity`.`name` = 'First-name' 
    AND `entity`.`activated_at` IS NOT NULL 
    AND (
        `entity`.`test_value` = '1' 
        OR `entity`.`test_value` = '4'
    )
);
```

The methods `where`, `whereNull`, `whereNotNull` and `whereCallable` will use `AND` to combine the clauses. To do otherwise, all have an `or`-version. `orWhere`, `orWhereNull`, `orWhereNotNull`, `orWhereCallable`, and by using those specifically, an `OR` clause can be created.

In many cases you'll want to either only use `or`-clauses, or, to use an `OR` clause in an otherwise `AND`-oriented where list, use a `callable` to make sure they're grouped appropriately.

#### Saving and deleting entities

To save an entity, simply tell the repository to do so:
```php
$savedEntity = $repository->save($entity);
```

Or save multiple:
```php
$savedEntities = $repository->saveAll($entity1, $entity2, $entity3);
```

You can also easily defer a save, so that the entity is prepared for a save but not actually saved until you decide to do so. When this is done, all entities that need to be updated are saved individually as they are found in the deferred save list, but all entities that are new (aka to be `INSERT`ed) are combined into a single `INSERT` query instead.

```php
foreach ($newEntities as $entity) {
    $repository->deferSave($entity);
}
// OR:
$repository->deferSave(...$newEntities);

$repository->saveDeferred(); // returns nothing
```

If `$newEntities` contains 10 entities that are all new, one query will now insert all 10 in one go. If, for example, `$newEntities` contains 5 new and 5 pre-existing entities, `saveDeferred` will build the single `INSERT` query for the 5 new ones, and as it comes across them, save the updates immediately.

Deleting works the same:
```php
$repository->delete($entity); // Single
$repository->delete($entity1, $entity2); // Multiple, just keep adding
$repository->delete(...$entities); // Splats for the win
```

And deferred deletes are also possible, and will attempt to delete all deferred entities in a single query:

```php
$repository->deferDelete($entity); // Single
$repository->deferDelete($entity1, $entity2); // Multiple, just keep adding
$repository->deferDelete(...$entities); // Splats for the win

$repository->deleteDeferred(); // Actually perform it.
```

For both deferred saves and deletes, the currently stored entities to be saved or deleted can be cleared by calling either `clearDeferredSaves()` or `clearDeferredDeletes()`.

## Contributing

Any suggestions, bug reports or general feedback is welcome. Use github issues and pull requests, or find me over at [devvoh.com](https://devvoh.com).

## License

All Parable components are open-source software, licensed under the MIT license.
