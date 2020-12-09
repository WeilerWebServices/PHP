--TEST--
tdb_chainunlock() basic tests 
--FILE--
<?php

$file = dirname(__FILE__)."/test.tdb";
$tdb = tdb_open($file);

var_dump(tdb_chainunlock());
var_dump(tdb_chainunlock($tdb, "", "", ""));

tdb_lock($tdb);
var_dump(tdb_chainunlock($tdb, ""));
var_dump(tdb_chainunlock($tdb, "", true));
tdb_unlock($tdb);

tdb_lock($tdb, true);
var_dump(tdb_chainunlock($tdb, ""));
var_dump(tdb_chainunlock($tdb, "", true));
tdb_unlock($tdb, true);

tdb_insert($tdb, "key", "");

var_dump(tdb_chainlock($tdb, "key"));
var_dump(tdb_chainunlock($tdb, "key"));
var_dump(tdb_chainunlock($tdb, "key"));

tdb_insert($tdb, "key2", "");

var_dump(tdb_chainlock($tdb, "key2", true));
var_dump(tdb_chainunlock($tdb, "key2"));
var_dump(tdb_chainunlock($tdb, "key2", true));
var_dump(tdb_chainunlock($tdb, "key2", true));

var_dump(tdb_error($tdb));

tdb_close($tdb);
var_dump(tdb_chainunlock($tdb, "key"));

unlink($file);

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_chainunlock() expects at least 2 parameters, 0 given in %s on line %d
NULL

Warning: tdb_chainunlock() expects at most 3 parameters, 4 given in %s on line %d
NULL
bool(true)
bool(true)

Warning: tdb_chainunlock(): Locking error in %s on line %d
bool(false)
bool(true)
bool(true)
bool(true)

Warning: tdb_chainunlock(): IO Error in %s on line %d
bool(false)
bool(true)
bool(true)
bool(false)
bool(false)
bool(false)

Warning: tdb_chainunlock(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
