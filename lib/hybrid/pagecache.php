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
 * PageCache 
 */

namespace Hybrid;

class PageCache {
    
    protected $_identifier;
    protected $_prebuffer;
    protected $_cache;
    
    public $defaultContentType;
    
    public $compress = false;
        
    public function __construct ($identifier = null) {
        
        if (is_null($identifier)) {
            $this->_identifier = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $this->_identifier = $identifier;
        }
        
        if (defined('PAGE_CACHE_DEFAULT_CONTENT-TYPE')) {
            $this->defaultContentType = PAGE_CACHE_DEFAULT_CONTENT-TYPE;
        } else {
            $this->defaultContentType = 'text/html; ';
        }
        
        $this->_cache = new Cache($this->_identifier);
        
    }
    
    public function saveClean ($cond) {
        $this->_cache->saveClean($cond);
    }
    
    public function setKeyEncodeMethod ($method) {
        $this->_cache->encode_key_method = $method;
    }
    
    public function run ($code = 200, $type = null, $termcode = 0) {
        
        if (is_null($type)) {
            $type = $this->defaultContentType;
        }
        
        $code = (int) $code;

        if ($page = $this->_cache->getCache(true)) {
            header("HTTP/1.1 $code OK");
            header("Content-type: {$type}");
            print $page;
            exit($termcode);
        } else {
            $this->_cache->setStatusSaving();
            ob_start(array($this,'save'));
        }
                
    }
    
    public function save($buffer) {
    
        if ($this->compress) $buffer = gzencode($buffer, 9);
    
        $this->_cache->save($buffer);
        return $buffer;
    }
        
}
