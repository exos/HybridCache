<?php

/**
 * Redis
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

class Redis implements StorageMedia {

    const F_PHP = 10;
    const F_JSON = 20;
    
    private $_host = 'localhost';
    private $_port = 6379;
    
    protected $_redis = null;
    protected $_conected = false;
    protected $_format = self::F_PHP;

    private function encode ($val) {
        
        switch ($this->_format) {
            
            case self::F_PHP:
                return serialize($val);
                break;
            case self::F_JSON:
                return json_encode($val);
                break;
            default:
                throw new Exception("Unknow format");
        }
        
    }

    private function decode ($val) {
        
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
    
    public function __construct($host = null, $port = null) {
        
        if (is_null($host)) {
            if (defined('REDIS_HOST')) {
                $this->_host = REDIS_HOST;
            }
        } else {
            $this->_host = $host;
        }
        
        if (is_null($port)) {
            if (defined('REDIS_PORT')) {
                $this->_port = REDIS_PORT;
            }
        } else {
            $this->_port = $port;
        }
        
        $this->_redis = new \Redis();
	
    }
    
    public function connect() {
        
        if ($this->_conected) {
            throw new Exception("Redis alrady connected");
        }
        
        $this->_conected = true;
        $this->_redis->connect($this->_host, $this->_port);
        
        $this->_redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
    }
    
    public function setFormat($format) {
        $this->_format = $format;
    }
    
    public function setPrefix ($prefix) {
        $this->_redis->setOption(\Redis::OPT_PREFIX,$prefix);
    }
    
    public function get($key) {
        return $this->decode($this->_redis->get($key));
    }
    
    public function set($key, $value, $expire = null) {

        // por algun motivo no exset ni set con el parametro de expiracion funcionaron, asi si.
        // For some reason, the exset method and the set method width expiration parameter don't work
        
        $this->_redis->set($key, $this->encode($value));
        if ($expire) $this->_redis->setTimeout($key,$expire);
    }

}
