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
 *     curl -i "http://127.0.0.1/?reset"
 *     or
 *     docker exec -t $(docker ps -qf "name=php") bash -c "curl -i http://web/?reset" # When running Docker containers
 *
 *     # Store variable $start in cache and return it back to HTTP client, then store variable $end to cache.
 *     curl -i "http://127.0.0.1/?start=1&end=2"
 *     or
 *     docker exec -t $(docker ps -qf "name=php") bash -c "curl -i 'http://web?start=1&end=2'" # When running Docker containers
 *
 *     # Print out cached data.
 *     curl -i "http://127.0.0.1/"
 *     or
 *     docker exec -t $(docker ps -qf "name=php") bash -c "curl -i http://web" # When running Docker containers
 */

declare(strict_types=1);

use CrowdStar\BackgroundProcessing\BackgroundProcessing;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Contracts\Cache\ItemInterface;

require_once __DIR__ . '/vendor/autoload.php';

$cache = new ApcuAdapter();

if (!isset($_REQUEST['reset'])) {
    /** @var ItemInterface $item */
    $item = $cache->getItem('key');

    // Set the cache entry with data in request field "start" if not empty.
    if (!empty($_REQUEST['start'])) {
        $item->set($_REQUEST['start']);
        $cache->save($item);
    }
    // Add a background task to set the cache entry with data in request field "end" if not empty.
    if (!empty($_REQUEST['end'])) {
        BackgroundProcessing::add(
            function () use ($cache, $item) {
                $item->set($_REQUEST['end']);
                $cache->save($item);
            }
        );
    }

    // Print out whatever in the cache entry. This is the response that will be send to HTTP client.
    echo $item->get(), "\n";

    // Send HTTP response back to the client first, then run background task(s).
    BackgroundProcessing::run();
} else {
    // Wipe cached data.
    $cache->clear();
}
