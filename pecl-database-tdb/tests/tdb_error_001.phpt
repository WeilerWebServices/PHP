--TEST--
tdb_error() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_error());
var_dump(tdb_error($tdb, ""));

var_dump(tdb_error($tdb));

tdb_transaction_start($tdb);
var_dump(tdb_error($tdb));

tdb_close($tdb);
var_dump(tdb_error($tdb));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_error() expects exactly 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_error() expects exactly 1 parameter, 2 given in %s on line %d
NULL
bool(false)

Warning: tdb_transaction_start(): Invalid parameter in %s on line %d
string(17) "Invalid parameter"

Warning: tdb_error(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
