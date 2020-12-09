--TEST--
tdb_get_flags() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL|TDB_SEQNUM);

var_dump(tdb_get_flags());
var_dump(tdb_get_flags($tdb, ""));

var_dump(tdb_get_flags($tdb));

$tdb = tdb_open("", 0, TDB_INTERNAL);
var_dump(tdb_get_flags($tdb));

$file = dirname(__FILE__)."/test.tdb";

$tdb = tdb_open($file, 0, 0);
var_dump(tdb_get_flags($tdb));

tdb_close($tdb);
unlink($file);

var_dump(tdb_get_flags($tdb));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_get_flags() expects exactly 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_get_flags() expects exactly 1 parameter, 2 given in %s on line %d
NULL
int(142)
int(14)
int(0)

Warning: tdb_get_flags(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
