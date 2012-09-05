<?php

require('../lib/init.php');

define('CACHE_DATA_FOLDER', dirname(__FILE__)."/cache-folder");

use Hybrid\Cache;
use Hybrid\PageCache;
use Hybrid\storages\Redis as RedisStorage;

Cache::addStorageMedia( new RedisStorage() );

$page = new PageCache('http://www.mihost.com/page_to_cache.html');
$page->run();


sleep(5);

echo "Example";

?>

No PHP content
