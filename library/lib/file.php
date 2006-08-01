<?php
/**
 * file.php, part of the seed framework
 *
 * @author mateo murphy
 * @copyright mateo murphy
 * @license The MIT License
 * @package library
 */

/**
 * A class for handling files and directories. 
 * Based on java.io.File
 *
 * @package library
 *
 */
class File {
	var $path;
	
	/**
	 * Constructor
	 *
	 * @param unknown_type $path
	 * @return File
	 */
	function File($path) {
		$this->path = $path;
	}

	/**
	 * Returns the complete path represented by this file
	 *
	 * @return string
	 */
	function get_path() {
		return $this->path;	
		
	}
	
	/**
	 * Returns the base name of the file
	 *
	 * @return string
	 */
	function get_name($without_extension = false) {
		if ($without_extension) {
			return basename($this->path, '.'.$this->get_extension());
		} else {
			return basename($this->path);
		}
	}
	
	/**
	 * Return the extension of the file, not including the dot
	 *
	 * @return string
	 */
	function get_extension() {
		return array_pop(explode('.', basename($this->path)));
	}
	
	/**
	 * Returns the parent path of the file
	 *
	 * @return string
	 */
	function get_parent() {
		return dirname($this->path);
	}
	
	/**
	 * Returns the parent path of the file as a file object
	 *
	 * @return File
	 */
	function get_parent_file() {
		$parent = $this->get_parent();
		
		if (file_exists($parent)) {
			return new File($parent);
		} else {
			return false;
		}
		
	}
	
	/**
	 * Returns true if this file object represents a directory
	 *
	 * @return bool
	 */
	function is_directory() {
		return is_dir($this->path);
		
	}
	
	/**
	 * Returns true if the file object represents a file
	 *
	 * @return bool
	 */
	function is_file() {
		return is_file($this->path);
		
	}
	
	/**
	 * Returns true if the file is hidden. Currently only works on unix based systems.
	 *
	 * @return bool
	 */
	function is_hidden() {
		return substr($this->get_name(), 0, 1) == '.';
		
	}
	
	/**
	 * Returns true if the file exists
	 *
	 * @return bool
	 */
	function exists() {
		return file_exists($this->path);
	}
	
	/**
	 * Returns a list of filenames in an array
	 * 
	 * Name changed from the original java "list" due to the word being reserved in php
	 * 
	 * @return array
	 */
	function list_names($recursive = false, $show_hidden = false) {
		return array_map(array('File', '_path_from_object'), $this->list_files($recursive, $show_hidden));
		
	}
	
	/**
	 * Returns the path of a given object, used by list_names
	 *
	 * @static 
	 * @access private
	 * @param File $file
	 */
	function _path_from_object($file) {
		if (is_a($file, 'File')) {
			return $file->get_path();
		} else {
			return false;	
		}
	}
	
	/**
	 * Returns a list of file objects in an array
	 *
	 * @return array
	 */
	function list_files($recursive = false, $show_hidden = false) {
		if (!$this->is_directory()) {
			return false;
		}
		
		$return = array();
		
		$handle = opendir($this->path); 
		
		while (false !== ($file_name = readdir($handle))) {
			if ($file_name == '.' || $file_name == '..') {
				continue;
			}
			
			$file = new File($this->path.'/'.$file_name);
			
			if (!$show_hidden && $file->is_hidden()) {
				continue;
			}
				
			$return[$this->path.'/'.$file_name] = $file;
			
			if ($recursive) {
				if ($file->is_directory()) {
					$return = array_merge($return, $file->list_files(true, $show_hidden));	
				}
				
			}
			
		}

		ksort($return);

		return $return;
		
		
	}

	/**
	 * Creates the directories needed for the path to exist, if it doesn't
	 *
	 * @return bool
	 */
	function mkdirs() {
		$path_parts = explode('/', $this->path);
		
		
		$make_path = '/';
		
		foreach ($path_parts as $path_part) {
			if ($path_part == '') {
				continue;
			}
			
			$make_path .= $path_part.'/';
			
			if (!file_exists($make_path)) {
				mkdir($make_path);
				
				chmod($make_path, 0777);
			}

		}
		
		return true;
		
	}

	/**
	 * Outputs the file to the browser.
	 *
	 */
	function output_contents() {
		if ($this->exists()) {
			readfile($this->path);
		}	
	}
	
	/**
	 * Returns the contents of the file.
	 *
	 * @return string
	 */
	function get_contents() {
		if ($this->exists()) {
			return file_get_contents($this->path);
		}
		
	}
	
