--TEST--
tdb_exists() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_exists());
var_dump(tdb_exists($tdb, "", "", ""));
var_dump(tdb_exists($tdb, -1));
var_dump(tdb_exists($tdb, ""));

tdb_insert($tdb, "", "");
var_dump(tdb_exists($tdb, ""));

tdb_insert($tdb, "te\0st", "");
var_dump(tdb_exists($tdb, "te\0st"));

tdb_close($tdb);
var_dump(tdb_exists($tdb, "test"));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_exists() expects exactly 2 parameters, 0 given in %s on line %d
NULL

Warning: tdb_exists() expects exactly 2 parameters, 4 given in %s on line %d
NULL
bool(false)
bool(false)
bool(true)
bool(true)

Warning: tdb_exists(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
