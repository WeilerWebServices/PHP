<?php session_start()?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">



<head>
<title>Test Output Files</title>
</head>

<body>


<?php

$rootDir = "/home/testfestreports";


$testRunDir = $rootDir."/testRunDir";

$phpVersion = array( 'php6.0',
                     'php5.3',
		'php5.2',
);

$file = $_GET['file'];
$basename = $_SESSION['basename'];

echo "<br><b>File:".htmlspecialchars($file, ENT_QUOTES, 'UTF-8')."</b><br><br>";

$file = realpath($file);
if (strpos($file, '/p2/var/www/results.testfest.php.net/public_html/publishresults/') !== 0) {
	echo "Test file ".htmlspecialchars($file, ENT_QUOTES, 'UTF-8')." outside expected path<br />";
} else {
        highlight_file($file);
}

echo "<br /><br /><a href='filelist.php?basename=$basename'>Back to file list</a><br />";

?>
</body>
</html>
