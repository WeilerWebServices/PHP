--TEST--
transactions - basic tests
--FILE--
<?php

$file = dirname(__FILE__)."/test.tdb";
$tdb = tdb_open($file);

var_dump(tdb_transaction_cancel($tdb));
var_dump(tdb_transaction_recover($tdb));
var_dump(tdb_transaction_commit($tdb));

var_dump(tdb_transaction_start($tdb));
var_dump(tdb_transaction_start($tdb));
var_dump(tdb_transaction_cancel($tdb));
var_dump(tdb_transaction_cancel($tdb));

var_dump(tdb_transaction_start($tdb));
var_dump(tdb_transaction_cancel($tdb));

var_dump(tdb_transaction_start($tdb));
var_dump(tdb_transaction_recover($tdb));

var_dump(tdb_transaction_start($tdb));
var_dump(tdb_transaction_commit($tdb));

tdb_close($tdb);
unlink($file);

echo "Done\n";
?>
--EXPECTF--	
bool(false)
bool(true)
bool(false)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
Done
