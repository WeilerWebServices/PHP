--TEST--
transactions funcs tests - wrong arguments
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_transaction_start());
var_dump(tdb_transaction_start($tdb, 0, 1));
var_dump(tdb_transaction_start($tdb, ""));

var_dump(tdb_transaction_cancel());
var_dump(tdb_transaction_cancel($tdb, 0, 1));
var_dump(tdb_transaction_cancel($tdb, ""));

var_dump(tdb_transaction_commit());
var_dump(tdb_transaction_commit($tdb, 0, 1));
var_dump(tdb_transaction_commit($tdb, ""));

var_dump(tdb_transaction_recover());
var_dump(tdb_transaction_recover($tdb, 0, 1));
var_dump(tdb_transaction_recover($tdb, ""));

tdb_close($tdb);
var_dump(tdb_transaction_start($tdb));
var_dump(tdb_transaction_cancel($tdb));
var_dump(tdb_transaction_commit($tdb));
var_dump(tdb_transaction_recover($tdb));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_transaction_start() expects exactly 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_transaction_start() expects exactly 1 parameter, 3 given in %s on line %d
NULL

Warning: tdb_transaction_start() expects exactly 1 parameter, 2 given in %s on line %d
NULL

Warning: tdb_transaction_cancel() expects exactly 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_transaction_cancel() expects exactly 1 parameter, 3 given in %s on line %d
NULL

Warning: tdb_transaction_cancel() expects exactly 1 parameter, 2 given in %s on line %d
NULL

Warning: tdb_transaction_commit() expects exactly 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_transaction_commit() expects exactly 1 parameter, 3 given in %s on line %d
NULL

Warning: tdb_transaction_commit() expects exactly 1 parameter, 2 given in %s on line %d
NULL

Warning: tdb_transaction_recover() expects exactly 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_transaction_recover() expects exactly 1 parameter, 3 given in %s on line %d
NULL

Warning: tdb_transaction_recover() expects exactly 1 parameter, 2 given in %s on line %d
NULL

Warning: tdb_transaction_start(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)

Warning: tdb_transaction_cancel(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)

Warning: tdb_transaction_commit(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)

Warning: tdb_transaction_recover(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
