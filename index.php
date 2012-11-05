<?php
/**
 * PrankZapper - Brego's static file packer / minifier / gzipper.
 *
 * Some concepts borrowed from SmartOptimizer by Ali Farhadi
 * (http://farhadi.ir/).
 * 
 * This file is ment for direct, non-WordPress use:
 * index.php?file=style.css
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

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'PrankZapper.class.php';

$PrankZapper = new PrankZapper();

?>