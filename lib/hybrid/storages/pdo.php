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
 * Disk
 *
 * @author Oscar Gentilezza (exos) <oscar@gentisoft.com>
 * @version 0.1
 * @package
 * @subpackage
 * @todo:
 */

namespace Hybrid\Storages;

use \Hybrid\StorageMedia;
use \Exception;
use \PDO as PDOLib;

class PDO implements StorageMedia {

    const F_PHP = 10;
    const F_JSON = 20;
    const F_CLEAN = 30;
    
    private $_compress = false;
    
    protected $_format = self::F_PHP;

    protected $_conString;
    protected $_user;
    protected $_password;
    protected $_table;

    private $_pdoConnector;

    protected $_connected = false;

    private function encode ($val) {
        
        switch ($this->_format) {
            
            case self::F_PHP:
                $data = serialize($val);
                break;
            case self::F_JSON:
                $data = json_encode($val);
                break;
            case self::F_CLEAN:
                $data = (string) $data;
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
            case self::F_CLEAN:
                return $val;
                break;
            default:
                throw new Exception("Unknow format");
        }
        
    }
    
    public function __construct($conString = null, $user = 'root', $password = '', $table='pdo_dict', $compress = null) {
        
        if ($compress) $this->compress = (bool) $compress;

        if (is_null($conString)) {
            if (defined('PDO_CONNECTION_STRING')) {
                $this->conString = PDO_CONNECTION_STRING;
            } else {
                throw new \Exception('PDO Storage needs the connection string');
            }
        } else {
            $this->conString = (string) $conString;
        }

        $this->_user = $user;
        $this->_password = $password;
        $this->_table = $table;

    }
    
    public function connect() {

        if ($this->_connected) {
            throw new \Exception('PDO storage media already connected!');
        }

        $this->_pdoConnector = new PDOLib($this->_conString, $user, $password);
        $this->_pdoConnector->setAttribute(PDOLib::ATTR_ERRMODE, PDOLib::ERRMODE_EXCEPTION);
        $this->_connected = true;
 
    }

    public function setPrefix($prefix) {} // ignoring!
    
    public function setFormat($format) {
        $this->_format = $format;
    }
    
    public function get($key) {
        $key = sha1($key);

        if (!$this->_connected)
            throw new \Exception('PDO Dict does not connected');

        $query = $this->_pdoConnector->prepare("
            SELECT
                value
            FROM
                :table 
            WHERE
                key = :key;
        ");

        $query->execute(array(
            ':table' => $this->_table,
            ':key' => $key
        ));

        if ($res = $query->fetch()) {
            return $this->decode($res[0]);
        } else {
            return null;
        }

    }
    
    public function set($key, $value, $expire = null) {

        $key = sha1($key);

        if (!$this->_connected)
            throw new \Exception('PDO Dict does not connected');

        $query = $this->_pdoConnector->prepare("
            REPLACE INTO
                :table 
                    (key, value)
            VALUES
                (:key, :value);
        ");

        $query->execute(array(
            ':table' => $this->_table,
            ':key' => $key,
            ':value' => $value
        ));

        return true;
       
    }

}
