<?php

require('../vendor/autoload.php');

define('CACHE_DATA_FOLDER', dirname(__FILE__)."/cache-folder");

use Hybrid\Cache;
use Hybrid\Storages\Disk as DiskStorage;

Cache::addStorageMedia( new DiskStorage() );

$cache = Cache::create('key');

$data = $cache->getCacheOr(true, function () {
    echo "Cache don't exists, heavy work here\n";
    sleep(5);
    return md5(rand(1,99999));
});

echo "Result: " . $data . "\n";
