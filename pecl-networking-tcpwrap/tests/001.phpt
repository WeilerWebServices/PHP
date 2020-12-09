--TEST--
Check for tcpwrap presence
--SKIPIF--
<?php if (!extension_loaded("tcpwrap")) print "skip"; ?>
--POST--
--GET--
--INI--
--FILE--
<?php 
echo "tcpwrap extension is available";
?>
--EXPECT--
tcpwrap extension is available
