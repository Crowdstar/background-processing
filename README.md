[![Build Status](https://travis-ci.org/Crowdstar/background-processing.svg?branch=master)](https://travis-ci.org/Crowdstar/background-processing)
[![Latest Stable Version](https://poser.pugx.org/Crowdstar/background-processing/v/stable.svg)](https://packagist.org/packages/crowdstar/background-processing)
[![Latest Unstable Version](https://poser.pugx.org/Crowdstar/background-processing/v/unstable.svg)](https://packagist.org/packages/crowdstar/background-processing)
[![License](https://poser.pugx.org/Crowdstar/background-processing/license.svg)](https://packagist.org/packages/crowdstar/background-processing)

This package allows continuing processing PHP after having HTTP response sent back to the client under PHP-FPM.

PHP functions added by this package are executed after HTTP response sent back to the client but before PHP shutdown (
before any registered shutdown function is called). For detailed discussions on PHP shutdown sequence, function
_fastcgi_finish_request()_ and related topics, please check the post "[Background Processing in PHP](https://github.com/deminy/background-processing-in-php)".

# Limitations and Side Effects

This package is for PHP-FPM only. Don't try to run it under CLI, PHP built-in web server, mod_php or FastCGI since it
won't work.

After sending HTTP response back to client side, background functions added continue to run and the PHP-FPM process is
still running. To avoid side effects on your web server, please use this package accordingly. You may consider using
some worker instances or queue servers instead. When using this package, you may consider following suggestions to
minimize side effects:

* increase number of child processes in PHP-FPM.
* increase maximum execution time for PHP-FPM.

When using locks, please note that subsequent requests might block even client side has received a response from a
previous request, since a lock may still be active while running background tasks in the previous request.

# Installation

```bash
composer require crowdstar/background-processing:~1.0.0
```

# Sample Usage

```php
<?php
use CrowdStar\BackgroundProcessing\BackgroundProcessing;

$sum  = 0;
$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'background-processing.txt';

// First background task added.
BackgroundProcessing::add(
// Increase $sum by the sum of given numbers. Final value of $sum is this example is 7 (1+2+4).
    function (int ...$params) use (&$sum) {
        $sum += array_sum($params);
    },
    1,
    2,
    4
);
// Second background task added and will be executed after the first one.
BackgroundProcessing::add(
    function () use (&$sum, $file) {
        // Number 7 calculated from first task will be written to the file.
        file_put_contents($file, $sum);
    }
);

// Number 0 will be returned back to HTTP client.
echo
    "Current sum value is {$sum}. ",
    "Please check file {$file} in the web server; final sum value there should be 7.\n";

// Send HTTP response back to the client first, then run the two background tasks added.
BackgroundProcessing::run();

// Anything here also runs in background.
echo "This message won't shown up in HTTP response.";
?>
```

# Integration Guides

## Integrate with Symfony

```php
<?php
// Sample code borrowed from https://github.com/symfony/demo/blob/v1.2.4/public/index.php
use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

$kernel = new Kernel($env, $debug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);

// Method BackgroundProcessing::add() could be called from a bundle, a controller or
// anywhere before method BackgroundProcessing::run() is called.
CrowdStar\BackgroundProcessing\BackgroundProcessing::add(
    function() {
        mail('user@example.com', 'test', 'test');
    }
);
CrowdStar\BackgroundProcessing\BackgroundProcessing::run();
?>
```

## Integrate with Laravel

```php
<?php
// Sample code borrowed from https://github.com/laravel/laravel/blob/5.5/public/index.php
define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$response->send();
$kernel->terminate($request, $response);

// Method BackgroundProcessing::add() could be called from a controller, a middleware or
// anywhere before method BackgroundProcessing::run() is called.
CrowdStar\BackgroundProcessing\BackgroundProcessing::add(
    function() {
        mail('user@example.com', 'test', 'test');
    }
);
CrowdStar\BackgroundProcessing\BackgroundProcessing::run();
?>
```

## Integrate with Lumen

```php
<?php
// Sample code borrowed from https://github.com/laravel/lumen/blob/5.5/public/index.php
$app = require __DIR__ . '/../bootstrap/app.php';
$app->run();

// Method BackgroundProcessing::add() could be called from a controller, a middleware or
// anywhere before method BackgroundProcessing::run() is called.
CrowdStar\BackgroundProcessing\BackgroundProcessing::add(
    function() {
        mail('user@example.com', 'test', 'test');
    }
);
CrowdStar\BackgroundProcessing\BackgroundProcessing::run();
?>
```

## Integrate with Slim 3

```php
<?php
// Sample code borrowed from https://github.com/slimphp/Slim/blob/3.x/example/index.php
require 'vendor/autoload.php';

$app = new Slim\App();

$app->get('/', function ($request, $response, $args) {
    $response->write("Welcome to Slim!");
    return $response;
});

$app->get('/hello[/{name}]', function ($request, $response, $args) {
    $response->write("Hello, " . $args['name']);
    return $response;
})->setArgument('name', 'World!');

$app->run();

// Method BackgroundProcessing::add() can be called from a route, a middleware or
// anywhere before method BackgroundProcessing::run() is called.
CrowdStar\BackgroundProcessing\BackgroundProcessing::add(
    function() {
        mail('user@example.com', 'test', 'test');
    }
);
CrowdStar\BackgroundProcessing\BackgroundProcessing::run();
?>
```
