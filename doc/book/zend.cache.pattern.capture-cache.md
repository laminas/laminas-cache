# Zend\\Cache\\Pattern\\CaptureCache

## Overview

The `CaptureCache` pattern is useful to auto-generate static resources in base of a HTTP request.
The Webserver needs to be configured to run a PHP script generating the requested resource so
further requests for the same resource can be shipped without calling PHP again.

It comes with basic logic to manage generated resources.

## Quick Start

Simplest usage as Apache-404 handler

``` sourceCode
# .htdocs
ErrorDocument 404 /index.php
```

``` sourceCode
// index.php
use Zend\Cache\PatternFactory;
$capture = Zend\Cache\PatternFactory::factory('capture', array(
    'public_dir' => __DIR__,
));

// Start capturing all output excl. headers and write to public directory
$capture->start();

// Don't forget to change HTTP response code
header('Status: 200', true, 200);

// do stuff to dynamically generate output
```

## Configuration Options

<table>
<colgroup>
<col width="14%" />
<col width="18%" />
<col width="18%" />
<col width="48%" />
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
<td align="left">public_dir</td>
<td align="left"><code>string</code></td>
<td align="left">&lt;none&gt;</td>
<td align="left">Location of public directory to write output to</td>
</tr>
<tr class="even">
<td align="left">index_filename</td>
<td align="left"><code>string</code></td>
<td align="left">&quot;index.html&quot;</td>
<td align="left">The name of the first file if only a directory was requested</td>
</tr>
<tr class="odd">
<td align="left">file_locking</td>
<td align="left"><code>boolean</code></td>
<td align="left"><code>true</code></td>
<td align="left">Locking output files on writing</td>
</tr>
<tr class="even">
<td align="left">file_permission</td>
<td align="left"><code>integer</code> <code>boolean</code></td>
<td align="left">0600 (<code>false</code> on win)</td>
<td align="left">Set permissions of generated output files</td>
</tr>
<tr class="odd">
<td align="left">dir_permission</td>
<td align="left"><code>integer</code> <code>boolean</code></td>
<td align="left">0700 (<code>false</code> on win)</td>
<td align="left">Set permissions of generated output directories</td>
</tr>
<tr class="even">
<td align="left">umask</td>
<td align="left"><code>integer</code> <code>boolean</code></td>
<td align="left"><code>false</code></td>
<td align="left">Using umask on generating output files / directories</td>
</tr>
</tbody>
</table>

## Available Methods

## Examples

**Scaling images in base of request**

``` sourceCode
# .htdocs
ErrorDocument 404 /index.php
```

``` sourceCode
// index.php
$captureCache = Zend\Cache\PatternFactory::factory('capture', array(
    'public_dir' => __DIR__,
));

// TODO
```
