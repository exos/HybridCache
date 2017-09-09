<?php

require('../vendor/autoload.php');

use Hybrid\Cache;
use Hybrid\Storages\Redis as RedisStorage;


// Set your host/port
Cache::addStorageMedia( new RedisStorage('localhost') );

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
