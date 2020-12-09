--TEST--
tdb_store() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_store($tdb));
var_dump(tdb_store($tdb, "", "", 0, ""));

var_dump(tdb_store($tdb, "", "", 0));
var_dump(tdb_store($tdb, "", "", -1));
var_dump(tdb_store($tdb, "", "", PHP_INT_MAX));

var_dump(tdb_store($tdb, "", "", TDB_MODIFY));

var_dump(tdb_store($tdb, "", "", TDB_INSERT));
var_dump(tdb_store($tdb, "", "", TDB_INSERT));
var_dump(tdb_fetch($tdb, ""));

$str = str_repeat("test", 256);
var_dump(tdb_store($tdb, "", $str, TDB_MODIFY));
var_dump(tdb_fetch($tdb, ""));

$str = str_repeat("blah", 64);
var_dump(tdb_store($tdb, "", $str, TDB_REPLACE));
var_dump(tdb_fetch($tdb, ""));

var_dump(tdb_store($tdb, "test key1", "test value", TDB_REPLACE));
var_dump(tdb_fetch($tdb, "test key1"));

var_dump(tdb_store($tdb, "test key2", "test value", TDB_INSERT));
var_dump(tdb_fetch($tdb, "test key2"));

var_dump(tdb_store($tdb, "test key2", "test value2", TDB_MODIFY));
var_dump(tdb_fetch($tdb, "test key2"));

tdb_close($tdb);
var_dump(tdb_store($tdb, "test key2", "test value2", TDB_MODIFY));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_store() expects exactly 4 parameters, 1 given in %s on line %d
NULL

Warning: tdb_store() expects exactly 4 parameters, 5 given in %s on line %d
NULL

Warning: tdb_store(): Invalid operation mode specified: 0 in %s on line %d
bool(false)

Warning: tdb_store(): Invalid operation mode specified: -1 in %s on line %d
bool(false)

Warning: tdb_store(): Invalid operation mode specified: 2147483647 in %s on line %d
bool(false)

Warning: tdb_store(): Record does not exist in %s on line %d
bool(false)
bool(true)

Warning: tdb_store(): Record exists in %s on line %d
bool(false)
string(0) ""
bool(true)
string(1024) "testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest"
bool(true)
string(256) "blahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblahblah"
bool(true)
string(10) "test value"
bool(true)
string(10) "test value"
bool(true)
string(11) "test value2"

Warning: tdb_store(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
