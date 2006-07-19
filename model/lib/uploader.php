<?php

class Uploader {
	
	/**
	 * @var Model
	 */
	var $model;
	
	/**
	 * @param Model $model
	 * @return Uploader
	 */
	function Uploader(& $model) {
		$this->model = & $model;
	}
	
	/**
	 * @param array $data
	 */
	function handle_uploads($data) {
		
		$errors = array();
		
		foreach($data as $field => $file) {
			$error = $this->handle_upload($field, $file);
			
			if ($error) {
				$errors[] = $error;	
			}
			
		}
		
		if (count($errors)) {
			return $errors;
		} else {
			return false;
		}
	}
	
	/**
	 * @param string $field
	 * @param string $file
	 */
	function handle_upload($field, $file) {
		// return an error message, if the upload failed
		if($file['tmp_name'] == '') {
			if ($file['name'] && $file['error']) {
				return file_upload_error_string($file['error'], $file['name']);
			} else {
				return false;
			}
		}

		$filename = $file['name'];
		
		// get the upload path for the field
		$upload_path = $this->model->upload_path($field);
		
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
		if ($new_file->exists() && defined('RENAME_DUPLICATE_UPLOADS') && RENAME_DUPLICATE_UPLOADS) {
			
			$x = 1;
			
			while (file_exists($new_path)) {	
				$new_path = $upload_path.$new_file->get_name(true)."_".$x;
				
				if ($new_file->get_extension()) {
					$new_path .= '.'.$new_file->get_extension();
				}
				
				$x ++;
			}
			
			$filename = basename($new_path);
			
			$new_path = $new_path;
		} 
		
		// try to move the upload file
		if (move_uploaded_file($file['tmp_name'], $new_path)) {
			// succeeded
			$this->model->set($field, $this->model->upload_file_name($filename, $field));
			chmod($new_path, 0777);
			
		} else {
			// failed
			trigger_error("Upload failed", E_USER_ERROR);
		}		
		
		return false;
	}	
	
}

?>