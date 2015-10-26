Zend\\Cache\\Pattern\\OutputCache
=================================

Overview
--------

The `OutputCache` pattern caches output between calls to `start()` and `end()`.

Quick Start
-----------

Instantiating the output cache pattern

``` sourceCode
use Zend\Cache\PatternFactory;

$outputCache = PatternFactory::factory('output', array(
    'storage' => 'apc'
));
```

Configuration Options
---------------------

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

Available Methods
-----------------

Examples
--------

**Caching simple view scripts**

``` sourceCode
$outputCache = Zend\Cache\PatternFactory::factory('output', array(
    'storage' => 'apc',
));

$outputCache->start('mySimpleViewScript');
include '/path/to/view/script.phtml';
$outputCache->end();
```
