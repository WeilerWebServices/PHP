--TEST--
tdb_append() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_append());
var_dump(tdb_append($tdb, "", "", ""));

var_dump(tdb_append($tdb, "", ""));
var_dump(tdb_append($tdb, "key", "value"));
var_dump(tdb_fetch($tdb, ""));
var_dump(tdb_fetch($tdb, "key"));

var_dump(tdb_append($tdb, "", ""));
var_dump(tdb_append($tdb, "key", "value2"));
var_dump(tdb_fetch($tdb, ""));
var_dump(tdb_fetch($tdb, "key"));

var_dump(tdb_append($tdb, "", "test"));
var_dump(tdb_append($tdb, "key", "value3"));
var_dump(tdb_fetch($tdb, ""));
var_dump(tdb_fetch($tdb, "key"));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_append() expects exactly 3 parameters, 0 given in %s on line %d
NULL

Warning: tdb_append() expects exactly 3 parameters, 4 given in %s on line %d
NULL
bool(true)
bool(true)
string(0) ""
string(5) "value"

Warning: tdb_append(): Out of memory in %s on line %d
bool(false)
bool(true)
string(0) ""
string(11) "valuevalue2"
bool(true)
bool(true)
string(4) "test"
string(17) "valuevalue2value3"
Done
