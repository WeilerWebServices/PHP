--TEST--
tdb_next_key() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_next_key());
var_dump(tdb_next_key($tdb, 0, 1));
var_dump(tdb_next_key($tdb, ""));

tdb_insert($tdb, "key", "data");
var_dump(tdb_next_key($tdb, "key"));

tdb_insert($tdb, "key2", "data2");
var_dump(tdb_next_key($tdb, "key"));

tdb_insert($tdb, "a", "data3");

for ($key = tdb_first_key($tdb); $key !== false; $key = tdb_next_key($tdb, $key)) {
	var_dump($key);
}

tdb_close($tdb);
var_dump(tdb_next_key($tdb, "key"));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_next_key() expects exactly 2 parameters, 0 given in %s on line %d
NULL

Warning: tdb_next_key() expects exactly 2 parameters, 3 given in %s on line %d
NULL

Warning: tdb_next_key(): Record does not exist in %s on line %d
bool(false)
bool(false)
string(4) "key2"
string(1) "a"
string(3) "key"
string(4) "key2"

Warning: tdb_next_key(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
