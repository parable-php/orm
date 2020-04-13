# Parable PHP ORM

## 0.8.2
- Fix bug in `toArrayWithoutEmptyValues()` where a value of `0` would be seen as an empty value.

## 0.8.1

_Changes_
- Upgrade [`parable-php/query`](https://github.com/parable-php/query) to 0.3.1.

## 0.8.0

_Changes_
- Massive simplification. Removed `MysqlConnection` and `SqliteConnection`, simply returning `PDO` instances instead.
- It's now possible to call `Database::setConnectionClass(string $connectionClass)` and set a custom connection class. This class _must_ extend `PDO`.
- `Database::setType()` now accepts any `int` value, and will only throw an exception upon trying to connect.
- Due to custom connection classes, code coverage of tests has risen to 100%. Just a nice little bonus.

_Bugfixes_
- One instance where the primary key is set was not being typed. This caused issues when, for example, a `getId(): int` would attempt to return a `string` value as set directly from the database.

## 0.7.0

_Changes_

- Time for more type casting! By implementing `HasTypedProperties` and the function `getPropertyType(string $property): ?int`, you can optionally allow value type casting from and to the database. Supported types are defined on `PropertyTypeDeterminer` as `TYPE_INT`, `TYPE_DATE`, `TYPE_TIME` and `TYPE_DATETIME`. The last three will return a `DateTimeImmutable` instance. The formats of `DATE_SQL`, `DATE_TIME`, `DATETIME_SQL` are forced for these.
  - These are cast TO these types after retrieving from the database. This allows, for example, type hinting the setter for created at: `setCreatedAt(DateTimeImmutable $createdAt)` and the getter: `getCreatedAt(): DateTimeImmutable`.
  - These are transformed back to string values before saving, because `toArray()` does so.
- `PropertyTypeDeterminer` offers two static methods (`typeProperty` and `untypeProperty`) which will attempt to type, if required, the passed property and value on the entity.
- New instances of entities are now built using the DI Container. This allows DI to be used in entities if you so wish.

## 0.6.1

_Changes_

- `AbstractEntity::toArrayWithout(string ...$keys)` will allow you to get an array representation of an entity without the specified keys.
- `AbstractEntity::hasBeenMarkedAsOriginal()` has been added, and can be used to determine whether it was loaded from the database or just saved.
- `AbstractRepository::isStored()` is now available, and will tell you if the ORM thinks the entity has been stored.

## 0.6.0

And we're back to no casting whatsoever. 0.5.0 was intended to be a first step to more complete type casting for values, but it became clear the functionality wasn't going where it needed to go.

So, placing the responsibility back at the implementation where I now believe it lies. The other improvements from 0.5.0 have been kept, however.

## 0.5.0

_Major change_

This update contains a handy change. The `TypeCaster` has been added. This will attempt to cast SQL-standard dates, times and datetimes to `DateTimeImmutable` objects, leaving everything else as `string`.

See `TypeCasterTest` for the test cases. In case there are scenarios in which the parsing does not go correctly, please open an issue or PR to add the scenario to the test cases.

It's possible to disable the `TypeCaster` by calling `TypeCaster::disable()` before doing any database retrievals. Enabling it again can be done by calling `TypeCaster::enable()`.

_Changes_
- `Database::DATE_SQL` and `Database::TIME_SQL` have been added.
- `setCreatedAt()` and `setUpdatedAt()` have been removed from the feature interfaces. This leaves only the mark methods left, and leaves the implementation up to you.

## 0.4.1

_Changes_

- `AbstractRepository` did not fail gracefully when instanced without a valid `Database` instance available in the DI Container. An active Database connection is not, however, required to _instantiate_ a repository. It is, however, to use it. Now instantiating is possible, but attempting to use the repository will fail.

  See `testCreateRepositorySuccessfulWithoutDatabaseButFailsOnPerformingAnything` for specifics. Also, I'm super good at naming tests.

## 0.4.0

_Changes_

- `Database` has gained multiple getters to make it easier to work with.
- `AbstractRepository` now uses `hasValueSets()` rather than `countValueSets() > 0`.
- Obsolete soft quoting setting removed, as it was never implemented here. Quoting is done in the `parable-php/query` package.
- Implemented `__debugInfo()` on `Database` to prevent the password being leaked while being dumped. 

  **NOTE**: This _cannot_ prevent `var_export` usage. It is strongly suggested not to use `var_export` for logging purposes where an instance of `Database` may be logged. 

## 0.3.0

_Changes_

- Updated to `parable-php/query` 0.2.1, which offers `OrderBy::asc(...$keys)` and `OrderBy::desc(...$keys)`, rather than using strings.

  Example usage: `$repository->findAll(OrderBy::asc('id');`.

## 0.2.1

_Changes_

- Entities will no longer attempt to set values on new instances that are already `null`. This makes it possible to more strictly type setters.
- Repositories gained a method: `findUniqueBy($callable): AbstractEntity`. It returns null on 0, the entity on 1 and throws on 1+.

## 0.2.0

Same as 0.1.2 but now without breaking changes in a major pre-release version.

## 0.1.3

_Changes_

Revert previous changes since the directory rename actually required a 0.2.0.

## 0.1.2

_Changes_

- Renamed `Traits` directory to `Features` since they haven't been traits for a while.
- Removed `getCreatedAt()` and `getUpdatedAt()` from the `SupportsSomethingedAt` features, since Parable ORM doesn't require them and you should choose how to expose them.

## 0.1.1

_Changes_

- Remove some debug stuff.

## 0.1.0

_Changes_

- First release.
