# file-serving
Serve files to the browser that are not available directly

## Installation
```bash
composer require gisostallenberg/file-serving
```

## Usage example
Add in serve-it/.htaccess
```txt
RewriteRule .*  file-serving.php [QSA,L]
```

file-serving.php content
```php
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use GisoStallenberg\FileServing\FileServer;

$fileserver = new FileServer('../../serve-me/', 'serve-it/');
$fileserver->serve(); // will server http://example.com/serve-it/example.txt when ../serve-me/example.txt exists, gives a 404 otherwise
```

```php
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use GisoStallenberg\FileServing\FileServer;
use Symfony\Component\HttpFoundation\Response;

$fileserver = new FileServer('../../serve-me/', 'serve-it/');
$response = $fileserver->getResponse(); // do not serve yet

if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
    $fileserver = new FileServer('../../serve-other-dir/', 'serve-it/'); // check another directory
    $response = $fileserver->getResponse();
}

$response->send();
```

## Filtering extensions
Restrict which files may be served by passing an allow list of extensions.
Anything not in the list gives a 404, even when the file exists.
```php
<?php
use GisoStallenberg\FileServing\FileServer;

$fileserver = FileServer::create('../../serve-me/', 'serve-it/')
    ->filterExtensions(['css', 'js', 'png', 'jpg']);
$fileserver->serve();
```

## Multiple directories
Pass an array as the first argument to serve from several directories. They are
checked in order and the first match wins, so a 404 only happens when the file
is in none of them.
```php
<?php
use GisoStallenberg\FileServing\FileServer;

$fileserver = new FileServer(['../../serve-me/', '../../serve-other-dir/'], 'serve-it/');
$fileserver->serve();
```

## Credits
Niels Nijens (https://github.com/niels-nijens/)
