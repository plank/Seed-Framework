<?php

/**
 * The uploader works in conjunction with the model to upload files onto the file system
 *
 */
class AbstractUploader {
	
	/**
	 * @var bool
	 */
	var $rename_duplicates = false;
	
	/**
	 * @var array
	 */
	var $errors;
	
	/**
	 * @var mixed
	 */
	var $upload_path;
	
	/**
	 * @param Model $model
	 * @return Uploader
	 */
	function Uploader() {
		$this->rename_duplicates = defined('RENAME_DUPLICATE_UPLOADS') && RENAME_DUPLICATE_UPLOADS;
		
	}
	
	function get_upload_path($field) {
		if (is_string($this->upload_path)) {
			return $this->upload_path;	
		} 
		
		if (is_array($this->upload_path)) {
			return $this->upload_path;	
		} 
		
		trigger_error("Uploadpath not set");
		
		return false;
		
	}
	
	function update_model($field, $filename) {
		return true;
		
	}
	
	/**
	 * @param array $data An array of file info
	 * @return mixed Returns an array containing any errors that occured, or false if there weren't any
	 */
	function handle_uploads($data) {
		
		$this->errors = array();
		
		foreach($data as $field => $file) {
			$error = $this->handle_upload($field, $file);
			
			if ($error) {
				$this->errors[] = $error;	
			}
			
		}
		
		if (count($this->errors)) {
			return $this->errors;
		} else {
			return false;
		}
	}
	
	/**
	 * @param string $field
	 * @param array $file
	 */
	function handle_upload($field, $file) {
		if ($errors = $this->validate_file_info($file) !== true) {
			return $errors;
		}

		$filename = $file['name'];
		
		// get the upload path for the field
		$upload_path = $this->get_upload_path($field);
		
		if (is_array($upload_path)) {
			$backup_paths = $upload_path;
			$upload_path = array_shift($backup_paths);	
		}		
		
		// make the upload directory if it doesn't exist
		$new_file = new File($upload_path);
		
		if (!$new_file->exists()) {
			$new_file->mkdirs();
		}
		
		// get the path for the new file
		$new_path = $upload_path.$file['name'];
		
		$new_file = new File($new_path);
		
		// if there's already a file by that name and we're renaming duplicates,
		// modify the name until we find a unique one.
		if ($new_file->exists() && $this->rename_duplicates) {
			
			$x = 1;
			
			while (file_exists($new_path)) {	
				$new_path = $upload_path.$new_file->get_name(true)."_".$x;
				
				if ($new_file->get_extension()) {
					$new_path .= '.'.$new_file->get_extension();
				}
				
				$x ++;
			}
			
			$filename = basename($new_path);
			
		} 
		
		// try to move the upload file
		if ($this->move_file($file['tmp_name'], $new_path)) {
			// succeeded
			$this->update_model($field, $filename);
			
			chmod($new_path, 0777);
			
			if (isset($backup_paths)) {
				foreach($backup_paths as $backup_path) {
					copy($new_path, str_replace($upload_path, $backup_path, $new_path));
				}	
			}
			
		} else {
			// failed
			trigger_error("Upload failed", E_USER_ERROR);
		}		
		
		return false;
	}	
	
	function move_file($source, $destination) {
		return copy($source, $destination);
	}

	function error_string($error_value, $file_name) {
		
		$error_strings = array(	
			UPLOAD_ERR_OK => "File uploaded with success.",
			UPLOAD_ERR_INI_SIZE => "The uploaded file '%s' exceeds the upload_max_filesize directive in php.ini.",
			UPLOAD_ERR_FORM_SIZE => "The uploaded file '%s' exceeds the MAX_FILE_SIZE directive specified in the HTML form.",
			UPLOAD_ERR_PARTIAL => "The uploaded file '%s' was only partially uploaded.",
			UPLOAD_ERR_NO_FILE => "No file was uploaded.",
			UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
			UPLOAD_ERR_CANT_WRITE => "Failed to write the file '%s' to disk."
		);
		
		return sprintf($error_strings[$error_value], $file_name);
		
	}	
	
	
	function validate_file_info($file) {
		// return an error message, if the upload failed
		if($file['tmp_name'] == '') {
			if ($file['name'] && $file['error']) {
				return $this->error_string($file['error'], $file['name']);
			} else {
				return false;
			}
		}

		return true;
			
	}	
	
}

/**
 * The uploader works in conjunction with the model to upload files onto the file system
 *
 */

class Uploader extends AbstractUploader {
	
	/**
	 * @var Model
	 */
	var $model;
	
	function Uploader(& $model) {
		$this->model = & $model;
		
		$this->rename_duplicates = defined('RENAME_DUPLICATE_UPLOADS') && RENAME_DUPLICATE_UPLOADS;
		
	}
	
	function get_upload_path($field) {
		return  $this->model->upload_path($field);
	}
	
	function update_model($field, $filename) {
		$this->model->set($field, $this->model->upload_file_name($filename, $field));		
	}
	
	function move_file($source, $destination) {
		die(debug($source, $destination));
		
		return move_uploaded_file($source, $destination);
	}	
}

?>