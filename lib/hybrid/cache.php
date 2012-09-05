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
 *  Copyright (C) 2012 Oscar J. Gentilezza Arenas (a.k.a exos) <oscar@gentisoft.com>
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
 * Cache
 *
 * @author Oscar J. Gentilezza Arenas (a.k.a exos) <oscar@gentisoft.com>
 * @version 1.0.0-feature-13, see https://github.com/exos/HybridCache/issues/13
 * @package Hybrid
 * @license WTFPL 2.0, http://sam.zoy.org/wtfpl/
 */

namespace Hybrid;

class Cache {

    const S_DONTEXIST = 0;
    const S_USABLE = 1;
    const S_CREATION = 2;
    const S_UPDATE = 3;
    const S_CANCELED = 4;
    const S_ERROR = 5;
    const S_EXPIRED = 6;
    const S_TRANSFERED = 7;
    
    const K_SERIALIZED_MD5 = '/^[a-f0-9]{32}$';
    const K_SERIALIZED_SHA1 = '/^[a-f0-9]{40}$';
    const K_UNSERIALIZED_URL = false;
    
    const B_HASH = 10;
    const B_RANDOM = 20;
    
    const FOR_READ = 'read';
    const FOR_WRITE = 'write';
    const FOR_MIXED = 'mixed';
    
    static private $_prestorages;
    
    /**
     * prefijo en cache
     * @var string
     */
    
    protected $_prefix = "";
    
    /**
     * Identificador (nombre ??nico)
     * @var string
     */
    
    protected $_identifier;

    /**
     * Objectos de conexi??n a los storages
     * @var $_storages
     */
    
    protected $_storages;
    
    /**
     * Metadata de la cache
     * @var array 
     */
    
    protected $_metadata;
    
    /**
     * Estado de la cache
     * @var int 
     */
    
    protected $_status;
    
    /**
     * Si se setearon en la instancia los StorageMedia
     * 
     * @var bool
     */
    
    protected $_storagesChange = false;
    
    /**
     * Metodo de balanceo entre storages medias
     * 
     * @var string 
     */
    
    public $balanceMethod;
    
    /**
     * Timepo limite de la data cacheada en segundos
     * @var int
     */

    public $timeLimit;

    /**
     * Timepo limite de la tolerancia de cache en segundos (DEPRECATED STATE)
     * @var int
     */

    public $dtimeLimit;

    /**
     * Tiempo maximo de espera por la data nueva
     * @var int
     */

    public $expireWaitingTime;

    /**
     * Esta parte corresponde a la implementacion del Pool de objetos: 
     */
    
    /*
     * Bool, si se va a usar el pool de objetos o no
     */
    
    public static $use_pool = false;
    
    /*
     * Maxima cantidad de isntancias en el pool de objetos
     */
    
    public static $pool_max_objects = 3;
    
    /*
     * Cantidad de veces que puede ser reutilizada una istancia
     */
    
    public $pool_max_resets;
    
    /*
     * Metodo utilizado para encodear la key
     */
    
    public $encode_key_method;
    
    public $save_clean;
    
    /*
     * Las veces que se reseteo este objeto
     */
    
    protected $resets = 0;
    
    protected static $pool = array();
        
    /*
     * Microtime donde fue creado el objeto (solo para stats de pool)
     */
    public $__created = null;
    
    public $__lastuse = null;
    
    /**
     * Devuelve una instancia nueva, para usar tipo fluent: WonderCacheHybrid::create($ident)->getCache();
     * 
     * @return self 
     */
    
    public static function create () {
        
        if (func_num_args() == 1) {
            if ($this->isValidKey(func_get_arg(0))) {
                $identifier = func_get_arg(0);
            } else {
                $identifier = $this->encodeKey(func_get_args());
            }
        } else {
            $identifier = $this->encodeKey(func_get_args());
        }
        
        if (static::$use_pool) {
            
            if (count(static::$pool) >= 1) {
                $instance = array_shift(static::$pool);
                $pmr = $instance->pool_max_resets;
                $instance->reset($identifier);
                $instance->pool_max_resets = $pmr;
            } else {
                $instance = new self($identifier);
            }
            
            return $instance;
            
        } else {
            return new self ($identifier);
        }
        
    }
    
    /**$_status
     * Constructor
     */
    
