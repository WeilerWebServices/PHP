--TEST--
tdb_unlock() and in-memory TDB 
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_unlock());
var_dump(tdb_unlock($tdb, "", ""));

var_dump(tdb_unlock($tdb));
var_dump(tdb_unlock($tdb));
var_dump(tdb_unlock($tdb, true));
var_dump(tdb_unlock($tdb, true));

var_dump(tdb_error($tdb));

tdb_close($tdb);
var_dump(tdb_unlock($tdb));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_unlock() expects at least 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_unlock() expects at most 2 parameters, 3 given in %s on line %d
NULL

Warning: tdb_unlock(): Locking error in %s on line %d
bool(false)

Warning: tdb_unlock(): Locking error in %s on line %d
bool(false)

Warning: tdb_unlock(): Locking error in %s on line %d
bool(false)

Warning: tdb_unlock(): Locking error in %s on line %d
bool(false)
string(13) "Locking error"

Warning: tdb_unlock(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
