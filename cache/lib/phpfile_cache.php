<?php

class PhpfileCache {
	
	/**
	 * The default experation time of the cache
	 *
	 * @var int
	 */
	var $expiration = 300;
	
	/**
	 * The format the use for creating file caches
	 *
	 * @var string
	 */
	var $cachefile_format = '%s.cache';
	
	/**
	 * Message log
	 *
	 * @var array
	 */
	var $log = array();
	
	function parse($filename, $cachefilename = null, $expiration = null) {
		
		$file = new File($filename);
		
		if (!$file->exists()) {
			$this->log("$filename does not exist");
			return false;	
		}
		
		if (is_null($expiration)) {
			$expiration = $this->expiration;	
		}
		
		if (is_null($cachefilename)) {
			$cachefilename = $this->getCacheFilname($filename);
		}
		
		$cachefile = new File($cachefilename);
		
		// if the cache is valid, return it
		if ($this->isCacheValid($file, $cachefile, $expiration)) {
			$this->log("Valid cache found");
			return $cachefile->get_contents();
		}
		
		ob_start();
		
		include($filename);
		
		$result = ob_get_clean();
		
		$cachefile->open('w');
		$cachefile->write($result);
		$cachefile->close();
		$this->log("Created new cache");
		return $result;
		
	}
	
	function getCacheFilname($filename) {
		return sprintf($this->cachefile_format, $filename);		
	}

	/**
	 * Checks the cache to see if it's valid
	 *
	 * @param File $file
	 * @param File $cachefile
	 * @param int $expiration
	 */
	function isCacheValid($file, $cachefile, $expiration) {
		
		// if the file doesn't exist...
		if (!$cachefile->exists()) {
			$this->log("Cachefile already exists");
			return false;	
		}
		
		// if the original file was modified since the cache was generated
		if ($cachefile->get_modification_time() < $file->get_modification_time()) {
			$this->log("File modified since cache generation");
			return false;	
		}
		
		// if the cache expires, check to see if the expiration time has passed
		if ($expiration && $cachefile->get_modification_time() < time() - $expiration) {
			$this->log("Cache has expired");
			return false;			
		}
		
		return true;
		
	}
	
	/**
	 * log a message
	 *
	 * @param string $message
	 */
	function log($message) {
		$this->log[] = date('r').' - '.$message;
	}	
	
}

?>