<?php
/**
 * PrankZap WP Deactivation
 * 
 * (Mostly stolen from WP Super Cache)
 *
 * @package   PrankZapper
 * @author    Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @copyright 2011-2012 Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @license   MIT - http://www.opensource.org/licenses/mit-license.php
 */

function pzap_deactivate() {
	pzap_remove_marker(ABSPATH.'.htaccess', PZAP_MARKER);
}

?>