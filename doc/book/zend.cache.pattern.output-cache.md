# Zend\\Cache\\Pattern\\OutputCache

## Overview

The `OutputCache` pattern caches output between calls to `start()` and `end()`.

## Quick Start

Instantiating the output cache pattern

```php
use Zend\Cache\PatternFactory;

$outputCache = PatternFactory::factory('output', array(
    'storage' => 'apc'
));
```

## Configuration Options

<table>
<colgroup>
<col width="7%" />
<col width="49%" />
<col width="12%" />
<col width="31%" />
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
</tbody>
</table>

## Available Methods

start(string $key) --- :noindex:

> If there is a cached item with the given key display it's data and return `true` else start
buffering output until `end()` is called or the script ends and return `false`.
rtype  
boolean
end() --- :noindex:

> Stops buffering output, write buffered data to cache using the given key on `start()` and displays
the buffer.
rtype  
boolean
setOptions(Zend\\Cache\\Pattern\\PatternOptions $options) --- :noindex:

> Set pattern options.
rtype  
Zend\\Cache\\Pattern\\OutputCache
getOptions() --- :noindex:

> Get all pattern options.
rtype  
Zend\\Cache\\Pattern\\PatternOptions
## Examples

**Caching simple view scripts**

```php
$outputCache = Zend\Cache\PatternFactory::factory('output', array(
    'storage' => 'apc',
));

$outputCache->start('mySimpleViewScript');
include '/path/to/view/script.phtml';
$outputCache->end();
```
