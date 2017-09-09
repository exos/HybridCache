<?php

require('../vendor/autoload.php');

define('CACHE_DATA_FOLDER', dirname(__FILE__)."/cache-folder");

use Hybrid\Cache;
use Hybrid\Storages\Disk as DiskStorage;

class ExampleClass {
    
    use Hybrid\Cacheable;
    
    public function heavyProcess () {
        
        if ($cacheData = $this->isCached(__FILE__,__METHOD__)) {
            return $cacheData;
        }
        
        // heavy proccess:
        sleep(3);
        
        $data = md5(microtime());
        
        return $this->saveCache($data, __FILE__,__METHOD__);
        
    }
    
}

echo "Generating data (firt time)\n";

$ins = new ExampleClass();

echo "data: " . $ins->heavyProcess() . "\n";

echo "Generating data (second time)\n";

$insb = new ExampleClass();

echo "data: " . $insb->heavyProcess() . "\n";
