<?php
/**
 * PrankZap WP Functions
 * 
 * (Mostly stolen from WP Super Cache)
 *
 * @package   PrankZapper
 * @author    Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @copyright 2011-2012 Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @license   MIT - http://www.opensource.org/licenses/mit-license.php
 */

function pzap_remove_marker($filename, $marker) {
	if (!file_exists($filename) || is_writeable_ACLSafe($filename)) {
		if (!file_exists($filename)) {
			return '';
		} else {
			$markerdata = explode("\n", implode('', file($filename)));
		}
		$file = fopen($filename, 'w');
		$foundit = false;
		if ($markerdata) {
			$state = true;
			foreach ($markerdata as $n => $markerline) {
				if (strpos($markerline, '# BEGIN ' . $marker) !== false) {
					$state = false;
				}
				if ($state) {
					if ($n + 1 < count($markerdata)) {
						fwrite($file, "{$markerline}\n");
					} else {
						fwrite($file, "{$markerline}");
					}
				}
				if (strpos($markerline, '# END ' . $marker) !== false) {
					$state = true;
				}
			}
		}
		return true;
	} else {
		return false;
	}
}

// from legolas558 d0t users dot sf dot net at http://www.php.net/is_writable
// PHP's is_writable does not work with Win32 NTFS
function pzap_is_writeable_ACLSafe($path) {
	if ($path{strlen($path)-1}=='/') {
		return is_writeable_ACLSafe($path.uniqid(mt_rand()).'.tmp');
	} elseif (is_dir($path)) {
		return is_writeable_ACLSafe($path.'/'.uniqid(mt_rand()).'.tmp');
	}
	$rm = file_exists($path);
	$f  = @fopen($path, 'a');
	if ($f === false) {
		return false;
	}
	fclose($f);
	if (!$rm) {
		unlink($path);
	}
	return true;
}

?>