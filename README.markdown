PrankZapper - Dynamic packer, minifier & gzipper for Javascript & CSS
=====================================================================

Some concepts borrowed from [SmartOptimizer][] by [Ali Farhadi][].

Used packers / minifiers are [CssMin][] and [JavaScriptPacker][] (Nicolas
Martins PHP port of Dean Edwards' [packer][].)

[SmartOptimizer]:   https://github.com/farhadi/SmartOptimizer
[Ali Farhadi]:      http://farhadi.ir/
[CssMin]:           http://code.google.com/p/cssmin/
[JavaScriptPacker]: http://joliclic.free.fr/php/javascript-packer/en/
[packer]:           http://dean.edwards.name/packer/


What is this?
-------------

This script, in essence, finds a specified file, packs it, creates a gzipped
version of it, and saves it in a cache folder.

* If you want to access the raw version of the requested file, add the nocache
  variable to the url (configurable, defaults to "nocache".)
* The gzipped version of the file will only be created if the client indicates
  that it supports gzip.

Disclaimer
==========

This is an ongoing experiment, and it is in continuous alpha stage, so use it
at your own risk. If you have comments or suggestions, contact me through
[email][brego].

Enjoy ;)

[brego]: mailto:brego.dk@gmail.com