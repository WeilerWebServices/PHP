--TEST--
transactions - basic transactions
--FILE--
<?php

$file = dirname(__FILE__)."/test.tdb";
$tdb = tdb_open($file);

var_dump(tdb_transaction_start($tdb));
tdb_insert($tdb, "key", "data");
var_dump(tdb_transaction_cancel($tdb));
var_dump(tdb_fetch($tdb, "key"));

var_dump(tdb_transaction_start($tdb));
tdb_insert($tdb, "key", "data");
var_dump(tdb_transaction_commit($tdb));
var_dump(tdb_fetch($tdb, "key"));

var_dump(tdb_transaction_start($tdb));
tdb_replace($tdb, "key", "new data");
var_dump(tdb_transaction_recover($tdb));
var_dump(tdb_fetch($tdb, "key"));

tdb_close($tdb);
unlink($file);

echo "Done\n";
?>
--EXPECTF--	
bool(true)
bool(true)

Warning: tdb_fetch(): Record does not exist in %s on line %d
bool(false)
bool(true)
bool(true)
string(4) "data"
bool(true)
bool(true)
string(8) "new data"
Done