	/**
	 * Returns the mime type of the file
	 *
	 * @return string
	 */
	function get_mime_type() {
	
		$mime_types = array(
		     "ai"=>"application/postscript",
		    "aif"=>"audio/x-aiff",
		   "aifc"=>"audio/x-aiff",
		   "aiff"=>"audio/x-aiff",
		    "asc"=>"text/plain",
		    "asf"=>"video/x-ms-asf",
		    "asx"=>"video/x-ms-asf",
		   "atom"=>"application/atom+xml",
		     "au"=>"audio/basic",
		    "avi"=>"video/x-msvideo",
		  "bcpio"=>"application/x-bcpio",
		    "bin"=>"application/octet-stream",
		    "bz2"=>"application/x-bzip2",
		      "c"=>"text/plain",
		     "cc"=>"text/plain",
		   "ccad"=>"application/clariscad",
		    "cdf"=>"application/x-netcdf",
		  "class"=>"application/octet-stream",
		   "cpio"=>"application/x-cpio",
		    "cpt"=>"application/mac-compactpro",
		    "csh"=>"application/x-csh",
		    "css"=>"text/css",
		    "dcr"=>"application/x-director",
		    "dir"=>"application/x-director",
		    "dms"=>"application/octet-stream",
		    "doc"=>"application/msword",
		    "dot"=>"application/msword",
		    "drw"=>"application/drafting",
		    "dvi"=>"application/x-dvi",
		    "dwf"=>"application/x-dwf",
		    "dwg"=>"application/acad",
		    "dxf"=>"application/dxf",
		    "dxr"=>"application/x-director",
		    "eps"=>"application/postscript",
		    "etx"=>"text/x-setext",
		    "exe"=>"application/octet-stream",
		     "ez"=>"application/andrew-inset",
		      "f"=>"text/plain",
		    "f90"=>"text/plain",
		    "fli"=>"video/x-fli",
		    "gif"=>"image/gif",
		   "gtar"=>"application/x-gtar",
		     "gz"=>"application/x-gzip",
		      "h"=>"text/plain",
		    "hdf"=>"application/x-hdf",
		     "hh"=>"text/plain",
		    "hqx"=>"application/mac-binhex40",
		    "htm"=>"text/html",
		   "html"=>"text/html",
		    "ica"=>"application/x-ica",
		    "ice"=>"x-conference/x-cooltalk",
		    "ico"=>"image/x-icon",
		    "ief"=>"image/ief",
		   "iges"=>"model/iges",
		    "igs"=>"model/iges",
		    "ips"=>"application/x-ipscript",
		    "ipx"=>"application/x-ipix",
		   "jnlp"=>"application/x-java-jnlp-file",
		    "jpe"=>"image/jpeg",
		   "jpeg"=>"image/jpeg",
		    "jpg"=>"image/jpeg",
		     "js"=>"application/x-javascript",
		    "kar"=>"audio/midi",
		  "latex"=>"application/x-latex",
		    "lha"=>"application/octet-stream",
		    "lsp"=>"application/x-lisp",
		    "lzh"=>"application/octet-stream",
		      "m"=>"text/plain",
		    "m3u"=>"audio/playlist",
		    "man"=>"application/x-troff-man",
		    "mda"=>"application/vnd.ms-access",
			"mdb"=>"application/vnd.ms-access",
			"mde"=>"application/vnd.ms-access",
		     "me"=>"application/x-troff-me",
		   "mesh"=>"model/mesh",
		    "mid"=>"audio/midi",
		   "midi"=>"audio/midi",
		    "mif"=>"application/vnd.mif",
		   "mime"=>"www/mime",
		    "mov"=>"video/quicktime",
		  "movie"=>"video/x-sgi-movie",
		    "mp2"=>"audio/mpeg",
		    "mp3"=>"audio/mpeg",
		    "mpa"=>"audio/mpeg",
		    "mpc"=>"application/launcher",
		    "mpe"=>"video/mpeg",
		   "mpeg"=>"video/mpeg",
		    "mpg"=>"video/mpeg",
		   "mpga"=>"audio/mpeg",
		    "mpp"=>"application/vnd.ms-project",
		    "mpt"=>"application/launcher",
		    "mpv"=>"application/launcher",
		    "mpw"=>"application/launcher",
		    "mpx"=>"application/launcher",
		     "ms"=>"application/x-troff-ms",
		    "msh"=>"model/mesh",
		     "nc"=>"application/x-netcdf",
		    "oda"=>"application/oda",
		    "pbm"=>"image/x-portable-bitmap",
		    "pdb"=>"chemical/x-pdb",
		    "pdf"=>"application/pdf",
		    "pfr"=>"application/font-tdpfr",
		    "pgm"=>"image/x-portable-graymap",
		    "pgn"=>"application/x-chess-pgn",
		    "pls"=>"audio/x-scpls",
			 "pm"=>"application/pagemaker",
			"pm5"=>"application/pagemaker",
		    "png"=>"image/png",
		    "pnm"=>"image/x-portable-anymap",
		    "pot"=>"application/mspowerpoint",
		    "ppm"=>"image/x-portable-pixmap",
		    "pps"=>"application/mspowerpoint",
		    "ppt"=>"application/mspowerpoint",
		    "ppz"=>"application/mspowerpoint",
		    "pre"=>"application/x-freelance",
		    "prt"=>"application/pro_eng",
		     "ps"=>"application/postscript",
		    "pt5"=>"application/pagemaker",
		     "qt"=>"video/quicktime",
		    "qtl"=>"application/x-quicktimeplayer",
		     "ra"=>"audio/x-realaudio",
		    "ram"=>"audio/x-pn-realaudio",
		    "ras"=>"image/cmu-raster",
		    "rdf"=>"application/rdf+xml",
		    "rgb"=>"image/x-rgb",
		     "rm"=>"audio/x-pn-realaudio",
		   "roff"=>"application/x-troff",
		    "rpm"=>"audio/x-pn-realaudio-plugin",
		    "rss"=>"application/rss+xml",
		    "rtf"=>"text/rtf",
		    "rtx"=>"text/richtext",
		    "sav"=>"application/x-spss",
		    "sbs"=>"application/x-spss",
		    "scm"=>"application/x-lotusscreencam",
		    "sea"=>"application/stuffit-lite",
		    "set"=>"application/set",
		    "sgm"=>"text/sgml",
		   "sgml"=>"text/sgml",
		     "sh"=>"application/x-sh",
		   "shar"=>"application/x-shar",
		   "silo"=>"model/mesh",
		    "sit"=>"application/x-stuffit",
		    "skd"=>"application/x-koan",
		    "skm"=>"application/x-koan",
		    "skp"=>"application/x-koan",
		    "skt"=>"application/x-koan",
		    "smi"=>"application/smil",
		   "smil"=>"application/smil",
		    "snd"=>"audio/basic",
		    "sol"=>"application/solids",
		    "spl"=>"application/x-futuresplash",
		    "spo"=>"application/x-spss",
		    "spp"=>"application/x-spss",
		    "sps"=>"application/x-spss",
		    "src"=>"application/x-wais-source",
		   "step"=>"application/STEP",
		    "stl"=>"application/SLA",
		    "stp"=>"application/STEP",
		"sv4cpio"=>"application/x-sv4cpio",
		 "sv4crc"=>"application/x-sv4crc",
		 	"svg"=>"application/svg+xml",
		    "swf"=>"application/x-shockwave-flash",
		      "t"=>"application/x-troff",
		    "tar"=>"application/x-tar",
		    "tbk"=>"application/toolbook",
		    "tcl"=>"application/x-tcl",
		    "tex"=>"application/x-tex",
		   "texi"=>"application/x-texinfo",
		 "texinf"=>"application/x-texinfo",
		"texinfo"=>"application/x-texinfo",		 
		    "tgz"=>"application/x-gzip",
		    "tif"=>"image/tiff",
		   "tiff"=>"image/tiff",
		     "tr"=>"application/x-troff",
		    "tsi"=>"audio/TSP-audio",
		    "tsp"=>"application/dsptype",
		    "tsv"=>"text/tab-separated-values",
		    "txt"=>"text/plain",
		    "unv"=>"application/i-deas",
		  "ustar"=>"application/x-ustar",
		    "vcd"=>"application/x-cdlink",
		    "vda"=>"application/vda",
		    "viv"=>"video/vnd.vivo",
		   "vivo"=>"video/vnd.vivo",
		   "vrml"=>"model/vrml",
		    "wav"=>"audio/x-wav",
		    "wks"=>"application/lotus-123",
		     "wp"=>"application/wordperfect",
		    "wp5"=>"application/wordperfect5.1",
		    "wp6"=>"application/wordperfect6.1",		     
		    "wpd"=>"application/wordperfect",
		    "wrl"=>"model/vrml",
		    "xbm"=>"image/x-xbitmap",
		    "xht"=>"application/xhtml+xml",
		  "xhtml"=>"application/xhtml+xml",
		    "xla"=>"application/vnd.ms-excel",
		    "xlb"=>"application/vnd.ms-excel",
		    "xlc"=>"application/vnd.ms-excel",
		    "xll"=>"application/vnd.ms-excel",
		    "xlm"=>"application/vnd.ms-excel",
		    "xls"=>"application/vnd.ms-excel",
		    "xlt"=>"application/vnd.ms-excel",
		    "xlw"=>"application/vnd.ms-excel",
		    "xml"=>"application/xml", // could also be "text/xml"
		    "xsl"=>"application/xml",
		   "xslt"=>"application/xslt+xml", 
		    "xpm"=>"image/x-xpixmap",
		    "xwd"=>"image/x-xwindowdump",
		    "xyz"=>"chemical/x-pdb",
		    "zip"=>"application/zip");
		    
		$extension = $this->get_extension();
		    
		if ($extension && isset($mime_types[$extension])) {
			return $mime_types[$extension];
		} else {
			return "application/octet-stream";
		}
		    
	}
	
}

?>
