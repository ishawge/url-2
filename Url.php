<?php

namespace clagraff;


function resolveRelPath($filename) {
    // from: http://tomnomnom.com/posts/realish-paths-without-realpath
    $filename = str_replace('//', '/', $filename);
    $parts = explode('/', $filename);
    $out = array();
    foreach ($parts as $part){
        if ($part == '.') continue;
        if ($part == '..') {
            array_pop($out);
            continue;
        }
        $out[] = $part;
    }
    return implode('/', $out);
}


class Url
{
    private $authority = null;
    private $hierarchical = null;
    private $host = null;
    private $path = null;
    private $port = null;
    private $query = null;
    private $url = null;
    private $scheme = null;


    public function __construct($url = null) {
        if (is_null($url)) {
            $this->url = $this->current();
        } else {
            $this->url = $url;
        }
        
        $this->deconstruct();
    }
    
    public function __toString() {
        $string = $this->scheme . "://" . $this->hierarchical;
        if (strlen($this->query) > 0) {
            $string .= "?" . $this->query;
        }
        
        return $string;
    }
    
    public function asString() {
        return $this->__toString();
    }
    
    public function current() {
        $url = 'http';
        if (isset($_SERVER["HTTPS"])
            && strtolower($_SERVER["HTTPS"]) == "on")
        {
            $url .= "s";
        }
        
        $url .= "://" . $_SERVER["SERVER_NAME"];
        
        if ($_SERVER["SERVER_PORT"] != "80") {
            $url .= ":" . $_SERVER["SERVER_PORT"];
        }
        
        $url .= $_SERVER["REQUEST_URI"];
        return $url;
    }
    
    public function deconstruct($url = null) {
        if (is_null($url)) {
            if (is_null($this->url)) {
                $this->url = $this->current();
            }
            $url = $this->url;
        }
        
        $parse = parse_url($url);
        
        $this->scheme = $parse["scheme"];
        $this->host = $parse["host"];
        
        $this->authority = $this->host;
        
        if (isset($parse["port"])) {
            $this->port = $parse["port"];
            $this->authority .= ":" . $this->port;
        } else {
            $this->port = 80;
        }
        
        if (isset($parse["path"])) {
            $this->path = $parse["path"];
        } else {
            $this->path = "/";
        }
        
        $this->hierarchical = $this->authority . $this->path;
        
        if (isset($parse["query"])) {
            $this->query = $parse["query"];
        } else {
            $this->query = "";
        }
        
        $this->url = $this->scheme . "://" . $this->hierarchical;
        if (strlen($this->query) > 0) {
            $this->url .= "?" . $this->query;
        }
        
    }
    
    public function getBase() {
        $partialUrl = $this->scheme . "://" . $this->hierarchical;
        if (substr($partialUrl, -1) != "/") {
            $pos = strpos($partialUrl, basename($partialUrl));
            $partialUrl = substr($partialUrl, 0, $pos);
        }

        return $partialUrl;
    }

    public function getBasePath() {
        $path = $this->path;
        if (substr($path, -1) != "/") {
            $pos = strpos($path, basename($path));
            $path = substr($path, 0, $pos);
        }

        return $path;
    }
    
    public function join() {
        $url = $this->scheme . "://" . $this->authority;
        
        $currPath = $this->getBasePath();
        $currPath .= implode("/", func_get_args());
        $path = resolveRelPath($currPath);
        
        if (substr($path, 0, 1) != "/") {
            $path = "/" . $path;
        }
        
        return $url . $path;
    }
    
    public function redirect($rel = null) {
        if (is_null($rel) == false) {
            $this->url = $this->relative($rel);
        }
        header("Location: " . $this->url);
        ob_end_flush();
        exit();
    }
    
    public function rel($path) {
        return $this->relative($path);
    }
    
    public function relative($path) {
        $url = $this->scheme . "://" . $this->authority;
        $currPath = $this->getBasePath();
        $currPath = resolveRelPath($currPath . $path);
        
        if (substr($currPath, 0, 1) != "/") {
            $currPath = "/" . $currPath;
        }
        
        return $url . $currPath;
    }
    
    public function set($url) {
        $this->deconstruct($url);
    }
}
