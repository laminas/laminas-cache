---
show_file_content: true
---

<!-- markdownlint-disable -->
## Avoid Unused Cache Adapters Are Being Installed
<!-- markdownlint-restore -->

> ### Only necessary in 2.10+
>
> Starting with 3.0.0, no storage adapter is required by this component and thus, each project has to specify the storage adapters which are required by the project.
> When migrated to 3.0.0, the `replace` section is not needed anymore.

With `laminas-cache` v2.10.0, we introduced satellite packages for all cache adapters.

In case, there is no need for several adapters in your project, you can use composer to ensure these adapters are not being installed. To make this happen, you have to specify a `replace` property within the `composer.json` of your project.

### Example `composer.json` with Only Memory Adapter Being Installed

```json
{
    "name": "vendor/project",
    "description": "",
    "type": "project",
    "require": {
        "laminas/laminas-cache": "^2.10",
        "laminas/laminas-cache-storage-adapter-memory": "^1.0"
    },
    "replace": {
        "laminas/laminas-cache-storage-adapter-apc": "*",
        "laminas/laminas-cache-storage-adapter-apcu": "*",
        "laminas/laminas-cache-storage-adapter-blackhole": "*",
        "laminas/laminas-cache-storage-adapter-dba": "*",
        "laminas/laminas-cache-storage-adapter-ext-mongodb": "*",
        "laminas/laminas-cache-storage-adapter-filesystem": "*",
        "laminas/laminas-cache-storage-adapter-memcache": "*",
        "laminas/laminas-cache-storage-adapter-memcached": "*",
        "laminas/laminas-cache-storage-adapter-mongodb": "*",
        "laminas/laminas-cache-storage-adapter-redis": "*",
        "laminas/laminas-cache-storage-adapter-session": "*",
        "laminas/laminas-cache-storage-adapter-wincache": "*",
        "laminas/laminas-cache-storage-adapter-xcache": "*",
        "laminas/laminas-cache-storage-adapter-zend-server": "*"
    }
}
```

When using `composer install` on this, only the `laminas/laminas-cache-storage-adapter-memory` is being installed.

```bash
Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
Package operations: 10 installs, 0 updates, 0 removals
  - Installing psr/simple-cache (1.0.1): Loading from cache
  - Installing psr/cache (1.0.1): Loading from cache
  - Installing laminas/laminas-zendframework-bridge (1.2.0): Loading from cache
  - Installing laminas/laminas-stdlib (3.3.1): Loading from cache
  - Installing psr/container (1.1.1): Loading from cache
  - Installing container-interop/container-interop (1.2.0): Loading from cache
  - Installing laminas/laminas-servicemanager (3.6.4): Loading from cache
  - Installing laminas/laminas-eventmanager (3.3.1): Loading from cache
  - Installing laminas/laminas-cache-storage-adapter-memory (1.0.1): Loading from cache
  - Installing laminas/laminas-cache (2.10.1): Loading from cache
Package container-interop/container-interop is abandoned, you should avoid using it. Use psr/container instead.
Generating autoload files
6 packages you are using are looking for funding.
Use the `composer fund` command to find out more!
```
