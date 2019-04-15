# Parable PHP ORM

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
