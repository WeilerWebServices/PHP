--TEST--
tdb_chainlock() basic tests
--FILE--
<?php

$file = dirname(__FILE__)."/test.tdb";
$tdb = tdb_open($file);

tdb_lock($tdb);
var_dump(tdb_chainlock($tdb, ""));
var_dump(tdb_chainlock($tdb, "", true));
tdb_unlock($tdb);

tdb_lock($tdb, true);
var_dump(tdb_chainlock($tdb, ""));
var_dump(tdb_chainlock($tdb, "", true));
tdb_unlock($tdb, true);

var_dump(tdb_chainlock($tdb, "", true));
var_dump(tdb_chainlock($tdb, "", true));
var_dump(tdb_error($tdb));

var_dump(tdb_chainlock($tdb, ""));
var_dump(tdb_chainlock($tdb, ""));
var_dump(tdb_error($tdb));

var_dump(tdb_chainlock($tdb, "", true));
var_dump(tdb_chainlock($tdb, "", true));
var_dump(tdb_error($tdb));

tdb_close($tdb);
var_dump(tdb_chainlock($tdb, ""));

unlink($file);

echo "Done\n";
?>
--EXPECTF--	
bool(true)
bool(true)

Warning: tdb_chainlock(): Locking error in %s on line %d
bool(false)
bool(true)
bool(true)
bool(true)
string(13) "Locking error"
bool(true)
bool(true)
string(13) "Locking error"
bool(true)
bool(true)
string(13) "Locking error"

Warning: tdb_chainlock(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
