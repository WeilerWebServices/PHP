--TEST--
tdb_chainunlock() and in-memory TDB 
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_chainunlock());
var_dump(tdb_chainunlock($tdb, "", "", ""));

var_dump(tdb_chainunlock($tdb, ""));
var_dump(tdb_chainunlock($tdb, ""));
var_dump(tdb_chainunlock($tdb, "", true));
var_dump(tdb_chainunlock($tdb, "", true));

var_dump(tdb_error($tdb));

tdb_close($tdb);
var_dump(tdb_chainunlock($tdb, ""));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_chainunlock() expects at least 2 parameters, 0 given in %s on line %d
NULL

Warning: tdb_chainunlock() expects at most 3 parameters, 4 given in %s on line %d
NULL
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)

Warning: tdb_chainunlock(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
