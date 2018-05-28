<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

/**
 * @return bool
 * @see http://php.net/fastcgi_finish_request
 */
function fastcgi_finish_request()
{
    return true;
}
