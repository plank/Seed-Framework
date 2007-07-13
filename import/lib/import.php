<?php

/**
Quick_CSV_import class provides interface to a quick CSV file to MySQL database import. Much quicker (10-100 times) that line by line SQL INSERTs.
version: 1.5
author: 
date: 23.8.2006
description:
   1. Before importing, you MUST:
     - establish connection with MySQL database and select database;
     - define CSV filename to import from.
   2. You CAN define several additional import attributes:
     - use CSV header or not: if yes, first line of the file will be recognized as CSV header, and all database columns will be called so, and this header line won't be imported in table content. If not, the table columns will be calles as "column1", "column2", etc
     - separate char: character to separate fields, comma [,] is default
     - enclose char: character to enclose those values that contain separate char in text, quote ["] is default
     - escape char: character to escape special symbols like enclose char, back slash [\] is default
     - encoding: string value which represents MySQL encoding table to parse files with. It's strongly recomended to use known values, not manual typing! Use get_encodings() method (or "SHOW CHARACTER SET" query) to ask the server for the encoding tables list
   3. You can read "error" property to get the text of the error after import. If import has been finished successfully, this property is empty.
   
   
* @author Lawrence Tenuta - modifying class from Skakunov Alexander <i1t2b3@gmail.com>
* @abstract Added methods: custom_import, get_csv_data
* 			Removed: Old mysql connection. Now using framework DB object
* @todo Clean up existing functions that Skakunov wrote. They seem messy and are using original mysql_connect. Should convert it to use our DB object
* @todo Break code into more independant functions
*/


class CSVImport 
{
  var $db; // db connection 
  var $num_records; // number of records in the given csv
  var $table_name; //where to import to
  var $file_name;  //where to import from
  var $use_csv_header; //use first line of file OR generated columns names
  var $field_separate_char; //character to separate fields
  var $field_enclose_char; //character to enclose fields, which contain separator char into content
  var $field_escape_char;  //char to escape special symbols
  var $error; //error message
  var $arr_csv_columns; //array of columns
  var $table_exists; //flag: does table for import exist
  var $encoding; //encoding table, used to parse the incoming file. Added in 1.5 version
  
  function CSVImport($db, $file_name="")
  {
  	$this->db = $db;
    $this->file_name = $file_name;
    $this->arr_csv_columns = array();
    $this->use_csv_header = true;
    $this->field_separate_char = ",";
    $this->field_enclose_char  = "\"";
    $this->field_escape_char   = "\\";
    $this->table_exists = false;
    $this->num_records = '';
  }
  
	/**
	 * Function takes a data array parsed from a csv file and a table name
	 * and returns false if a critical error occurs or returns an array with information
	 * that contains the final data that was imported, those that weren't and any error
	 * or notices.
	 *
	 * @param unknown_type $import_data
	 * @param unknown_type $tablename
	 * @return unknown
	 */
	
