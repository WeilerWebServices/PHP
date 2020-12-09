<?php session_start()?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<link rel="stylesheet" href="PhpTestFestRegistration.css">

<head>
<title>Output files</title>
</head>

<body>


<?php

$rootDir = "/home/testfestreports";

$svnCheckOutDir = $rootDir."/svnCheckOutDir";

$phpBuildDir = $rootDir."/phpBuildDir";

$testRunDir = $rootDir."/testRunDir";

$phpVersion = array( 'php6.0',
                     'php5.3',
//'php5.2',
);


$base = $_GET['basename'];
//$base = preg_replace('#[^a-z_-.]#', '', $base);
$base = preg_replace('#[^a-z0-9_\-\.\/]#i', '', $base);

$_SESSION['basename'] = $base;

$expFile = $base.".exp";
$outFile = $base.".out";
$difFile = $base.".diff";
$phptFile = $base.".phpt";
$phpFile = $base.".php";

echo "<br><br><b>Output files for test: $base.phpt</b><br><br>";

echo "<b>View test case source: </b> <br>";
echo "<a href=viewfilecontents.php?file=".$phptFile.">Test file</a><br>";

echo "<br><b>Check these if the test has failed: </b> <br>";
echo "<a href=viewfilecontents.php?file=".$outFile.">Actual output</a><br>";
echo "<a href=viewfilecontents.php?file=".$expFile.">Expected output</a><br>";
echo "<a href=viewfilecontents.php?file=".$difFile.">Difference</a><br>";
echo "<a href=viewfilecontents.php?file=".$phpFile.">PHP</a><br>";

echo "<br><br><a href=displayresults.php>Back to results table</a><br>";

?>
</body>
</html>