    public function __construct() {
        
        if (func_num_args() == 1) {
            if ($this->isValidKey(func_get_arg(0))) {
                $identifier = func_get_arg(0);
            } else {
                $identifier = $this->encodeKey(func_get_args());
            }
        } else {
            $identifier = $this->encodeKey(func_get_args());
        }
        
        $this->__created = microtime(true);
        $this->reset($identifier);
    }
    
    public function __destruct() {
    
        if (static::$use_pool) {
            if (count(static::$pool) < static::$pool_max_objects && $this->resets <= $this->pool_max_resets) {
                array_push(static::$pool, $this);
            }
        }
      
    }
    
    /*
     * Check key
     */
     
    protected function isValidKey($key) {
        
        if ($this->encode_key_method) {
            return preg_match($this->encode_key_method, $key);
        } else {
            return true;
        }
        
    }
    
    /*
     * Encode key
     */
    
    protected function encodeKey($targs) {
    
        switch ($this->encode_key_method) {
        
            case self::K_SERIALIZED_MD5:
                return md5(serialize($targs));
            case self::K_SERIALIZED_SHA1:
                return sha1(serialize($targs));
            case self::K_UNSERIALIZED_URL:
                if (is_string($targs)) {
                    return $targs;
                }
                
                if (is_array($targs)) {
                    if (count($targs) > 1) {
                        throw new Exception('The method K_UNSERIALIZED_URL needs to receive a unique string as indetifier');
                    }
                    
                    if (isset($targs[0]) && is_string($targs[0])) {
                        return $targs[0];
                    } else {
                        throw new Exception('Error reciving identifier, the K_UNSERIALIZED_URL methods needs a simple string');
                    }
                    
                }
                break;
                
            default:
                throw new Exception('Key encode method not implemented');
        }
    
    }
    
    /*
     * Clear object, set all properties to null
     */
    
    private function clear() {
        
        $this->_metadata = null;
        $this->_status   = null;
        
        $this->_identifier      = null;
        $this->timeLimit	= null;
        $this->dtimeLimit	= null;
        $this->expireWaitingTime= null;
        $this->_prefix		= null;
        $this->balanceMethod	= null;
        $this->_storages = null;
        
    }
    
    /*
     * Reset properties to default
     * @param string    identifier      Final identifier
     */
    
    public function reset($identifier) {
        
        $this->_identifier      = $identifier;
        $this->timeLimit	= defined('CACHE_EXPIRE_TIME') ? CACHE_EXPIRE_TIME		: 3600;
        $this->dtimeLimit	= defined('CACHE_DEPRECATED_LIMIT') ? CACHE_DEPRECATED_LIMIT	: 3600*1.2;
        $this->expireWaitingTime= defined('CACHE_EXPIRE_WAITING') ? CACHE_EXPIRE_WAITING	: 5;
        $this->_prefix		= defined('CACHE_PREFIX') ? CACHE_PREFIX 			: (isset($_SERVER['HOST']) ? isset($_SERVER['HOST']) : '') ;
        $this->balanceMethod	= defined('CACHE_BALANCE_METHOD') ? CACHE_BALANCE_METHOD	: self::B_HASH;
        $this->pool_max_resets	= defined('CACHE_POOL_MAXRESETS') ? CACHE_POOL_MAXRESETS	: 5;
        $this->encode_key_method = defined('CACHE_KEY_ENCODE_METHOD') ? CACHE_KEY_ENCODE_METHOD   : self::K_SERIALIZED_SHA1;
        $this->save_clean       = defined('CACHE_SAVE_CLEAN') ? CACHE_SAVE_CLEAN   : false;
        
        $this->_metadata = null;
        $this->_status   = null;
        
        $this->_storages = self::$_prestorages;
        
        $this->resets++;
        $this->__lastuse = microtime(true);
        
    }

    public static function addStorageMedia(StorageMedia $storage, $for = self::FOR_MIXED) {
        if (!self::$_prestorages)
            self::$_prestorages = array();
        
        $storageMedia = (object) array(
            'connected' => false,
            'store' => $storage,
            'for'   => $for
        );
        
        if ($for == self::FOR_MIXED) {
            self::$_prestorages[self::FOR_READ][] = $storageMedia;
            self::$_prestorages[self::FOR_WRITE][] = $storageMedia;
        } else {
            self::$_prestorages[$for][] = $storageMedia;
        }
        
    }
    
