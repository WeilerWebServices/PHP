--TEST--
tdb_get_seqnum() basic tests
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL|TDB_SEQNUM);

var_dump(tdb_get_seqnum());
var_dump(tdb_get_seqnum($tdb, ""));

var_dump(tdb_get_seqnum($tdb));

tdb_insert($tdb, "", "");
var_dump(tdb_get_seqnum($tdb));
tdb_insert($tdb, "test", "");
var_dump(tdb_get_seqnum($tdb));
tdb_insert($tdb, "test2", "");
var_dump(tdb_get_seqnum($tdb));
tdb_update($tdb, "test", "3");
var_dump(tdb_get_seqnum($tdb));
tdb_update($tdb, "test", "1");
tdb_update($tdb, "test", "1");
var_dump(tdb_get_seqnum($tdb));
tdb_update($tdb, "test", "2");
tdb_update($tdb, "test2", "2");
var_dump(tdb_get_seqnum($tdb));

tdb_close($tdb);
var_dump(tdb_get_seqnum($tdb));

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_get_seqnum() expects exactly 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_get_seqnum() expects exactly 1 parameter, 2 given in %s on line %d
NULL
int(0)
int(1)
int(2)
int(3)
int(5)
int(7)
int(9)

Warning: tdb_get_seqnum(): %d is not a valid Trivial DB context resource in %s on line %d
bool(false)
Done
