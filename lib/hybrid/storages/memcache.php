<?php

/* This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details.
 *
 *             DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
 *                     Version 2, December 2004
 *  
 *  Copyright (C) 2011 Oscar J. Gentilezza Arenas (a.k.a exos) <exos@exodica.com.ar>
 *
 *  Everyone is permitted to copy and distribute verbatim or modified
 *  copies of this license document, and changing it is allowed as long
 *  as the name is changed.
 *  
 *            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
 *    TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION
 *  
 *  0. You just DO WHAT THE FUCK YOU WANT TO
 */

/**
 * Memcache
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

class Memcache implements StorageMedia {

    const F_PHP = 10;
    const F_JSON = 20;
    
    private $_host = 'localhost';
    private $_port = 11211;
    private $_prefix = "";
    private $_compress = false;
    
    protected $_mco;
    protected $_conected;
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
    
    public function __construct($host = null, $port = null, $compress = null) {
        
        if (is_null($host)) {
            if (defined('MEMCACHE_HOST')) {
                $this->_host = MEMCACHE_HOST;
            }
        } else {
            $this->_host = $host;
        }
        
        if (is_null($port)) {
            if (defined('MEMCACHE_PORT')) {
                $this->_port = MEMCACHE_PORT;
            }
        } else {
            $this->_port = $port;
        }
        
        if (is_null($compress)) {
            if (defined('MEMCACHE_COMPRESS')) {
                $this->_compress = (bool) MEMCACHE_COMPRESS;
            }
        } else {
            $this->_compress = (bool) $compress;
        }
        
        $this->_mco = new \Memcache();
	
    }
    
    public function connect() {
        
        if ($this->_conected) {
            throw new Exception("Memcache alrady connected");
        }
        
        $this->_conected = true;
        
        $this->_mco->connect($this->_host, $this->_port);
    }
    
    public function setFormat($format) {
        $this->_format = $format;
    }
    
    public function setPrefix ($prefix) {
        $this->_prefix = $prefix;
    }
    
    public function get($key) {
        return $this->decode($this->_mco->get($this->_prefix.$key));
    }
    
    public function set($key, $value, $expire = 3600) {
        $this->_mco->set($this->_prefix.$key,$this->encode($value),$this->_compress, $expire);
    }

}