    public function clearStorages() {
        $this->_storagesChange = true;
        $this->_storages = array();
    }
    
    public function addStorage(StorageMedia $storage, $for = self::FOR_MIXED) {
        
        $this->_storagesChange = true;
        
        $storageMedia = (object) array(
            'connected' => false,
            'store' => $storage,
            'for'   => $for
        );
        
        if ($for == self::FOR_MIXED) {
            $this->_storages[self::FOR_READ][] = $storageMedia;
            $this->_storages[self::FOR_WRITE][] = $storageMedia;
        } else {
            $this->_storages[$for][] = $storageMedia;
        }

    }
    
    protected function get () {
        
        switch ($this->balanceMethod) {
            
            case self::B_HASH:
                $n = abs(crc32(substr($this->_identifier,0,4)) % count($this->_storages[self::FOR_READ]));
                
                $sinst = $this->_storages[self::FOR_READ][$n];
                
                if (!$sinst->connected) {
                    $sinst->store->connect();
                    $sinst->store->setPrefix($this->_prefix);
                    $sinst->connected = true;
                }
                
                return $sinst->store->get($this->_identifier);
                
            case self::B_RANDOM:
                
                $list = $this->_storages[self::FOR_READ];
                shuffle($list);
                
                foreach ($list as $sinst) {
                    if (!$sinst->connected) {
                        $sinst->store->connect();
                        $sinst->store->setPrefix($this->_prefix);
                        $sinst->connected = true;
                    }
                    
                    if ($val = $sinst->get($this->_identifier)) {
                        return $val;
                    }
                    
                }
                
                return null;            
        }
        
    }
    
    protected function set ($val,$expire) {
        
        switch ($this->balanceMethod) {
            
            case self::B_HASH:
                $n = abs(crc32(substr($this->_identifier,0,4)) % count($this->_storages[self::FOR_WRITE]));
                break;
            case self::B_RANDOM:
                $n = rand(0,count($this->_storages[self::FOR_WRITE])-1);
                break;
                
        }
        
        $sinst = $this->_storages[self::FOR_WRITE][$n];
                
        if (!$sinst->connected) {
            $sinst->store->connect();
            $sinst->store->setPrefix($this->_prefix);
            $sinst->connected = true;
        }

        $sinst->store->set($this->_identifier,$val,$expire);
        
    }
    
    /**
     * Devuelve la metadata del registro de cache
     * 
     * @param bool $force       True forza la lectura de cache
     * @return array            O null si no existe el regitro en cache
     */
    
    public function getMetadata($force = false) {
        if (!$force && $this->_metadata) return $this->_metadata;
                
        $data = $this->get();
        
        if ($this->save_clean) {
        
            if ($data) {
                return array(
                    'status' => self::S_USABLE;
                    'data' => $data
                );
            } else {
                return null
            }
            
        }
        
        if ($data) {
            $this->_metadata = $data;
            return $data;
        } else {
            return null;
        }
        
    }
    
    /**
     * Devuelve el codigo de estatus de la cache
     * 
     * @param bool $force   True forza la lectura desde cache
     * @return int 
     */
    
    public function getStatus ($force = false) {
    
        if (!$force && $this->_status) return $this->_status;
        
        $md = $this->getMetadata($force);
        
        if ($md) {
        
            if ($this->save_clean) {
                return self::S_USABLE;
            }
            
            if (($md['status'] == self::S_CREATION || $md['status'] == self::S_UPDATE) && $md['expire'] < time()) {
                $this->_status = self::S_CANCELED;
                return self::S_CANCELED;
            }
            
            if ($md['expire'] < time()) {
                $this->_status = self::S_EXPIRED;
                return self::S_EXPIRED;
            }
            
            return $md['status'];
        } else {
            $this->_status = self::S_DONTEXIST;
            return self::S_DONTEXIST;
        }
        
    }

    /**
     * Define un nuevo estatus a la cache
     * 
     * @param int $status           Codigo de estatus
     * @param array $extras         Datos extras a guardar en la metadata
     */
    
