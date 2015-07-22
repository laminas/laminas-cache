# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.5.3 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#15](https://github.com/zendframework/zend-cache/pull/15) fixes an issue
  observed on HHVM when merging a list of memcached servers to add to the
  storage resource.

## 2.5.2 - 2015-07-16

### Added

- [#10](https://github.com/zendframework/zend-cache/pull/10) adds TTL support
  for the Redis adapter.
- [#6](https://github.com/zendframework/zend-cache/pull/6) adds more suggestions
  to the `composer.json` for PHP extensions supported by storage adapters.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#9](https://github.com/zendframework/zend-cache/pull/9) fixes an issue when
  connecting to a Redis instance with the `persistent_id` option.
