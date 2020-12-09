--TEST--
transactions - nested transactions
--FILE--
<?php

$file = dirname(__FILE__)."/test.tdb";
$tdb = tdb_open($file);

var_dump(tdb_transaction_start($tdb));
var_dump(tdb_transaction_start($tdb));
var_dump(tdb_transaction_start($tdb));

var_dump(tdb_transaction_cancel($tdb));
var_dump(tdb_transaction_recover($tdb));
var_dump(tdb_transaction_commit($tdb));

tdb_close($tdb);
unlink($file);

echo "Done\n";
?>
--EXPECTF--	
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)

Warning: tdb_transaction_commit(): IO Error in %s on line %d
bool(false)
Done
