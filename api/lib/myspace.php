<?php

class MyspaceScraper {

	/**
	 * Page data
	 *
	 * @var string
	 */
	var $_data;

	var $name;
	var $genre;
	var $headline;
	var $member_since;
	var $band_website;
	var $band_members;
	var $influences;
	var $sounds_like;
	var $record_label;
	var $type_of_label;
//	var $about;

	function to_array() {
		$vars = get_object_vars($this);

		unset($vars['_data']);

		return $vars;

	}

	function load_file($filename) {
		if (!file_exists($filename)) {
			return false;
		}

		$this->_data = file_get_contents($filename);

		return $this->parse_data();
	}


	function open_url($url) {
		$request = new HTTP(new SimpleSocket());

		if (!$request->open($url)) return false;

		$result = $request->get();

		$request->close();

		if (!$result) return false;

		$this->_data = $result->body;

		return $this->parse_data();

	}

	/**
	 * Parses the data
	 *
	 * @return bool
	 */
	function parse_data() {
		// username
		if (preg_match('/<span class="nametext">([^<]*)<\/span>/', $this->_data, $matches)) {
			$this->name = trim($matches[1]);
		}

		// genre and headline
		if (preg_match_all('/<font color="#\d{6}" size="1" face="[^"]*"><strong>\s*([^<]*)<\/strong><\/font>/', $this->_data, $matches)) {
			$this->genre = trim($matches[1][0]);
			$this->headline = trim(trim($matches[1][1]), '"');
		}

		$this->extract_profile_table();
//		$this->extract_about_info();

		return true;
	}

	function extract_about_info() {
		$start_chunk = '<td valign="top" align="left" width="435" bgcolor="ffffff" style="word-wrap:break-word">';
		$end_chunk = '/\s*<\/td>\s*<\/tr>\s*<\/table>\s*<\/td>\s*<\/tr>\s*<\/table>\s*<br>/m';

		$start_pos = strpos($this->_data, $start_chunk) + strlen($start_chunk);
		$end_pos = preg_pos($end_chunk, $this->_data, $start_pos);

		$data = trim(substr($this->_data, $start_pos, $end_pos - $start_pos));

		die(debug(strip_tags($data)));

	}

	function extract_profile_table() {
		$start_chunk = '<table bordercolor="#000000" cellspacing="3" cellpadding="3" width="300" align="center" bgcolor="#ffffff" border="0">';
		$end_chunk = '/<\/table>\s*<\/td>\s*<\/tr>\s*<\/table>/m';
		$row_middle = '/<\/span><\/td><td id="Profile(.*?)" width="175" bgcolor="#d5e8fb" style="WORD-WRAP: break-word">/';
		$row_start_chunk = '<td valign="top" align="left" width="100" bgcolor="#b1d0f0" NOWRAP><span class="lightbluetext8">';

		$row_start_len = strlen($row_start_chunk);

		$start_pos = strpos($this->_data, $start_chunk) + strlen($start_chunk);
		$end_pos = preg_pos($end_chunk, $this->_data, $start_pos);

		$data = trim(substr($this->_data, $start_pos, $end_pos - $start_pos));
		$data = preg_split('/<tr id=(.*?)Row>/', $data);
		unset($data[0]);

		foreach($data as $row) {
			$row_chunks = preg_split($row_middle, $row);

			$type =  str_replace(' ', '_', strtolower(substr($row_chunks[0], $row_start_len)));
			$value = substr($row_chunks[1], 0, -10);

			$this->{$type} = $value;

		}

	}

	function extract_href($string) {
		if (preg_match('/<a href="(.*?)"/', $string, $matches)) {
			return $matches[1];

		}

		return '';

	}


}


?>