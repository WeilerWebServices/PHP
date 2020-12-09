--TEST--
transactions - in-memory TDB
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_transaction_cancel($tdb));
var_dump(tdb_transaction_recover($tdb));
var_dump(tdb_transaction_commit($tdb));

var_dump(tdb_transaction_start($tdb));
var_dump(tdb_transaction_start($tdb));

echo "Done\n";
?>
--EXPECTF--	
bool(false)
bool(true)
bool(false)

Warning: tdb_transaction_start(): Invalid parameter in %s on line %d
bool(false)

Warning: tdb_transaction_start(): Invalid parameter in %s on line %d
bool(false)
Done
