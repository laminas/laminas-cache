# laminas-cache

[![Build Status](https://github.com/laminas/laminas-cache/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/laminas/laminas-cache/actions/workflows/continuous-integration.yml)

> ## 🇷🇺 Русским гражданам
>
> Мы, участники Laminas, родились и живем в разных странах. У многих из нас есть друзья, родственники и коллеги как в России, так и в Украине. Некоторые из нас родились в России. Некоторые из нас живут в России. У некоторых бабушки и дедушки сражались с фашистами во Второй мировой войне. Здесь никто не поддерживает фашизм.
>
> У одного из нас есть украинская родственница, которая спаслась из дома вместе с сыном. Поезд задержался из-за бомбежки на дороге впереди. У нас есть друзья, которые прячутся в бомбоубежищах. Мы с тревогой ждем весточки от них после воздушных налетов, которые беспорядочно наносят удары и попадают по больницам, школам, детским садам и домам. Мы не берем это из каких-либо СМИ. Мы наблюдаем это напрямую.
>
> Вы доверяете нам достаточно, чтоб использовать наши программы, и мы просим вас довериться нам вновь. Мы нуждаемся в помощи. Выходите и протестуйте против этой бесполезной войны. Остановите кровопролитие. Скажите "Нет войне!"
>
> ## 🇺🇸 To Citizens of Russia
>
> We at Laminas come from all over the world. Many of us have friends, family and colleagues in both Russia and Ukraine. Some of us were born in Russia. Some of us currently live in Russia. Some have grandparents who fought Nazis in World War II. Nobody here supports fascism.
>
> One team member has a Ukrainian relative who fled her home with her son. The train was delayed due to bombing on the road ahead. We have friends who are hiding in bomb shelters. We anxiously follow up on them after the air raids, which indiscriminately fire at hospitals, schools, kindergartens and houses. We're not taking this from any media. These are our actual experiences.
>
> You trust us enough to use our software. We ask that you trust us to say the truth on this. We need your help. Go out and protest this unnecessary war. Stop the bloodshed. Say "stop the war!"

`Laminas\Cache` provides a general cache system for PHP. The `Laminas\Cache` component
is able to cache different patterns (class, object, output, etc) using different
storage adapters (DB, File, Memcache, etc).

## Documentation

- [Quickstart and introduction  to laminas](https://docs.laminas.dev/laminas-cache/v3/pattern/intro/)

- [Full Documentation](https://docs.laminas.dev/laminas-cache/)

- [Adapters API and supported cache services (ex. Redis, memcached, APCu, FileSystem, etc.)](https://docs.laminas.dev/laminas-cache/v3/storage/adapter/)

- File issues at https://github.com/laminas/laminas-cache/issues

## Standalone

If this component is used without `laminas-mvc` or `mezzio`, a [PSR-11](https://www.php-fig.org/psr/psr-11/) container to fetch services, adapters, plugins, etc. is needed.

The easiest way would be to use [laminas-config-aggregator](https://docs.laminas.dev/laminas-config-aggregator/) along with [laminas-servicemanager](https://docs.laminas.dev/laminas-servicemanager/).

```php
use Laminas\Cache\ConfigProvider;
use Laminas\Cache\Service\StorageAdapterFactoryInterface;
use Laminas\Cache\Storage\Adapter\Memory;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\ServiceManager;

$config = (new ConfigAggregator([
    ConfigProvider::class,
]))->getMergedConfig();

$dependencies = $config['dependencies'];

$container = new ServiceManager($dependencies);

/** @var StorageAdapterFactoryInterface $storageFactory */
$storageFactory = $container->get(StorageAdapterFactoryInterface::class);

$storage = $storageFactory->create(Memory::class);

$storage->setItem('foo', 'bar');
```

## Benchmarks

We provide scripts for benchmarking laminas-cache using the
[PHPBench](https://github.com/phpbench/phpbench) framework; these can be
found in the `benchmark/` directory.

To execute the benchmarks you can run the following command:

```bash
$ vendor/bin/phpbench run --report=aggregate
```
