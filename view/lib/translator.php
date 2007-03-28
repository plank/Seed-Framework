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
		$args = func_get_args();
		array_shift($args);
		
		if (!$translation = $this->_get_translation($string)) {

			$this->_insert_translation($string);
			$translation = 	$string;
		}
		
		return vsprintf($translation, $args);		
		
	}

	/**
	 * Returns the translation for a given string. Normally overridden in sub classes
	 *
	 * @param string $string  The term to be translated
	 * @return string		  Returns false if a translation isn't found
	 */
	function _get_translation($term) {
		return $term;	
	}

	/**
	 * Stores a term in the database to be translated
	 *
	 * @param string $term
	 * @return bool
	 */
	function _insert_translation($term) {
		return true;	
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
		// if there aren't any, insert
		$sql = sprintf("INSERT INTO cms_translations (domain, language, original, translation) VALUES ('%s', '%s', '%s', '%s')", $this->domain, $this->lang, $this->db->escape($term), $this->db->escape($term));	
		return $this->db->execute($sql);
		
	}
	
}

?>