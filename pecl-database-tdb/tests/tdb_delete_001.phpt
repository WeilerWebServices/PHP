--TEST--
tdb_delete() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_delete());
var_dump(tdb_delete($tdb, "", "", ""));
var_dump(tdb_delete($tdb, -1));
var_dump(tdb_delete($tdb, ""));

tdb_insert($tdb, "", "");
var_dump(tdb_fetch($tdb, ""));
var_dump(tdb_delete($tdb, ""));
var_dump(tdb_fetch($tdb, ""));

tdb_insert($tdb, "te\0st", "value");
var_dump(tdb_fetch($tdb, "te\0st"));
var_dump(tdb_delete($tdb, "te\0st"));
var_dump(tdb_fetch($tdb, "te\0st"));

tdb_close($tdb);
var_dump(tdb_delete($tdb, "test"));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_delete() expects exactly 2 parameters, 0 given in %s on line %d
NULL

Warning: tdb_delete() expects exactly 2 parameters, 4 given in %s on line %d
NULL

Warning: tdb_delete(): Record does not exist in %s on line %d
bool(false)

Warning: tdb_delete(): Record does not exist in %s on line %d
bool(false)
string(0) ""
bool(true)

Warning: tdb_fetch(): Record does not exist in %s on line %d
bool(false)
string(5) "value"
bool(true)

Warning: tdb_fetch(): Record does not exist in %s on line %d
bool(false)

Warning: tdb_delete(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
