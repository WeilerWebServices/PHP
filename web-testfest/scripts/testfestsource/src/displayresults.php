<?php session_start()?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<link rel="stylesheet" href="PhpTestFestRegistration.css">

<head>
<title>PHPT results</title>
</head>

<h1> PHP TestFest 2009 run results - 32 bit Linux</h1>

<a href=http://testfest.php.net/displayresults.php>Results on 64 bit PPC Linux are here</a>

<body>
<p>This site is for checking tests that are contributed as part of PHP TestFest 2009. <p>The tests are committed in a subversion repository, then extracted and run using PHP5.2, PHP5.3 and PHP6. TestFest is focussed on PHP5.3, so the tests should work with PHP5.3. The PHP5.2 and PHP 6 results are there to help people who will commit the tests in PHP's CVS to know how much work they have to do.
<p>The final column in the table shows whether the test has already been reviewed and committed in PHP's CVS repository.
<p>The results table is updated hourly, at approximately 40 minutes past the hour.
<p>The name of the user group responsible for writing the test is appended at that end of the test name. Thanks!

<?php

$phpVersion = array( 'php6.0',
                     'php5.3',
                     'php5.2',
);
$publishDir = 'publishresults';
$testRunDir = '/p2/home/testfestreports/testRunDir';

$relat = array();

foreach($phpVersion as $version) {
    $testfile=$publishDir."/".$version."/results";
    $results = file($testfile);

    foreach($results as $line) {
        if(preg_match('/^TEST.*\s+(WARN|SKIP|PASS|FAIL|XFAIL|BORK|WARN&FAIL)\s+.*\[(.*\.phpt)\]/', $line, $matches)) {

	    $state = $matches[1];

	    //subtract the final .phpt off the test names
            $base = substr($matches[2], 0, -5);         
	    
	    //strip off the location dependent part of the path
	    $relativename = substr($base, strlen($testRunDir."/".$version."/"));         

	    //construct the full name of the published location of the test file and its output files
            $publishname = $publishDir."/".$version."/".$relativename;

            $relat[] = $relativename;
            $states[$relativename][$version] = $matches[1];
            $filebase[$relativename][$version] = $publishname;
           
        }
    }
    if(isset($relat)) {
            $relat = array_unique($relat);
    }
}

//get the list of committed files
$testNames = file($publishDir . "/commits");
$totalCommittedTests = count($testNames);
$committedTests = array();

foreach ($testNames as $line) {
   $strip = substr(trim($line), 1); //get rid of the starting / and the LF
   $committedTests[] = substr($strip, 0, -strlen(".phpt")); //get rid of the .phpt
}

$_SESSION['states'] = $states;
$_SESSION['filebase'] = $filebase;
$_SESSION['relat'] = $relat;

$summary = buildSummary($relat, $totalCommittedTests);
echo $summary;


$out = buildSummaryTable($states,  $relat, $filebase, $committedTests);
echo $out;

function buildSummary($relat, $totalCommittedTests) {
$groups = array();
foreach($relat as $testname) {
        preg_match("/.*_(\w+)$/", $testname, $matches);
        $groupName = $matches[1];
        if(isset($groups[$groupName])) {
              $groups[$groupName] ++;
        } else {
              $groups[$groupName] = 1;
        }
 }
$htmlString = "<h2> Break down of tests by PHP User Group </h2>";
$totalTests = 0;
foreach ($groups as $groupName => $numberOfTests) {
  $totalTests += $numberOfTests;
  $htmlString .= "Tests from group $groupName = $numberOfTests<br>";
}
$htmlString .= "<br><b>Total tests contributed = $totalTests</b><br>";

$htmlString .= "<br><b>Total tests moved to PHP CVS = $totalCommittedTests</b><br><br>";

return $htmlString;
}



function buildSummaryTable($states, $relat, $filebase, $committedTests) {
   
    $htmlString = "<table class=\"resultTable\">";
    $htmlString .= "<th>TestName</th><th>PHP52</th><th>PHP53</th><th>PHP6</th><th>CVS?</th>";
    
    foreach($relat as $testname) {
        $s52 = $states[$testname]['php5.2'];
            $basename = $filebase[$testname]['php5.2'];
            $ref = 'filelist.php?basename='.$basename;
            $s52 = "<a href=$ref>$s52</a><br>";
        $s53 = $states[$testname]['php5.3'];
            $basename = $filebase[$testname]['php5.3'];
            $ref = 'filelist.php?basename='.$basename;
            $s53 = "<a href=$ref>$s53</a><br>";
        $s60 = $states[$testname]['php6.0'];
            $basename = $filebase[$testname]['php6.0'];
            $ref = 'filelist.php?basename='.$basename;
            $s60 = "<a href=$ref>$s60</a><br>"; 

       if(in_array($testname, $committedTests)) {
            $committed = "<img src=\"tick.gif\">";
       } else {
            $committed = "N";
       }

        $htmlString .= "<tr><td>$testname</td><td>$s52</td><td>$s53</td><td>$s60</td><td>$committed</td></tr>";
    }
    $htmlString .= "</table>";
    return $htmlString;
}

?>

</body>
</html>
