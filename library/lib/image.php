<?php
/**
 * image.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * Class for manipulating images
 *
 * @package library
 */

class Image  {
	
	/**
	 * The maximum number of pixels in any dimension
	 *
	 * @staticvar int
	 */
	var $MAX_DIMENSION_SIZE = 1000;
	
	/**
	 * The path to the image
	 *
	 * @var string
	 */
	var $path;
	
	/**
	 * The width of the image in pixels
	 *
	 * @var int
	 */
	var $width;
	
	/**
	 * The height of the image in pixels
	 *
	 * @var int
	 */
	var $height;
	
	/**
	 * The type of image, will be on of the IMAGETYPE constants
	 *
	 * @var int
	 */
	var $type;
	
	/**
	 * A handle to the file for image functions
	 *
	 * @var resource
	 */
	var $resource;
	
	/**
	 * Constructor
	 *
	 * Creates an image object, using either an existing image (passed to var1), or a given
	 * set of dimensions ($var1 = width, $var2 = height) and a type
	 * 
	 * @param mixed $var1
	 * @param int $var2
	 * @return Image
	 */
	function Image($var1, $var2 = null, $type = null) {
		if (is_file($var1)) {
			return $this->open($var1);
		} elseif (isset($var2) && isset($type) && is_numeric($var1) && is_numeric($var2)) {
			return $this->create($var1, $var2, $type);
		} else {
			trigger_error("Invalid parameters passed to Image::Image()", E_USER_ERROR);
		}
	}

	/**
	 * Open an existing image
	 *
	 * @param string $path
	 */
	function open($path) {
		$this->path = $path;

		list($this->width, $this->height, $this->type) = getimagesize($path);
		
		$this->resource = $this->get_resource();
		
		if (!$this->resource) {
			trigger_error("Couldn't open file '$path'", E_USER_ERROR);
		}
	}
	
	/**
	 * Create a new image
	 *
	 * @param int $width
	 * @param int $height
	 * @param int $type
	 */
	function create($width, $height, $type = null) {
		
		if ($width > $this->MAX_DIMENSION_SIZE) {
			trigger_error("Width exceeds max width");
			return false;
		}
		
		if ($height > $this->MAX_DIMENSION_SIZE) {
			trigger_error("Height exceeds max height");
			return false;
		}
		
		$this->type = $type;
		$this->width = $width;
		$this->height = $height;
		$this->resource = $this->new_resource($width, $height, $type);

	}
	
	/**
	 * Destructor
	 *
	 */
	function destroy() {
		$this->destroy_resource($this->resource);
	}
	
	/**
	 * Returns the mime type for the image
	 *
	 * @return string
	 */
	function mime_type() {
		return image_type_to_mime_type($this->type);
	}
	
	/**
	 * Returns a resized version of the current image
	 *
	 * @param string $file_name
	 * @param int $width
	 * @param int $height
	 * @return Image
	 */
	function clone_resized($width = null, $height = null) {
		if (isset($width) && !isset($height)) {
			$height = ($width * $this->height) / $this->width;
		} elseif (isset($height) && !isset($width)) {
			$width = ($height * $this->width) / $this->height;			
		} else {
			die('resizing with both dimension not currently supported');
		}
		
		$new_image = new Image($width, $height, $this->type);
				
		$this->copy_to($new_image);
		
		return $new_image;
		
	}
	
	/**
	 * Copies the current image to another, resizing to fit
	 *
	 * @param Image $image
	 */
	
	function copy_to($image) {
		$this->copy_to_resource($image->resource);
		$image->type = $this->type;
	}
	
	/**
	 * Returns the resource of the current image
	 *
	 * @return resource
	 */
	function get_resource() {
		
		if (!$this->type) {
			return false;
		}
		
		switch ($this->type) {
			case IMAGETYPE_GIF:
				return imagecreatefromgif($this->path);
			
			case IMAGETYPE_JPEG:
				return imagecreatefromjpeg($this->path);
				
			case IMAGETYPE_PNG:
				return imagecreatefrompng($this->path);
				
			default:
				trigger_error("'$this->path' is an unsupported image type", E_USER_ERROR);
				return false;
			
		}
		
	}
	
	/**
	 * Saves the current image to file
	 *
	 * @param string $file_name
	 * @return bool
	 */
	function save($file_name = null, $quality = 75) {
		
		if (isset($file_name)) {
			$this->path = $file_name;
		}
		
		if ($this->path == '') {
			trigger_error("Can't save image without a path", E_USER_WARNING);
			return false;
		}
				
		// image* functions sometimes(?) fail if the file doesn't already exist
		if (!file_exists($file_name)) {
			fclose(fopen($file_name, 'w'));
		}
		
		if (!is_writable($file_name)) {
			trigger_error("'$file_name' is not writable, please check permissions", E_USER_ERROR);
		}
		
		switch ($this->type) {
			case IMAGETYPE_GIF:
				return imagegif($this->resource, $this->path);
			
			case IMAGETYPE_JPEG:
				return imagejpeg($this->resource, $this->path, $quality);
				
			case IMAGETYPE_PNG:
				return imagepng($this->resource, $this->path);
				
			default:
				trigger_error("'$this->path' is an unsupported image type", E_USER_ERROR);
				return false;
			
		}		
	}	
	
	/**
	 * Creates an new image resource
	 *
	 * @static 
	 * @param int $width
	 * @param int $height
	 * @return resource
	 */
	function new_resource($width, $height, $type) {

		if (function_exists('imagecreatetruecolor') && $type != IMAGETYPE_GIF) {
			$resource = imagecreatetruecolor($width, $height);
		} 
		
		if (!isset($resource) || !$resource) {
			$resource = imagecreate($width, $height);
		}
	
		return $resource;
		
	}
	
	function copy_to_resource($resource) {
		$width = imagesx($resource);
		$height = imagesy($resource);
		
		if (function_exists('imagecopyresampled')) {
			return imagecopyresampled($resource, $this->resource, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
		} else {
			return imagecopy($resource, $this->resource, 0, 0, 0, 0, $width, $height, $this->width, $this->height);			
		}
	}
	
	function destroy_resource($resource) {
		return imagedestroy($resource);
	}
		
}