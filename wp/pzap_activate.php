<?php
/**
 * PrankZap WP Activation
 * 
 * (Mostly stolen from WP Super Cache)
 *
 * @package   PrankZapper
 * @author    Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @copyright 2011-2012 Kamil "Brego" Dzielinski <brego.dk@gmail.com>
 * @license   MIT - http://www.opensource.org/licenses/mit-license.php
 */

function pzap_activate() {
	// AND NOW, FOR SOME EFFING MAGIC!
	if (isset($_SERVER["PHP_DOCUMENT_ROOT"])) {
		$document_root = $_SERVER["PHP_DOCUMENT_ROOT"];
	} else {
		$document_root = $_SERVER["DOCUMENT_ROOT"];
	}
	$content_dir_root = $document_root;

	if (strpos($document_root, '/kunden/') === 0) {
		// http://wordpress.org/support/topic/plugin-wp-super-cache-how-to-get-mod_rewrite-working-on-1and1-shared-hosting?replies=1
		$content_dir_root = substr($content_dir_root, 7);
	}
	
	$home_root = parse_url(get_bloginfo('url'));
	$home_root = isset($home_root['path']) ? trailingslashit($home_root['path']) : '/';
	$inst_root = str_replace('//', '/', '/'.trailingslashit(str_replace($content_dir_root, '', str_replace('\\', '/', WP_CONTENT_DIR))));

	$condition_rules = array();
	if (substr(get_option('permalink_structure'), -1) == '/') {
		$condition_rules[] = "  RewriteCond %{REQUEST_URI} !^.*//.*$";
	}
	$condition_rules[] = "  RewriteCond %{REQUEST_METHOD} !POST";
	$condition_rules[] = "  RewriteCond %{QUERY_STRING} !.*=.*";
	$condition_rules[] = "  RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$";
	$condition_rules[] = "  RewriteCond %{HTTP:X-Wap-Profile} !^[a-z0-9\\\"]+ [NC]";
	$condition_rules[] = "  RewriteCond %{HTTP:Profile} !^[a-z0-9\\\"]+ [NC]";

	$condition_rules   = implode("\n", $condition_rules);
	
	$rules = array();
	
	$rules[] = "<IfModule mod_rewrite.c>";
	$rules[] = "  RewriteEngine On";
	$rules[] = "  RewriteBase $home_root"; // props Chris Messina
	if (isset($wp_cache_disable_utf8) == false || $wp_cache_disable_utf8 == 0) {
		$charset = get_option('blog_charset');
		$charset = ($charset == '') ? 'UTF-8' : $charset;
		$rules[] = "  AddDefaultCharset {$charset}";
	}
	$rules[] = $condition_rules;
	$rules[] = "  RewriteRule ^(.+)\.(js|css)$ \"{$inst_root}plugins/PrankZapper/PrankZapper.php?file=$1.$2\" [NC,L,QSA]";
	$rules[] = "</IfModule>";

	$wp_rules = extract_from_markers(ABSPATH.'.htaccess', 'WordPress');
	pzap_remove_marker(ABSPATH.'.htaccess', 'WordPress');    // Need to go on top ;)

	if (is_plugin_active('wp-super-cache/wp-cache.php')) {
		$wpsc_rules = extract_from_markers(ABSPATH.'.htaccess', 'WPSuperCache');
		pzap_remove_marker(ABSPATH.'.htaccess', 'WPSuperCache'); // Need to go on top ;)
	}

	insert_with_markers(ABSPATH.'.htaccess', PZAP_MARKER,    $rules);
	if (isset($wpsc_rules)) {
		insert_with_markers(ABSPATH.'.htaccess', 'WPSuperCache', $wpsc_rules);
	}
	insert_with_markers(ABSPATH.'.htaccess', 'WordPress',    $wp_rules);
}

?>