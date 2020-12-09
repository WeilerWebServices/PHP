<?php

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


$testSetup->setFilesToCopy();

$filesToCopy = $testSetup->getFilesToCopy();
 
var_dump($filesToCopy);


//copy all the files into a directory thay can be run from
foreach($phpVersion as $dir) {
    //remove .phpt files from last run, necessary of people have moved things around in SVN.
    shell_exec("find $testRunDir/$dir -name \"*.phpt\" | xargs -i rm {}");	

    foreach ($filesToCopy as $file) {         
        $from = trim($svnCheckOutDir."/testfest/".$file);
        $destFile = $testSetup->targetFileName($file);
        $to = $testRunDir."/".$dir."/".$destFile;
        echo "Copy $from to $to\n";
//        shell_exec("cp $from $to");         
    }
}

?>
?>
