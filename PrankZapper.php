<?php
/**
 * Plugin Name: PrankZapper
 * Plugin URI: https://github.com/brego/PrankZapper
 * Description: Static file packer / minifier / gzipper.
 * Version: 0.5
 * Author: Kamil "Brego" Dzieliński <brego.dk@gmail.com>
 * Author URI: http://brego.dk/
 * License: The MIT License (MIT)
 * License URI: http://www.opensource.org/licenses/mit-license.php
 * 
 * This is a WordPress plugin file - it will make PrankZapper work in the
 * WordPress ecosystem.
 * 
 * The activation function will rewrite the WordPress .htaccess file, to point
 * all CSS and JavaScript files to this endpoint. They will be minified
 * compressed and cached.
 * 
 * The deactivation function removes those .htaccess rules.
 * 
 * @package   PrankZapper
 * @author    Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @copyright 2011-2012 Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @license   MIT - http://www.opensource.org/licenses/mit-license.php
 */

define('PZAP_DS',     DIRECTORY_SEPARATOR);
define('PZAP_DIR',    dirname(__FILE__).PZAP_DS);
define('PZAP_THEME',  dirname(dirname(dirname(PZAP_DIR))).PZAP_DS);
define('PZAP_MARKER', 'PrankZapper');


if (function_exists('is_admin') === true) {
	if (is_admin()) {
		require_once PZAP_DIR.'wp/pzap_functions.php';
		require_once PZAP_DIR.'wp/pzap_activate.php';
		require_once PZAP_DIR.'wp/pzap_deactivate.php';
		register_activation_hook(__FILE__,   'pzap_activate');
		register_deactivation_hook(__FILE__, 'pzap_deactivate');
	}
} else {
	require_once PZAP_DIR.'PrankZapper.class.php';
	$PrankZapper = new PrankZapper(array('base_dir' => PZAP_THEME));
}

?>