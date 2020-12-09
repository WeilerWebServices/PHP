--TEST--
tdb_first_key() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_first_key());
var_dump(tdb_first_key($tdb, 0, 1));

var_dump(tdb_first_key($tdb));
tdb_insert($tdb, "key", "data");
var_dump(tdb_first_key($tdb));
tdb_insert($tdb, "key2", "data2");
var_dump(tdb_first_key($tdb));
tdb_insert($tdb, "a", "data3");
var_dump(tdb_first_key($tdb));

tdb_close($tdb);
var_dump(tdb_first_key($tdb));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_first_key() expects exactly 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_first_key() expects exactly 1 parameter, 3 given in %s on line %d
NULL
bool(false)
string(3) "key"
string(3) "key"
string(1) "a"

Warning: tdb_first_key(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
