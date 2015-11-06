# Zend\\Cache\\Pattern\\ObjectCache

## Overview

The `ObjectCache` pattern is an extension to the `CallbackCache` pattern. It has the same methods
but instead it generates the internally used callback in base of the configured object and the given
method name.

## Quick Start

Instantiating the object cache pattern

```php
use Zend\Cache\PatternFactory;

$object      = new stdClass();
$objectCache = PatternFactory::factory('object', array(
    'object'  => $object,
    'storage' => 'apc'
));
```

## Configuration Options

<table>
<colgroup>
<col width="16%" />
<col width="33%" />
<col width="13%" />
<col width="36%" />
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
<td align="left">object</td>
<td align="left"><code>object</code></td>
<td align="left">&lt;none&gt;</td>
<td align="left">The object to cache methods calls of</td>
</tr>
<tr class="odd">
<td align="left">object_key</td>
<td align="left"><code>null</code> <code>string</code></td>
<td align="left">&lt;Class name of object&gt;</td>
<td align="left">A hopefully unique key of the object</td>
</tr>
<tr class="even">
<td align="left">cache_output</td>
<td align="left"><code>boolean</code></td>
<td align="left"><code>true</code></td>
<td align="left">Cache output of callback</td>
</tr>
<tr class="odd">
<td align="left">cache_by_default</td>
<td align="left"><code>boolean</code></td>
<td align="left"><code>true</code></td>
<td align="left">Cache method calls by default</td>
</tr>
<tr class="even">
<td align="left">object_cache_methods</td>
<td align="left"><code>array</code></td>
<td align="left"><code>[]</code></td>
<td align="left">List of methods to cache (If <code>cache_by_default</code> is disabled)</td>
</tr>
<tr class="odd">
<td align="left">object_non_cache_methods</td>
<td align="left"><code>array</code></td>
<td align="left"><code>[]</code></td>
<td align="left">List of methods to no-cache (If <code>cache_by_default</code> is enabled)</td>
</tr>
<tr class="even">
<td align="left">object_cache_magic_properties</td>
<td align="left"><code>boolean</code></td>
<td align="left"><code>false</code></td>
<td align="left">Cache calls of magic object properties</td>
</tr>
</tbody>
</table>

## Available Methods

\#\#\# call(string $method, array $args = array()) :noindex:

> Call the specified method of the configured object.

> rtype  
mixed
\#\#\# \_\_call(string $method, array $args) :noindex:

> Call the specified method of the configured object.

> rtype  
mixed
\#\#\# \_\_set(string $name, mixed $value) :noindex:

> Set a property of the configured object.

> rtype  
void
\#\#\# \_\_get(string $name) :noindex:

> Get a property of the configured object.

> rtype  
mixed
\#\#\# \_\_isset(string $name) :noindex:

> Checks if static property of the configured object exists.

> rtype  
boolean
\#\#\# \_\_unset(string $name) :noindex:

> Unset a property of the configured object.

> rtype  
void
\#\#\# generateKey(string $method, array $args = array()) :noindex:

> Generate a unique key in base of a key representing the callback part and a key representing the
arguments part.
rtype  
string
\#\#\# setOptions(Zend\\Cache\\Pattern\\PatternOptions $options) :noindex:

> Set pattern options.
rtype  
Zend\\Cache\\Pattern\\ObjectCache
\#\#\# getOptions() :noindex:

> Get all pattern options.
rtype  
Zend\\Cache\\Pattern\\PatternOptions
## Examples

**Caching a filter**

```php
$filter       = new Zend\Filter\RealPath();
$cachedFilter = Zend\Cache\PatternFactory::factory('object', array(
    'object'     => $filter,
    'object_key' => 'RealpathFilter',
    'storage'    => 'apc',

    // The realpath filter doesn't output anything
    // so the output don't need to be caught and cached
    'cache_output' => false,
));

$path = $cachedFilter->call("filter", array('/www/var/path/../../mypath'));
// OR
$path = $cachedFilter->filter('/www/var/path/../../mypath');
```
