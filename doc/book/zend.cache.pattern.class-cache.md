# Zend\\Cache\\Pattern\\ClassCache

## Overview

The `ClassCache` pattern is an extension to the `CallbackCache` pattern. It has the same methods but
instead it generates the internally used callback in base of the configured class name and the given
method name.

## Quick Start

Instantiating the class cache pattern

```php
use Zend\Cache\PatternFactory;

$classCache = PatternFactory::factory('class', array(
    'class'   => 'MyClass',
    'storage' => 'apc'
));
```

## Configuration Options

<table>
<colgroup>
<col width="14%" />
<col width="36%" />
<col width="8%" />
<col width="39%" />
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
<td align="left">class</td>
<td align="left"><code>string</code></td>
<td align="left">&lt;none&gt;</td>
<td align="left">The class name</td>
</tr>
<tr class="odd">
<td align="left">cache_output</td>
<td align="left"><code>boolean</code></td>
<td align="left"><code>true</code></td>
<td align="left">Cache output of callback</td>
</tr>
<tr class="even">
<td align="left">cache_by_default</td>
<td align="left"><code>boolean</code></td>
<td align="left"><code>true</code></td>
<td align="left">Cache method calls by default</td>
</tr>
<tr class="odd">
<td align="left">class_cache_methods</td>
<td align="left"><code>array</code></td>
<td align="left"><code>[]</code></td>
<td align="left">List of methods to cache (If <code>cache_by_default</code> is disabled)</td>
</tr>
<tr class="even">
<td align="left">class_non_cache_methods</td>
<td align="left"><code>array</code></td>
<td align="left"><code>[]</code></td>
<td align="left">List of methods to no-cache (If <code>cache_by_default</code> is enabled)</td>
</tr>
</tbody>
</table>

## Available Methods

call(string $method, array $args = array()) :noindex:

> Call the specified method of the configured class.

> rtype  
mixed
Call the specified method of the configured class.
rtype  
mixed
Set a static property of the configured class.
rtype  
void
Get a static property of the configured class.
rtype  
mixed
Checks if a static property of the configured class exists.
rtype  
boolean
Unset a static property of the configured class.
rtype  
void
generateKey(string $method, array $args = array()) :noindex:

> Generate a unique key in base of a key representing the callback part and a key representing the
arguments part.
rtype  
string
setOptions(Zend\\Cache\\Pattern\\PatternOptions $options) :noindex:

> Set pattern options.
rtype  
Zend\\Cache\\Pattern\\ClassCache
getOptions() :noindex:

> Get all pattern options.
rtype  
Zend\\Cache\\Pattern\\PatternOptions
## Examples

**Caching of import feeds**

```php
$cachedFeedReader = Zend\Cache\PatternFactory::factory('class', array(
    'class'   => 'Zend\Feed\Reader\Reader',
    'storage' => 'apc',

    // The feed reader doesn't output anything
    // so the output don't need to be caught and cached
    'cache_output' => false,
));

$feed = $cachedFeedReader->call("import", array('http://www.planet-php.net/rdf/'));
// OR
$feed = $cachedFeedReader->import('http://www.planet-php.net/rdf/');
```
