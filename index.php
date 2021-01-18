<?php
/**
 * This PHP page is used under Nginx/PHP-FPM for unit test purpose. It accepts three request parameters:
 *     1. Parameter "reset" to wipe data cached in APCu.
 *     2. If parameter "reset" not set:
 *        2.1. Parameter "start" to store given value in APCu and return it back in HTTP response;
 *        2.2. Parameter "end" to store given value in APCu after HTTP response sent back from PHP-FPM/Nginx.
 *
 * Examples:
 *     # Wipe cached data.
 *     curl -vi "http://127.0.0.1/?reset"
 *
 *     # Store variable $start in cache and return it back to HTTP client, then store variable $end to cache.
 *     curl -vi "http://127.0.0.1/?start=1&end=2"
 *
 *     # Print out cached data.
 *     curl -vi "http://127.0.0.1/"
 */

declare(strict_types=1);

use CrowdStar\BackgroundProcessing\BackgroundProcessing;
use Symfony\Component\Cache\Simple\ApcuCache;

require_once __DIR__ . '/vendor/autoload.php';

$cache = new ApcuCache();

if (!isset($_REQUEST['reset'])) {
    // Set the cache entry with data in request field "start" if not empty.
    if (!empty($_REQUEST['start'])) {
        $cache->set('key', $_REQUEST['start']);
    }
    // Add a background task to set the cache entry with data in request field "end" if not empty.
    if (!empty($_REQUEST['end'])) {
        BackgroundProcessing::add(
            function () use ($cache) {
                $cache->set('key', $_REQUEST['end']);
            }
        );
    }

    // Print out whatever in the cache entry. This is the response that will be send to HTTP client.
    echo $cache->get('key', '');

    // Send HTTP response back to the client first, then run background task(s).
    BackgroundProcessing::run();
} else {
    // Wipe cached data.
    $cache->clear();
}
