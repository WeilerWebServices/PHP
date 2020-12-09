<?php

/*
 * Main php file for testManager
 *
 */

require_once dirname(__FILE__) . '/tmAutoload.php';

$repositoryPath = "http://89.151.87.83/repos/testfest";

//Setup

$rootDir = "/home/testfestreports";

$svnCheckOutDir = $rootDir."/svnCheckOutDir";
$cvsCheckOutDir = $rootDir."/cvsCheckOutDir/php5";

$phpBuildDir = $rootDir."/phpBuildDir";

$testRunDir = $rootDir."/testRunDir";

$phpVersion = array( 'php6.0',
                     'php5.3',
                     'php5.2',
);

//Script sould run once an hour. Relies on having PHP builds in the right place

$testSetup = new tmExtractTests($repositoryPath, $svnCheckOutDir);

if(!is_dir($svnCheckOutDir)) {
    $testSetup->checkOut();
} else {
    echo "updating SVN\n";
    $testSetup->update();
}

//set enviroment variable needed for CURL tests. Better not to do this, shoudl have an --ENV-- section & --SKIPIF--
putenv('PHP_CURL_HTTP_REMOTE_SERVER=http://results.testfest.php.net');

$testSetup->setFilesToCopy();

$filesToCopy = $testSetup->getFilesToCopy();

//copy all the files into a directory thay can be run from
foreach($phpVersion as $dir) {
    //remove .phpt files from last run, necessary of people have moved things around in SVN.
    shell_exec("find $testRunDir/$dir -name \"*.phpt\" | xargs -i rm {}");	

    foreach ($filesToCopy as $file) {         
        $from = trim($svnCheckOutDir."/testfest/".$file);
        $destFile = $testSetup->targetFileName($file);
        $to = $testRunDir."/".$dir."/".$destFile;
        // echo "Copy $from to $to\n";
        shell_exec("cp $from $to");         
    }
}

foreach ($phpVersion as $dir) {
    $testDir = $testRunDir."/".$dir;
    $run_tests = $phpBuildDir."/".$dir."/run-tests.php";
    $phpExecutable = $phpBuildDir."/".$dir."/sapi/cli/php";
    echo "$phpExecutable -n  $run_tests -n -p $phpExecutable $testDir\n";
    // The php 6 run doesn't clean up properly for LDAP. So run the 5.3 tests once to clean up before running properly
    if($dir == 'php5.3') {
          shell_exec("$phpExecutable -n  $run_tests -n -p $phpExecutable $testDir/ext/ldap/tests");
    }

    $results = shell_exec("$phpExecutable -n  $run_tests -n -p $phpExecutable $testDir");
    file_put_contents($testDir."/results", $results);
}
//check to see what has already been committed - just does a 5.3 check an gets a list of files that 
// have been copied to CVS

$cvsChecker = new tmCvsCheck($testRunDir . "/php5.3", $cvsCheckOutDir);
$commitList = $cvsChecker->makeCommitList();
$commitString = implode("\n", $commitList);
file_put_contents($testRunDir . "/commits", $commitString);
?>
