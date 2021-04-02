# laminas-cache

[![Build Status](https://github.com/laminas/laminas-cache/actions/workflows/continous-integration.yml/badge.svg)](https://github.com/laminas/laminas-cache/actions/workflows/continous-integration.yml)
[![Coverage Status](https://coveralls.io/repos/github/laminas/laminas-cache/badge.svg)](https://coveralls.io/github/laminas/laminas-cache)

`Laminas\Cache` provides a general cache system for PHP. The `Laminas\Cache` component
is able to cache different patterns (class, object, output, etc) using different
storage adapters (DB, File, Memcache, etc).


- File issues at https://github.com/laminas/laminas-cache/issues
- Documentation is at https://docs.laminas.dev/laminas-cache/

## Benchmarks

We provide scripts for benchmarking laminas-cache using the
[PHPBench](https://github.com/phpbench/phpbench) framework; these can be
found in the `benchmark/` directory.

To execute the benchmarks you can run the following command:

```bash
$ vendor/bin/phpbench run --report=aggregate
```
