<?php
require_once dirname(__FILE__) . '/tmAutoload.php';

$repositoryPath = "http://89.151.87.83/repos/testfest";

//Setup

$rootDir = "/home/testfestreports";

$svnCheckOutDir = $rootDir."/svnCheckOutDir";

$phpBuildDir = $rootDir."/phpBuildDir";

$testRunDir = $rootDir."/testRunDir";

$phpVersion = array( 'php6.0',
                     'php5.3',
                     'php5.2',
);

foreach($phpVersion as $version) {
    //remove old tar files
    shell_exec("rm $phpBuildDir/*.tar.gz");
    
    $builder = tmGetPhp::getInstance($version);
    $builder->getLatestBuildFile($phpBuildDir);
    $builder->extractSource($phpBuildDir);
    $builder->buildPHP($phpBuildDir);
}

?>
