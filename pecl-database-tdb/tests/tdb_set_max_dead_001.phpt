--TEST--
tdb_set_max_dead() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_set_max_dead());
var_dump(tdb_set_max_dead($tdb, 0, 1));
var_dump(tdb_set_max_dead($tdb, -1));

var_dump(tdb_set_max_dead($tdb, 1));

tdb_insert($tdb, "", "");
tdb_insert($tdb, "1", "1");
tdb_insert($tdb, "2", "2");
tdb_insert($tdb, "3", "3");

var_dump(tdb_delete($tdb, ""));
var_dump(tdb_delete($tdb, "1"));
var_dump(tdb_delete($tdb, "2"));
var_dump(tdb_delete($tdb, "3"));

tdb_close($tdb);

var_dump(tdb_set_max_dead($tdb, 1));

echo "Done\n";
?>
--EXPECTF--	

Warning: tdb_set_max_dead() expects exactly 2 parameters, 0 given in %s on line %d
NULL

Warning: tdb_set_max_dead() expects exactly 2 parameters, 3 given in %s on line %d
NULL

Warning: tdb_set_max_dead(): Maximum number of dead records cannot be less than zero in %s on line %d
bool(false)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)

Warning: tdb_set_max_dead(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
