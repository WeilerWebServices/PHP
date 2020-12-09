--TEST--
tdb_get_seqnum() without TDB_SEQNUM
--FILE--
<?php

$tdb = tdb_open("", 0, TDB_INTERNAL);

var_dump(tdb_get_seqnum($tdb));

tdb_insert($tdb, "", "");
var_dump(tdb_get_seqnum($tdb));
tdb_insert($tdb, "test", "");
tdb_update($tdb, "test", "2");
var_dump(tdb_get_seqnum($tdb));

echo "Done\n";
?>
--EXPECTF--	
int(0)
int(0)
int(0)
Done
