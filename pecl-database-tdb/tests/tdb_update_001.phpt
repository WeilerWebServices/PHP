--TEST--
tdb_update() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_update());
var_dump(tdb_update($tdb, "", "", ""));

var_dump(tdb_update($tdb, "", ""));
var_dump(tdb_update($tdb, "key", "value"));
var_dump(tdb_fetch($tdb, ""));
var_dump(tdb_fetch($tdb, "key"));

var_dump(tdb_insert($tdb, "", ""));
var_dump(tdb_insert($tdb, "key", "value2"));
var_dump(tdb_fetch($tdb, ""));
var_dump(tdb_fetch($tdb, "key"));

var_dump(tdb_update($tdb, "", ""));
var_dump(tdb_update($tdb, "key", "value3"));
var_dump(tdb_fetch($tdb, ""));
var_dump(tdb_fetch($tdb, "key"));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_update() expects exactly 3 parameters, 0 given in %s on line %d
NULL

Warning: tdb_update() expects exactly 3 parameters, 4 given in %s on line %d
NULL

Warning: tdb_update(): Record does not exist in %s on line %d
bool(false)

Warning: tdb_update(): Record does not exist in %s on line %d
bool(false)

Warning: tdb_fetch(): Record does not exist in %s on line %d
bool(false)

Warning: tdb_fetch(): Record does not exist in %s on line %d
bool(false)
bool(true)
bool(true)
string(0) ""
string(6) "value2"
bool(true)
bool(true)
string(0) ""
string(6) "value3"
Done
