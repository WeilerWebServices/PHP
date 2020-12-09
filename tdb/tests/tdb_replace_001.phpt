--TEST--
tdb_replace() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_replace());
var_dump(tdb_replace($tdb, "", "", ""));

var_dump(tdb_replace($tdb, "", ""));
var_dump(tdb_replace($tdb, "key", "value"));

var_dump(tdb_fetch($tdb, ""));
var_dump(tdb_fetch($tdb, "key"));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_replace() expects exactly 3 parameters, 0 given in %s on line %d
NULL

Warning: tdb_replace() expects exactly 3 parameters, 4 given in %s on line %d
NULL
bool(true)
bool(true)
string(0) ""
string(5) "value"
Done
