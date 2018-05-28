<?php
/**
 * This PHP page is used under Nginx/PHP-FPM for unit test purpose. It accepts two request parameters:
 *     1. parameter "start" to store given value in APCu and return it back in HTTP response;
 *     1. parameter "end" to store given value in APCu after HTTP response sent back from PHP-FPM/Nginx.
 */

use CrowdStar\BackgroundProcessing\BackgroundProcessing;
use Symfony\Component\Cache\Simple\ApcuCache;

require_once __DIR__ . '/vendor/autoload.php';

$cache = new ApcuCache();

// Set the cache entry with data in request field "start" if not empty.
if (!empty($_REQUEST['start'])) {
    $cache->set('key', $_REQUEST['start']);
}
// Add a task to background to set the cache entry with data in request field "end" if not empty.
if (!empty($_REQUEST['end'])) {
    BackgroundProcessing::add(
        function () use ($cache) {
            $cache->set('key', $_REQUEST['end']);
        }
    );
}

// Print out whatever in the cache entry.
echo $cache->get('key', '');

// Run tasks added to background. At this point, HTTP response has already been sent back to client.
BackgroundProcessing::run();