  function custom_import($import_data, $tablename) {
	
  	$file_data = $this->get_csv_data($import_data);
  	$table_fields = $this->db->column_definitions($tablename);
  	// rebuild table field results
  	
  	foreach ($table_fields as $key => $value) {
  		$field_info[$value['Field']] = $value;
  	}
  	
  	$final_file_data = array();
  	$errors = array();  	
  	
  	// get our primary keys
	foreach($field_info as $k=>$v) {
		if($field_info[$k]['Key'] == 'PRI') {
			$primaries[] = $k;
		}
	}
		
	foreach ($file_data as $row => $data) {

		// VALIDATION BEFORE INSERT
		$valid = true;
		 
		foreach ($data as $field => $value) {
					
			// get values in brackets of field type. ie: the 55 in varchar(55)
			if(stristr($field_info[$field]['Type'], ')')) {
				$startpos = strpos($field_info[$field]['Type'], '(') + 1;
				$endpostmp = strpos($field_info[$field]['Type'], ')');
				$endpos = $endpostmp - $startpos;
				$typevalue = substr($field_info[$field]['Type'], $startpos, $endpos);
			}
			
			 
			/**
			 *  Here are our core DB validations
			 */
			
			/// UNIQUE HANDLER

			if($field_info[$field]['Key'] == 'UNI' || $field_info[$field]['Key'] == 'MUL') {
				if($result = $this->db->query_single('SELECT * FROM ' . $tablename . ' WHERE UCASE(' . $field . ') LIKE UCASE(\'' . mysql_real_escape_string(trim($value)) . '\')')) {
					
					// DISCARD DUPLICATES
					if($import_data['rules']['rule_unique'] == 'discard') {
					 $errors['unique'][] = array("<strong>[DISCARDED]CSV Row $row </strong>: Unique field '$field' already has the value <strong>'$value'</strong>.", $result);
					 $valid = false;	
					 
					 // OVERWRITE ORIGINAL			 
					} else if($import_data['rules']['rule_unique'] == 'overwrite') {
						$this->db->update_query($tablename, "id = '" . $result['id'] . "'", $data);
						
						$errors['unique'][] = array("<strong>[OVERWRITTEN]CSV Row $row </strong>: Unique field '$field' already has the value <strong>'$value'</strong>. Record " . $result['id'] . ' has been overwritten.', $result);
						$valid = false;
						 
					// MERGE DUPLICATE	 
					} else if($import_data['rules']['rule_unique'] == 'merge') {
						 die('merge rule not implemented yet');
						 $valid = false;
					}
					 
				} 		
			}
			/// END UNIQUE HANDLER
			
			
			// insert defaults if blank value
			if(trim($value) == '') {
				$file_data[$row][$field] = $field_info[$field]['Default'];
			}
			
			// check varchars
			if(stristr($field_info[$field]['Type'], 'varchar') ) {
				if(strlen($value) > $typevalue) {
					$shortened_value = substr($value, 0, $typevalue);		
					 $errors['truncated'][] = array("CSV Row: $row: The value <strong>'$value'</strong> going into the field <strong>'$field'</strong> is too long and has been shortened to <strong>'$shortened_value'</strong>");
					 $file_data[$row][$field] = $shortened_value;
				}
			}
			
			// check enums
			if(stristr($field_info[$field]['Type'], 'enum') ) {
				$enum_pass = false;
				$enum_values = explode(',', str_replace("'", "", $typevalue));
				foreach($enum_values as $k => $v) {
					if($value == $enum_values[$k]) {
						$enum_pass = true;
					} 
				}
				
				if(!$enum_pass){
					$errors['enum'][] = array("CSV Row: $row: The value <strong>'$value'</strong> going into the field <strong>'$field'</strong> does not match any of the optional values. Value has been overwritten to the default value '<strong>" . $field_info[$field]['Default'] . "</strong>'");
				}
				$file_data[$row][$field] = $field_info[$field]['Default'];
			}
			
			// check ints
			if(stristr($field_info[$field]['Type'], 'int')) {
				if(!is_numeric($value)) {
					$errors['number'][] = array("CSV Row: $row: The value <strong>'$value'</strong> going into the field <strong>'$field'</strong> is not an integer. Value has been overwritten to the default value '<strong>" . $field_info[$field]['Default'] . "</strong>'");
					$file_data[$row][$field] = $field_info[$field]['Default'];
					
				} else if(strlen($value) > $typevalue){
					$shortened_value = substr($value, 0, $typevalue);		
					 $errors['truncated'][] = array("CSV Row: $row: The value <strong>'$value'</strong> going into the field <strong>'$field'</strong> is too long and has been shortened to <strong>'$shortened_value'</strong>");
					 $file_data[$row][$field] = $shortened_value;
				}
			}
			/**
			 * End core DB validation. Will break into functions soon. Maybe place into db models?
			 */
			
			
			// validate dates!
			if(stristr($field_info[$field]['Type'], 'date')) {
				
				if(($value != '') && (strtotime($value) == -1)) {
					$errors['date'][] = array("CSV Row: $row: The value <strong>'$value'</strong> going into the field <strong>'$field'</strong> is not a valid date format. The date value will set to its default");
					$file_data[$row][$field] = 'invalid date';
				}
			}
		}
		
		// END VALIDATION
		
				
		if($valid) {
			$final_file_data[] = $file_data[$row];	
			$this_row = $file_data[$row];
			
			if(!$this->db->insert_query($tablename, $this_row)) {					
				 return false;
			}
		}
		
	}
	
	$final_file_data['errors'] = $errors;
	
	return $final_file_data;
  }
  
  /**
   * Function gets all csv data including csv headers
   * 
   * @param $relation; an array consisting of the relationship between the csv fields and the selected database fields
   *		array key is the csv fields and the value is the database fields. ie: $relation[csv_field] = database_field 					 		
   * @return array of data such that row has fields and fields have field values ie: $value = [row][field]
   */
  function get_csv_data($import_data){
		$handle = fopen($this->file_name, "r");
		$row = 0;
		$all_fields = $this->get_csv_header_fields();
		$reserved_csvfield = array();
		
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		
			$num = count($all_fields);
			$preserve = '';
			
			// per row, let's go through each column of data $c
		    for ($c=0; $c < $num; $c++) {
		    	
		    	$reserved = false;
		    	
		    	
		    	// we know how many columns there are hence there should be data set in each column  
		    	// even blank. CSV's from excel don't always generate the right amount of commas so 
		    	// let's  make sure if there is missing a comma, we compensate for it
		    	if(($row != '0') && (!isset($data[$c]))) {
		    		$data[$c] = '';
		    	}
		    	
		    	// let's compile the extra data that wasn't mapped to be put into a specific field
		    	foreach ($import_data['preserve'] as $key => $value) {
		    		
		    		if(($import_data['preserve'][$key] == $all_fields[$c]) && $data[$c] != '') {
		    			$preserve .= $import_data['preserve'][$key] . ': ' . $data[$c] . "\r\n";
		    		}
		    			
		    		if(($row == '0') && (trim($data[$c]) == trim($import_data['preserve'][$key]))) {
		    			$reserved = true;
		    			$reserved_csvfield[$c] = '';
		    		}
		    	}
		   
		    	
		    	// get field names
		    	if($row == '0'){
		  			if(isset($import_data['relation'][$data[$c]])) {
		    			$field_names[] = $import_data['relation'][$data[$c]];
		  			} else {
		  				if(!$reserved) {
		  					$field_names[] = 'blank';
		  				} else {
		  					$field_names[] = $import_data['extra'];
		  				}
		  			}
		    	} else {
		    		$cur_data = $data[$c];

		    		if(!isset($reserved_csvfield[$c]) && $field_names[$c] != 'blank') {
		        		$all_data[$row][$field_names[$c]] = trim(htmlentities($cur_data), '&nbsp;'); 
		    		}
	    		}	
		    }
		    
		    // set our extra data if isset and put it into the given field
	    	if((isset($import_data['extra']) && $import_data['extra'] != 'off') && $row != 0) {
	    		
	    		if(isset($all_data[$row][$import_data['extra']])) {
		    		 $all_data[$row][$import_data['extra']] .= "\r\n" . $preserve;
	    		} else {
	    			$all_data[$row][$import_data['extra']] = $preserve;
	    		}
		    	
	    	}
		    $row++;
		}
		
