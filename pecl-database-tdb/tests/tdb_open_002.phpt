--TEST--
tdb_open() errors - 2
--FILE--
<?php

$file = dirname(__FILE__)."/test.tdb";

var_dump(tdb_open());
var_dump(tdb_open(1,1,1,1,1,1,1));
var_dump(tdb_open("1",1,1,1,1,1,1));

var_dump($tdb = tdb_open($file));
var_dump(tdb_close($tdb));
@unlink($file);

var_dump($tdb = tdb_open($file, 1024));
var_dump(tdb_close($tdb));
@unlink($file);

var_dump($tdb = tdb_open($file, 0, TDB_INTERNAL|TDB_NOSYNC|TDB_NOLOCK|TDB_NOMMAP|TDB_CLEAR_IF_FIRST|TDB_SEQNUM));
var_dump(tdb_close($tdb));
@unlink($file);

var_dump($tdb = tdb_open($file, 0, 0, 0));
var_dump($tdb = tdb_open($file, 0, 0, O_RDWR));
var_dump($tdb = tdb_open($file, 0, 0, O_RDONLY));
var_dump($tdb = tdb_open($file, 0, 0, O_RDWR));
var_dump($tdb = tdb_open($file, 0, 0, O_RDWR|O_RDONLY));
var_dump($tdb = tdb_open($file, 0, 0, O_CREAT|O_RDONLY));

var_dump($tdb = tdb_open($file, 0, 0, O_CREAT|O_RDWR));
var_dump(tdb_close($tdb));
@unlink($file);

var_dump($tdb = tdb_open($file, 0, 0, O_CREAT|O_RDWR, S_IRUSR));
var_dump(tdb_close($tdb));
@unlink($file);

var_dump($tdb = tdb_open($file, 0, 0, O_CREAT|O_RDWR, S_IWUSR));
var_dump(tdb_close($tdb));
@unlink($file);

var_dump($tdb = tdb_open($file, 0, 0, O_CREAT|O_RDWR, S_IWUSR|S_IRUSR));
var_dump(tdb_close($tdb));
@unlink($file);

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_open() expects at least 1 parameter, 0 given in %s on line %d
NULL

Warning: tdb_open() expects at most 5 parameters, 7 given in %s on line %d
NULL

Warning: tdb_open() expects at most 5 parameters, 7 given in %s on line %d
NULL
resource(%d) of type (Trivial DB context)
bool(true)
resource(%d) of type (Trivial DB context)
bool(true)
resource(%d) of type (Trivial DB context)
bool(true)

Warning: tdb_open(): No such file or directory in %s on line %d
bool(false)

Warning: tdb_open(): No such file or directory in %s on line %d
bool(false)

Warning: tdb_open(): No such file or directory in %s on line %d
bool(false)

Warning: tdb_open(): No such file or directory in %s on line %d
bool(false)

Warning: tdb_open(): No such file or directory in %s on line %d
bool(false)

Warning: tdb_open(): Input/output error in %s on line %d
bool(false)
resource(%d) of type (Trivial DB context)
bool(true)
resource(%d) of type (Trivial DB context)
bool(true)
resource(%d) of type (Trivial DB context)
bool(true)
resource(%d) of type (Trivial DB context)
bool(true)
Done
