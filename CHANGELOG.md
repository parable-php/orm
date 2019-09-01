# Parable PHP ORM

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
