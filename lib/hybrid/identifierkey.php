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
 * @version 0.9.5
 * @package Hybrid
 * @license WTFPL 2.0, http://sam.zoy.org/wtfpl/
 */

namespace Hybrid;

class IdentifierKey {

    const K_SERIALIZED_MD5 = '/^[a-f0-9]{32}$';
    const K_SERIALIZED_SHA1 = '/^[a-f0-9]{40}$';
    const K_UNSERIALIZED_URL = false;

    private $_keyType;
    private $_content;
    private $_prefix;

    public function __construct($params, $prefix = '', $keyType = null) {

        $this->prefix = (string) $prefix;

        if (is_null($keyType)) {
            $this->_keyType = defined('CACHE_KEY_ENCODE_METHOD') ? CACHE_KEY_ENCODE_METHOD  : static::K_SERIALIZED_SHA1;
        }

        if (is_string($params)) {
            $this->_content = $params;
        } else {
            $this->_content = serialize($params);
        }

    }

    /*
     * Encode key
     */
    
    protected function encodeKey() {
    
        switch ($this->_keyType) {
        
            case static::K_SERIALIZED_MD5:
                return md5(serialize($this->_content));

            case static::K_SERIALIZED_SHA1:
                return sha1(serialize($this->_content));

            case static::K_UNSERIALIZED_URL:

                if (is_string($this->_content)) {
                    return $this->_content;
                }
                
                throw new \Exception('The method K_UNSERIALIZED_URL needs to receive a unique string as indetifier');
                
            default:
                throw new \Exception('Key encode method not implemented');
        }
    
    }

    public function generate() {
        return $this->_prefix . $this->encodeKey(); 
    }

    public function __toString() {
        return $this->generate();
    }

}
