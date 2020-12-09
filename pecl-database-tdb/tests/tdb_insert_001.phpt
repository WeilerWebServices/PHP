--TEST--
tdb_insert() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_insert());
var_dump(tdb_insert($tdb, "", "", ""));

var_dump(tdb_insert($tdb, "", ""));
var_dump(tdb_insert($tdb, "key", "value"));
var_dump(tdb_fetch($tdb, ""));
var_dump(tdb_fetch($tdb, "key"));

var_dump(tdb_insert($tdb, "", ""));
var_dump(tdb_insert($tdb, "key", "value3"));
var_dump(tdb_fetch($tdb, ""));
var_dump(tdb_fetch($tdb, "key"));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_insert() expects exactly 3 parameters, 0 given in %s on line %d
NULL

Warning: tdb_insert() expects exactly 3 parameters, 4 given in %s on line %d
NULL
bool(true)
bool(true)
string(0) ""
string(5) "value"

Warning: tdb_insert(): Record exists in %s on line %d
bool(false)

Warning: tdb_insert(): Record exists in %s on line %d
bool(false)
string(0) ""
string(5) "value"
Done
