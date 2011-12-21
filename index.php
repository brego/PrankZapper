<?php
/**
 * PrankZapper - Brego's static file packer / minifier / gzipper.
 *
 * Some concepts borrowed from SmartOptimizer by Ali Farhadi
 * (http://farhadi.ir/).
 * 
 * Used packers / minifiers are CssMin (http://code.google.com/p/cssmin/) and
 * JavaScriptPacker (Nicolas Martins PHP port of Dean Edwards packer -
 * http://dean.edwards.name/packer/).
 *
 *
 * @author    Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @copyright 2011 Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @license   MIT - http://www.opensource.org/licenses/mit-license.php
 */

// Functions
function gmt_date($time = null) {
	if (is_null($time)) {
		$time = time();
	}
	return gmdate('D, d M Y H:i:s', $time).' GMT';
}

// Config
$base_dir           = '../';
$charset            = 'utf-8';
$cache_directory    = 'cache/';
$cache_prefix       = 'cache_';
$minifiers          = array('js' => 'minifiers/javascriptpacker.class.php', 'css' => 'minifiers/cssmin.php');

// Vars
$query              = '';
$file_name          = '';
$file_full_name     = '';
$directory_name     = '';
$file_type          = '';
$file_mtime         = null;
$cached_file_name   = '';
$cached_file_exists = false;
$cached_file_mtime  = null;
$mime_types         = array('js' => 'text/javascript', 'css' => 'text/css');
$gzip_supported     = false;
$generate_file      = false;
$out_mtime          = null;
$out_content        = null;
$out_content_header = null;
$file_handle        = null;

$accept_encoding    = null;
$is_accept_encoding = isset($_SERVER['HTTP_ACCEPT_ENCODING']);
$modified_since     = null;
$is_modified_since  = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']);

if ($is_accept_encoding) {
	$accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
}

if ($is_modified_since) {
	$modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
}

// Getting the query
$query = $_GET['file'];
$query = explode('/', $query);

// Setting the file name
$file_name = array_pop($query);
// Setting the directory name
$directory_name = $base_dir.implode('/', $query).'/';

$file_full_name = $directory_name.$file_name;

// Checking if the file actually exists
if (file_exists($file_full_name) === false) {
	die('File doesn\'t exist.');
}

// Getting the file type
$file_type = array_pop(explode('.', $file_name));

// Sending the right mime-type
header('Content-Type: '.$mime_types[$file_type].'; charset='.$charset);

// Get the name of the cache file
$cached_file_name = $cache_directory.$cache_prefix.md5($directory_name.$file_name).'.'.$file_type;

// Do we support gzip?
if (function_exists('gzencode') &&
	$is_accept_encoding &&
	in_array('gzip', array_map('trim', explode(',', $accept_encoding)))) {
	$gzip_supported = true;
	$cached_file_name .= '.gz';
}

if (file_exists($cached_file_name)) {
	$cached_file_exists = true;
	$cached_file_mtime  = filemtime($cached_file_name);
}

$file_mtime = filemtime($file_full_name);

// Do we need to generate a new cache file?
if ((!$is_modified_since || $modified_since != gmt_date($file_mtime)) ||
	(!$cached_file_exists || ($file_mtime > $cached_file_mtime))) {
	$generate_file = true;
}

// What's the mtime?
if ($generate_file === false && $cached_file_exists) {
	$out_mtime = $cached_file_mtime;
} else {
	$out_mtime = time();
}
$out_mtime = gmt_date($out_mtime);

if (!$is_modified_since || $modified_since != $out_mtime) {
	header('Last-Modified: '.$out_mtime);
	header('Cache-Control: must-revalidate');
	header('Vary: Accept-Encoding');
	if ($gzip_supported) {
		header('Content-Encoding: gzip');
	}

	if ($generate_file) {

		$out_content = file_get_contents($file_full_name);
		
		$out_content_header = '';
		if (substr($out_content, 0, 2) == '/*') {
			$out_content_header = substr($out_content, strpos($out_content, '/*'), strpos($out_content, '*/')+2)."\n";
		}

		if ($file_type === 'js' && file_exists($minifiers['js'])) {
			require_once $minifiers['js'];
			$packer      = new JavaScriptPacker($out_content);
			$out_content = $packer->pack();
		} elseif ($file_type === 'css' && file_exists($minifiers['css'])) {
			require_once $minifiers['css'];
			$out_content = CssMin::minify($out_content);
		}
		$out_content = $out_content_header.$out_content;
		if ($gzip_supported) {
			$out_content = gzencode($out_content, 9);
		}
		$file_handle = fopen($cached_file_name, 'w');
		fwrite($file_handle, $out_content);
		fclose($file_handle);

		header('Content-Length: '.strlen($out_content));		
		echo $out_content;
		
	} else {
		header('Content-Length: '.filesize($cached_file_name));
		readfile($cached_file_name);
	}
} else {
	header('HTTP/1.0 304 Not Modified');
	exit();
}

?>