    public function setStatus ($status, array $extras = null) {
        
        $this->_status = $status;
        $this->_metadata = null;
        
        if ($this->save_clean) {
            if (is_array($extras) && isset($extras['data'])) {
                $this->set($extras['data'], $this->dtimeLimit + $this->timeLimit );
            } else {
                // Ignore
                return;
            }
        }
        
        $md = $this->getMetadata();
        
        if (!$md) {
            $md = array(
                'identifier' => $this->_identifier,
                'created'   => time(),
                'updating'  => time(),
                'status'    => $status,
                'expire'    => time() + $this->timeLimit,
            );
        } else {
            $md['updating'] = time();
            $md['status'] = $status;
            $md['expire'] = time() + $this->timeLimit;
        }
        
        if ($extras) {
            $md = array_merge($md, $extras);
        }
        
        $this->set($md, $this->dtimeLimit + $this->timeLimit );
        return $md;
        
    }
    
    /**
     * Avisa que la cache se esta generando o actualizando
     */
    
    public function setStatusSaving () {
        
        if (in_array($this->getStatus(), array(self::S_CANCELED, self::S_DONTEXIST, self::S_ERROR))) {
            $this->setStatus(self::S_CREATION, array(
                'expire'    => time() + $this->expireWaitingTime,
            ));
        } else {
            $this->setStatus(self::S_UPDATE, array(
                'expire'    => time() + $this->expireWaitingTime,
            ));
        }
        
    }
    
    /**
     * Guarda la cache
     * @param mixed $data           Datos a cachear 
     */
    
    public function saveInCache($data) {
        
        $this->setStatus(self::S_USABLE, array(
            'data'  => $data,
            'expire' => time() + $this->timeLimit
        ));
        
    }

    public function save($data) {
        $this->saveInCache($data);
    }
    
    /**
     * Cancela el guardado de la cache, si hay procesos esperando esto cancela la espera
     */
    
    public function cancel() {
        $this->setStatus(self::S_CANCELED);
    }
    
    /**
     * Devuelve la cache o null si no existe
     * 
     * @param bool $debile        Modo debil, si la cache se esta actualizando devuelve la anterior
     * @return mixed              Dato cacheado.
     */
    
    public function getCache($debile = true) {
        
        if ($this->getStatus() == self::S_USABLE) {
            $mb = $this->getMetadata();
            return $mb['data'];
        } elseif ($debile && $this->getStatus() == self::S_UPDATE) {
            $mb = $this->getMetadata();
            return $mb['data'];
        } elseif ($this->getStatus() == self::S_UPDATE || $this->getStatus() == self::S_CREATION) {
            
            $start = time();
            
            while ($start + $this->expireWaitingTime > time()) {
                
                usleep(300000); // 0.3 seconds | 0.3 segundos

                $status = $this->getStatus(true);
                
                if ($status == self::S_USABLE) {
                    return $this->getCache($debile);
                } elseif ($status == self::S_CANCELED) {
                    return null;
                }
                                
            }
            
            return null;
        }
        
    }

   /**
     * Devuelve la cache o devuelve lo que retorna el callback luego de cacharlo
     * 
     * @param function callback   Callback a ajecutar para generar la cache
     * @return mixed              Dato cacheado.
     *
     * @param bool $debile        Modo debil, si la cache se esta actualizando devuelve la anterior
     * @param function callback   Callback a ajecutar para generar la cache
     * @return mixed              Dato cacheado.
     */

    public function getCacheOr () {
        
        if (func_num_args() == 1) {
            $debile = true;
            $cb = func_get_arg(0);
        } elseif (func_num_args() == 2) {
            $debile = (bool) func_get_arg(0);
            $cb = func_get_arg(1);
        } else {
            throw new \Exception('Bad parameters format');
        }

        if (!is_callable($cb)) {
            throw new \Exception('The callback isn\'t callable');
        }
        
        if ($cache = $this->getCache($debile)) {
            return $cache;
        } else {
            
            $this->setStatusSaving();
            
            try {
                $result = $cb($this);
            } catch (\Exception $e){
                $this->cancel();
                throw $e;
            }
            
            $this->saveInCache($result);
            
            return $result;
            
        }
        
    }
    
    public function getResets() {
        return $this->resets;
    }
    
    public function getPoolStatus () {
        
        $lepool = array();
        
        foreach (static::$pool as $obj) {
            $lepool[] = array(
                'created' => $obj->__created,
                'las use' => $obj->__lastuse,
                'resets' => $obj->getResets(),
            );
        }
        
        return array (
            'max objects on pool' => static::$pool_max_objects,
            'max resets by object' => $this->pool_max_resets,
            'total objects' => count(static::$pool),
            'pool' => $lepool
        );
    }
        
}
