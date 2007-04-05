<?php

/**
 * part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package view
 * @subpackage translator
 */


/**
 * The translator classes are responsible for localisation, returning locale specific text for
 * given input. This base translator provides default functionality for use when translations are not
 * required.
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package view
 * @subpackage translator
 */
class Translator { 

	/**
	 * The domain is the context in which a given term is used. The same term can have different translations
	 * in different domains
	 *
	 * @var string
	 */
	var $domain = 'default';
	
	/**
	 * The current language, defaults to english
	 *
	 * @var string
	 */
	var $lang = 'en';	
	
	
	/**
	 * Cache of terms
	 *
	 * @var array
	 */
	var $cache = array();
	
	/**
	 * Constructor
	 *
	 * @param string $domain
	 * @param string $lang
	 * @return Translator
	 */
	function Translator($domain = null, $lang = null) {
		if (!is_null($domain)) $this->domain = $domain;
		if (!is_null($lang)) $this->lang = $lang;		
	}
	
	/**
	 * Returns the localised version of the given string
	 *
	 * @param string $string
	 * @param mixed $args,...
	 * @return string 
	 */
	function text($string) {
		if (!$string) return false;
		
		$args = func_get_args();
		array_shift($args);
		
		// check for a cached translation first
		if (!isset($this->cache[$string])) {
			if (!$translation = $this->_get_translation($string)) {
				$translation = $this->_insert_translation($string);
			}
		
			$this->cache[$string] = $translation;
		
		}
		
		// ignore errors
		@$translation = vsprintf($this->cache[$string], $args);		
		
		if (!$translation) {
			return vsprintf($string, $args);		
		}
		
		return $translation;
		
	}

	/**
	 * Returns the translation for a given string. Normally overridden in sub classes
	 *
	 * @param string $term  The term to be translated
	 * @return string		Returns false if a translation isn't found
	 */
	function _get_translation($term) {
		return $term;	
	}

	/**
	 * Stores a term in the database to be translated
	 *
	 * @param string $term
	 * @return string		Returns the input term
	 */
	function _insert_translation($term) {
		return $term;	
	}
}

/**
 * This translator uses a database table to hold translations
 *
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package view
 * @subpackage translator
 */
class DbTranslator extends Translator {
	
	var $db;
	
	function DbTranslator($domain, $lang, $db) {
		parent::Translator($domain, $lang);
		$this->db = $db;
	}
	
	function _get_translation($term) {
		// check for a translation
		$sql = sprintf("SELECT translation FROM cms_translations WHERE domain = '%s' AND language = '%s' AND original = '%s'", $this->domain, $this->lang, $this->db->escape($term));
		$result = $this->db->query_single($sql);		
		
		if (isset($result['translation'])) {
			return $result['translation'];	
		} else {
			return false;	
		}
	}
	
	function _insert_translation($term) {
		// check to see if maybe the term is present, but untranslated
		$sql = sprintf("SELECT original FROM cms_translations WHERE domain = '%s' AND language = '%s' AND original = '%s'", $this->domain, $this->lang, $this->db->escape($term));
		$result = $this->db->query_single($sql);		
		
		if (!isset($result['original'])) { 		//die(debug($result));
			// if there aren't any, insert
			$sql = sprintf("INSERT INTO cms_translations (domain, language, original, translation) VALUES ('%s', '%s', '%s', '%s')", $this->domain, $this->lang, $this->db->escape($term), $this->db->escape($term));	
			$this->db->execute($sql);
		}
		
		return $term;
		
	}
	
}

?>