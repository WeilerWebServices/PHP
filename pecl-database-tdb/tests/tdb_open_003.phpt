--TEST--
tdb_open() in-memory test
--FILE--
<?php

$file = dirname(__FILE__)."/test.tdb";

var_dump($tdb = tdb_open($file, 0, TDB_INTERNAL));
var_dump(tdb_close($tdb));
var_dump(unlink($file));

echo "Done\n";
?>
--EXPECTF--	
resource(%d) of type (Trivial DB context)
bool(true)

Warning: unlink(%stest.tdb): No such file or directory in %s on line %d
bool(false)
Done
