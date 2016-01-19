# Zend\\Cache\\Pattern\\CallbackCache

## Overview

The callback cache pattern caches calls of non specific functions and methods given as a callback.

## Quick Start

For instantiation you can use the `PatternFactory` or do it manual:

```php
use Zend\Cache\PatternFactory;
use Zend\Cache\Pattern\PatternOptions;

// Via the factory:
$callbackCache = PatternFactory::factory('callback', array(
    'storage'      => 'apc',
    'cache_output' => true,
));

// OR, the equivalent manual instantiation:
$callbackCache = new \Zend\Cache\Pattern\CallbackCache();
$callbackCache->setOptions(new PatternOptions(array(
    'storage'      => 'apc',
    'cache_output' => true,
)));
```

## Configuration Options

<table>
<colgroup>
<col width="10%" />
<col width="47%" />
<col width="11%" />
<col width="30%" />
</colgroup>
<thead>
<tr class="header">
<th align="left">Option</th>
<th align="left">Data Type</th>
<th align="left">Default Value</th>
<th align="left">Description</th>
</tr>
</thead>
<tbody>
<tr class="odd">
<td align="left">storage</td>
<td align="left"><code>string</code> <code>array</code>
<code>Zend\Cache\Storage\StorageInterface</code></td>
<td align="left">&lt;none&gt;</td>
<td align="left">The storage to write/read cached data</td>
</tr>
<tr class="even">
<td align="left">cache_output</td>
<td align="left"><code>boolean</code></td>
<td align="left"><code>true</code></td>
<td align="left">Cache output of callback</td>
</tr>
</tbody>
</table>

## Available Methods

call(callable $callback, array $args = array())

> Call the specified callback or get the result from cache.
rtype  
mixed
Function call handler.
rtype  
mixed
generateKey(callable $callback, array $args = array())

> Generate a unique key in base of a key representing the callback part and a key representing the
arguments part.
rtype  
string
setOptions(Zend\\Cache\\Pattern\\PatternOptions $options)

> Set pattern options.
rtype  
Zend\\Cache\\Pattern\\CallbackCache
getOptions()

> Get all pattern options.
rtype  
Zend\\Cache\\Pattern\\PatternOptions
## Examples

**Instantiating the callback cache pattern**

```php
use Zend\Cache\PatternFactory;

$callbackCache = PatternFactory::factory('callback', array(
    'storage' => 'apc'
));
```
