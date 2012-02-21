<?php

/**
 * Disk
 *
 * @author Oscar Gentilezza (exos) <ogentilezza@dreamdesigners.com.ar>
 * @version 0.1
 * @package
 * @subpackage
 * @todo:
 */

namespace Hybrid\Storages;

use \Hybrid\StorageMedia;
use \Exception;

class Disk implements StorageMedia {

    const F_PHP = 10;
    const F_JSON = 20;
    
    private $_path = '/var/lib/cache/HybridCache';
    private $_compress = false;
    
    protected $_format = self::F_PHP;

    private function encode ($val) {
        
        switch ($this->_format) {
            
            case self::F_PHP:
                $data = serialize($val);
                break;
            case self::F_JSON:
                $data = json_encode($val);
                break;
            default:
                throw new Exception("Unknow format");
        }
        
        if ($this->_compress) {
            return gzdeflate($data);
        } else {
            return $data;
        }
        
    }

    private function decode ($val) {
        
        if ($this->_compress) {
            $val = gzinflate($val);
        }
        
        switch ($this->_format) {
            
            case self::F_PHP:
                return unserialize($val);
                break;
            case self::F_JSON:
                return json_decode($val);
                break;
            default:
                throw new Exception("Unknow format");
        }
        
    }
    
    public function __construct($path = null, $compress = null) {
        
        if (is_null($path)) {
            if (defined('CACHE_DATA_FOLDER')) {
                $this->_path = CACHE_DATA_FOLDER;
            } else {
		$this->_path = '/tmp/hybridcache/'. $_SERVER['SERVER_NAME'];
	    }
        } else {
            $this->_path = $path;
        }
        
        if (is_null($compress)) {
            if (defined('MEMCACHE_COMPRESS')) {
                $this->_compress = (bool) MEMCACHE_COMPRESS;
            }
        } else {
            $this->_compress = (bool) $compress;
        }
        	
    }
    
    public function connect() {}

    public function setPrefix($prefix) {}
    
    public function setFormat($format) {
        $this->_format = $format;
    }
    
    public function get($key) {
        $kk = md5($key);
        
        $path = "{$this->_path}/{$kk{0}}/{$kk{1}}/{$kk}.cache";
        
        if (file_exists($path)) {
            return $this->decode(file_get_contents($path));
        }
    }
    
    public function set($key, $value, $expire = null) {
        
        $kk = md5($key);
        
        $dir = "{$this->_path}/{$kk{0}}/{$kk{1}}";
        $path = "$dir/{$kk}.cache";
        
        if (!file_exists($dir)) {
            mkdir($dir, 0750, true);
        }
        
        file_put_contents($path, $this->encode($value));
        
    }

}
