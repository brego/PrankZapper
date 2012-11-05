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
 * @package   PrankZapper
 * @author    Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @copyright 2011-2012 Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @license   MIT - http://www.opensource.org/licenses/mit-license.php
 */

class PrankZapper
{
	private $config = array(
		'base_dir'         => '../',
		'charset'          => 'utf-8',
		'cache_directory'  => 'cache/',
		'cache_prefix'     => 'cache_',
		'minifiers'        => array('js' => 'minifiers/javascriptpacker.class.php', 'css' => 'minifiers/cssmin.php'),
		'var_file'         => 'file',
		'var_nocache'      => 'nocache',
		'nocache_link'     => true,
		'ignore_min_files' => true
	);
	private $file_name          = '';
	private $file_full_name     = '';
	private $directory_name     = '';
	private $file_type          = '';
	private $file_mtime         = null;
	private $cached_file_name   = '';
	private $cached_file_exists = false;
	private $cached_file_mtime  = null;
	private $mime_types         = array('js' => 'text/javascript', 'css' => 'text/css');
	private $gzip_supported     = false;
	private $generate_file      = false;
	private $out_mtime          = null;
	private $accept_encoding    = null;
	private $modified_since     = null;
	private $is_accept_encoding = false;
	private $is_modified_since  = false;
	private $is_min_file        = false;

	public function __construct($config = array()) {
		$this->config = array_merge($this->config, $config);

		if (isset($_GET[$this->config['var_file']]) === false) {
			die('File variable not set.');
		}

		$this->setup_http_vars();
		$this->setup_file();
		header('Content-Type: '.$this->mime_types[$this->file_type].'; charset='.$this->config['charset']);
		$this->check_nocache();
		$this->check_gzip();
		$this->check_cached_file();
		$this->file_mtime = filemtime($this->file_full_name);

		if ((!$this->is_modified_since || $this->modified_since != $this->gmt_date($this->file_mtime)) ||
			(!$this->cached_file_exists || ($this->file_mtime > $this->cached_file_mtime))) {
			$this->generate_file = true;
		}

		$this->setup_out_mtime();

		// if (!$this->is_modified_since || $this->modified_since != $this->out_mtime) {
		// if (($this->is_modified_since === true) && ($this->modified_since !== $this->out_mtime)) {
		if (!$this->is_modified_since || $this->modified_since != $this->out_mtime) {
			header('Last-Modified: '.$this->out_mtime);
			header('Cache-Control: must-revalidate');
			header('Vary: Accept-Encoding');
			if ($this->gzip_supported) {
				header('Content-Encoding: gzip');
			}

			if ($this->is_min_file) {
				$out_content = file_get_contents($this->file_full_name);
				$out_content = $this->compress_content($out_content);
				$this->save_cache_file($out_content);
				header('Content-Length: '.strlen($out_content));
				echo $out_content;
				die();
			} elseif ($this->generate_file) {
				$out_content = file_get_contents($this->file_full_name);

				$out_content_header = '';
				if (substr($out_content, 0, 2) == '/*') {
					$out_content_header = substr($out_content, strpos($out_content, '/*'), strpos($out_content, '*/')+2)."\n";
				}

				$out_content = $this->minify_content($out_content);
				$out_content = $out_content_header.$out_content;

				$preamble   = array();
				$preamble[] = "/* Compressed by PrankZapper: https://github.com/brego/PrankZapper */";
				if ($this->config['nocache_link']) {
					$preamble[] = "/* To access uncompressed version of this file, add '?".$this->config['var_nocache']."' to the url */";
				}

				$out_content = implode("\n", $preamble)."\n".$out_content;

				$out_content = $this->compress_content($out_content);
				$this->save_cache_file($out_content);
				header('Content-Length: '.strlen($out_content));
				echo $out_content;
				die();
			} else {
				header('Content-Length: '.filesize($this->cached_file_name));
				readfile($this->cached_file_name);
				die();
			}
		} else {
			header('HTTP/1.0 304 Not Modified');
			die();
		}
	}

	private function setup_http_vars() {
		$this->is_accept_encoding = isset($_SERVER['HTTP_ACCEPT_ENCODING']);
		if ($this->is_accept_encoding) {
			$this->accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
		}
		$this->is_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']);
		if ($this->is_modified_since) {
			$this->modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		}
	}

	private function setup_file() {
		$query = $_GET[$this->config['var_file']];
		$query = explode('/', $query);

		$this->file_name      = array_pop($query);
		$this->directory_name = $this->config['base_dir'].implode('/', $query).'/';
		$this->file_full_name = $this->directory_name.$this->file_name;

		if (file_exists($this->file_full_name) === false) {
			die('File doesn\'t exist: '.$this->file_full_name);
		}

		$this->file_type = array_pop(explode('.', $this->file_name));

		if (strpos($this->file_name, '.min.'.$this->file_type) !== false) {
			$this->is_min_file = true;
		}

		$this->cached_file_name = $this->config['cache_directory'].$this->config['cache_prefix'].md5($this->file_full_name).'.'.$this->file_type;
	}

	private function check_nocache() {
		if (isset($_GET[$this->config['var_nocache']])) {
			header('Content-Length: '.filesize($this->file_full_name));
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			readfile($this->file_full_name);
			die();
		}
	}

	private function check_gzip() {
		if (function_exists('gzencode') &&
			$this->is_accept_encoding &&
			in_array('gzip', array_map('trim', explode(',', $this->accept_encoding)))) {
			$this->gzip_supported    = true;
			$this->cached_file_name .= '.gz';
		}
	}

	private function check_cached_file() {
		if (file_exists($this->cached_file_name)) {
			$this->cached_file_exists = true;
			$this->cached_file_mtime  = filemtime($this->cached_file_name);
		}
	}

	private function setup_out_mtime() {
		if ($this->generate_file === false && $this->cached_file_exists) {
			$out_mtime = $this->cached_file_mtime;
		} else {
			$out_mtime = time();
		}
		$this->out_mtime = $this->gmt_date($out_mtime);
	}

	private function minify_content($out_content) {
		if ($this->file_type === 'js' && file_exists($this->config['minifiers']['js'])) {
			require_once dirname(__FILE__).DIRECTORY_SEPARATOR.$this->config['minifiers']['js'];
			$packer      = new JavaScriptPacker($out_content);
			$out_content = $packer->pack();
		} elseif ($this->file_type === 'css' && file_exists($this->config['minifiers']['css'])) {
			require_once dirname(__FILE__).DIRECTORY_SEPARATOR.$this->config['minifiers']['css'];
			$out_content = CssMin::minify($out_content);
		}
		return $out_content;
	}

	private function compress_content($out_content) {
		if ($this->gzip_supported) {
			$out_content = gzencode($out_content, 9);
		}
		return $out_content;
	}

	private function save_cache_file($out_content) {
		$file_handle = fopen($this->cached_file_name, 'w');
		fwrite($file_handle, $out_content);
		fclose($file_handle);
	}

	private function gmt_date($time) {
		return gmdate('D, d M Y H:i:s', $time).' GMT';
	}
}

?>