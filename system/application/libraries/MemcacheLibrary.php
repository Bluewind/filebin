<?php

/**
* codeigniter-memcache v0.1
*
* codeigniter-memcache is a codeigniter library to work with memcached easily.
*
*
* @file MemcacheLibrary.php
* @copyright 2010 egnity - egnity.com
* @author Emre Yilmaz <mail@emreyilmaz.me>
* @license GNU General Public License
* @version 0.1
*/


class MemcacheLibrary {

    /**
    * variable that holds memcached backend instance
    *
    * @var object
    * @access public
    */
    public $memcachedInstance;
    
    /**
    * variable that holds servers for the memcache
    *
    * @var array
    * @access public
    */
    public $servers = array();
    
    
    /**
    * main CodeIgniter instance
    *
    * @var object
    * @access public
    */
    public $CI;

    /**
    * constructor function for the library
    */
    public function __construct() {
        
        /* initialize memcached instance */
        if(class_exists("Memcache")) {
            $this->memcachedInstance = new Memcache();
        } else {
            throw new Exception(
                    "Memcached client doesn't exists in your PHP configuration"
                );
        }

        /* load super CI instance */
        $this->CI =& get_instance();
        
        /* load default server info */
        $this->CI->config->load("memcache");

        /* connect to default server */
        if($this->CI->config->item("MEMCACHE_HOST") && $this->CI->config->item("MEMCACHE_PORT") !== false) {
            $this->addServer($this->CI->config->item("MEMCACHE_HOST"), $this->CI->config->item("MEMCACHE_PORT"));
        }

    }
    
    /**
    * adder function for the memcache servers
    *
    * @access public
    * @return void
    */ 
    public function addServer($server, $port) {
        $this->servers[] = array(
            "server" => $server,
            "port"   => $port,
        );
        
        $this->memcachedInstance->addServer($server, $port);
    }

    /**
    * gets related key from the memcache
    *
    * @access public
    */ 
    public function get($key) {
        $this->logDebugMessage(sprintf("%s key requested from memcache", $key));
        return $this->memcachedInstance->get($key);
    }
    
    /**
    * sets related key to the memcache
    *
    * @access public
    */   
    public function set($key, $value, $expire = null) {
        $this->logDebugMessage(
                sprintf("%s key set to memcache. (expire: %s)",$key, $expire)
            );
        return $this->memcachedInstance->set($key, $value, null, $expire);
    }

    /**
    * deletes related key from the memcache
    *
    * @access public
    */ 
    public function delete($key) {
        $this->logDebugMessage(sprintf("%s key deleted from memcache.", $key));
        return $this->memcachedInstance->delete($key);
    }

    /**
    * increments related key from the memcache
    *
    * @access public
    */ 
    public function increment($key, $offset = 1) {
        $this->logDebugMessage(sprintf("%s key incremented %s times", $key, $offset));
        return $this->memcachedInstance->increment($key,  $offset);
    }

    /**
    * decrements related key from the memcache
    *
    * @access public
    */ 
    public function decrement($key, $offset = 1) {
        $this->logDebugMessage(sprintf("%s key decremented %s times", $key, $offset));
        return $this->memcachedInstance->decrement($key,  $offset);
    }
        
    /**
    * gets running memcached servers.
    *
    * @access public
    * @return array
    */ 
    public function getRunningServers() {
        return $this->servers;
    }

    /**
    * array of server statistics, one entry per server. 
    *
    * @access public
    * @return array
    */ 
    public function getStatistics() {
        return $this->memcachedInstance->getStats();
    }

    /**
    * Invalidates all items from the memcache.
    *
    * @access public
    * @return boolean
    */ 
    public function flush($delay = 0) {
        $this->logDebugMessage(sprintf("memcache flushed! (delay: %s)", $delay));
        return $this->memcachedInstance->flush($delay);
    }

    /**
    * logs the memcache actions to the codeigniter's main logging system.
    *
    * @access private
    */ 
    private function logDebugMessage($message) {
        log_message("debug", $message);
    }
}




?>
