--TEST--
tdb_open() errors
--FILE--
<?php

var_dump(tdb_open("/there/is/no/such/file/or/directory"));

$dir = dirname(__FILE__)."/test_dir";
$file = dirname(__FILE__)."/test.tdb";
@rmdir($dir);
mkdir($dir);

var_dump(tdb_open($dir));
var_dump(tdb_open(""));

var_dump(tdb_open($file, -1));
var_dump(tdb_open($file,0,0,0,0));
var_dump(tdb_open($file,0,-1,0,0));
var_dump(tdb_open($file,0,0,-1,0));
var_dump(tdb_open($file,0,0,0,-1));

@rmdir($dir);
@unlink($file);

echo "Done\n";
?>
--EXPECTF--	
Warning: tdb_open(): No such file or directory in %s on line %d
bool(false)

Warning: tdb_open(): Is a directory in %s on line %d
bool(false)

Warning: tdb_open(): No such file or directory in %s on line %d
bool(false)

Warning: tdb_open(): The integer value of hash_size cannot be less than zero in %s on line %d
bool(false)

Warning: tdb_open(): No such file or directory in %s on line %d
bool(false)
resource(%d) of type (Trivial DB context)

Warning: tdb_open(): Bad file descriptor in %s on line %d
bool(false)

Warning: tdb_open(): Permission denied in %s on line %d
bool(false)
Done
