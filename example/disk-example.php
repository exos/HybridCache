<?php

require('../lib/init.php');

define('CACHE_DATA_FOLDER', dirname(__FILE__)."/cache-folder");

use Hybrid\Cache;
use Hybrid\storages\Disk as DiskStorage;

Cache::addStorageMedia( new DiskStorage() );

$cache = Cache::create('key');

if ($data = $cache->getCache(true)) {
	echo "del cache: " . $data;
	echo "\n";
	exit(0);
} else {
	$cache->setStatusSaving();
}


echo "Generando contenido";

sleep(5);

$contenido = md5(rand(1,99999));

echo $contenido;
echo "\n";

$cache->save($contenido);
