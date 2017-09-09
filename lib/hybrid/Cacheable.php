<?php

namespace Hybrid;

use Cache as HCache;

trait Cacheable {
    
    protected $_cache = array();
    
    public function isCached() {
        
        $idenfifier = md5(serialize(func_get_args()));
        
        if (!$this->_cache[$identifier]) {
            $this->_cache[$identifier] = HCache::create($idenfifier);
        }
        
        if ($data = $this->_cache[$identifier]->getCache(true)) {
            return $data;
        } else {
            $this->_cache[$identifier]->setStatusSaving();
            return null;
        }
    }
    
    public function saveCache() {
        $args = func_get_args();
        $data = array_shift($args);
        $idenfifier = md5(serialize($args));
        
        $this->_cache[$identifier]->save($data);
        
        return $data;
        
    }
    
}
