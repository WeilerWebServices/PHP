--TEST--
tdb_close() basic tests
--FILE--
<?php

$file = dirname(__FILE__)."/test.tdb";

var_dump(tdb_close());
var_dump(tdb_close(1,2));

$fp = fopen(__FILE__, "r");
var_dump(tdb_close($fp));
fclose($fp);
var_dump(tdb_close($fp));

$tdb = tdb_open($file);
var_dump(tdb_close($tdb));
var_dump(tdb_close($tdb));

@unlink($file);

$tdb = tdb_open($file, 0, TDB_INTERNAL);
var_dump(tdb_close($tdb));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_close() expects exactly 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_close() expects exactly 1 parameter, 2 given in %s on line %d
NULL

Warning: tdb_close(): supplied resource is not a valid Trivial DB context resource in %s on line %d
bool(false)

Warning: tdb_close(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
bool(true)

Warning: tdb_close(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
bool(true)
Done