		fclose($handle);
		
		// set number of rows in file
		$this->num_records = ($row - 1); //die(debug($all_data));
		return $all_data;
	}
  
  
  function import()
  {
    if($this->table_name=="")
      $this->table_name = "temp_".date("d_m_Y_H_i_s");
    
    $this->table_exists = false;
    $this->create_import_table();
    
    if(empty($this->arr_csv_columns))
      $this->get_csv_header_fields();
    
    /* change start. Added in 1.5 version */
    if("" != $this->encoding && "default" != $this->encoding)
      $this->set_encoding();
    /* change end */
    
    if($this->table_exists)
    {
      $sql = "LOAD DATA INFILE '".@mysql_escape_string($this->file_name).
             "' INTO TABLE `".$this->table_name.
             "` FIELDS TERMINATED BY '".@mysql_escape_string($this->field_separate_char).
             "' OPTIONALLY ENCLOSED BY '".@mysql_escape_string($this->field_enclose_char).
             "' ESCAPED BY '".@mysql_escape_string($this->field_escape_char).
             "' ".
             ($this->use_csv_header ? " IGNORE 1 LINES " : "")
             ."(`".implode("`,`", $this->arr_csv_columns)."`)";
      $res = @mysql_query($sql);
      $this->error = mysql_error();
    }
  }
  
  //returns array of CSV file columns
  function get_csv_header_fields()
  {
    $this->arr_csv_columns = array();
    $fpointer = fopen($this->file_name, "r");
    if ($fpointer)
    {
      $arr = fgetcsv($fpointer, 10*1024, $this->field_separate_char);
      if(is_array($arr) && !empty($arr))
      {
        if($this->use_csv_header)
        {
          foreach($arr as $val)
            if(trim($val)!="")
              $this->arr_csv_columns[] = $val;
        }
        else
        {
          $i = 1;
          foreach($arr as $val)
            if(trim($val)!="")
              $this->arr_csv_columns[] = "column".$i++;
        }
      }
      unset($arr);
      fclose($fpointer);
    }
    else
      $this->error = "file cannot be opened: ".(""==$this->file_name ? "[empty]" : @mysql_escape_string($this->file_name));
    return $this->arr_csv_columns;
  }
  
  function create_import_table()
  {
    $sql = "CREATE TABLE IF NOT EXISTS ".$this->table_name." (";
    
    if(empty($this->arr_csv_columns))
      $this->get_csv_header_fields();
    
    if(!empty($this->arr_csv_columns))
    {
      $arr = array();
      for($i=0; $i<sizeof($this->arr_csv_columns); $i++)
          $arr[] = "`".$this->arr_csv_columns[$i]."` TEXT";
      $sql .= implode(",", $arr);
      $sql .= ")";
      $res = @mysql_query($sql);
      $this->error = mysql_error();
      $this->table_exists = ""==mysql_error();
    }
  }
  
  /* change start. Added in 1.5 version */
  //returns recordset with all encoding tables names, supported by your database
  function get_encodings()
  {
    $rez = array();
    $sql = "SHOW CHARACTER SET";
    $res = @mysql_query($sql);
    if(mysql_num_rows($res) > 0)
    {
      while ($row = mysql_fetch_assoc ($res))
      {
        $rez[$row["Charset"]] = ("" != $row["Description"] ? $row["Description"] : $row["Charset"]); //some MySQL databases return empty Description field
      }
    }
    return $rez;
  }
  
  //defines the encoding of the server to parse to file
  function set_encoding($encoding="")
  {
    if("" == $encoding)
      $encoding = $this->encoding;
    $sql = "SET SESSION character_set_database = " . $encoding; //'character_set_database' MySQL server variable is [also] to parse file with rigth encoding
    $res = @mysql_query($sql);
    return mysql_error();
  }
  /* change end */

}
?>