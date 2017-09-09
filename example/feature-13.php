<?php

require('../vendor/autoload.php');

define('CACHE_DATA_FOLDER', dirname(__FILE__)."/cache-folder");

use Hybrid\Cache;
use Hybrid\PageCache;
use Hybrid\Storages\Redis as RedisStorage;

$storage = new RedisStorage();
$storage->setFormat(RedisStorage::F_CLEAN);

Cache::addStorageMedia( $storage );


$page = new PageCache($argv[1]);

$page->setKeyEncodeMethod( Cache::K_UNSERIALIZED_URL );

$page->saveClean(true);

$page->run();


sleep(5);

echo "Example";

?>

No PHP content


