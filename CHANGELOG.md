# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.5.3 - 2015-09-15

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-cache#15](https://github.com/zendframework/zend-cache/pull/15) fixes an issue
  observed on HHVM when merging a list of memcached servers to add to the
  storage resource.
- [zendframework/zend-cache#17](https://github.com/zendframework/zend-cache/pull/17) Composer: moved
  `laminas/laminas-serializer` from `require` to `require-dev` as using the
  serializer is optional.
- A fix was provided for [ZF2015-07](https://getlaminas.org/security/advisory/ZF2015-07),
  ensuring that any directories or files created by the component use umask 0002
  in order to prevent arbitrary local execution and/or local privilege
  escalation.

## 2.5.2 - 2015-07-16

### Added

- [zendframework/zend-cache#10](https://github.com/zendframework/zend-cache/pull/10) adds TTL support
  for the Redis adapter.
- [zendframework/zend-cache#6](https://github.com/zendframework/zend-cache/pull/6) adds more suggestions
  to the `composer.json` for PHP extensions supported by storage adapters.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-cache#9](https://github.com/zendframework/zend-cache/pull/9) fixes an issue when
  connecting to a Redis instance with the `persistent_id` option.
