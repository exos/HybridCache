<?php

/**
 * WonderCacheStorageMedia
 *
 * @author Oscar Gentilezza (exos) <ogentilezza@dreamdesigners.com.ar>
 * @version 0.1
 * @package
 * @subpackage
 * @todo:
 */

namespace Hybrid;

interface StorageMedia {

    public function connect();

    public function setFormat($format);

    public function setPrefix ($prefix);

    public function get($key);

    public function set($key, $value, $expire);

}

