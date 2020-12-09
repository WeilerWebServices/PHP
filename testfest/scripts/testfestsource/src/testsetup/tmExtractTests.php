<?php


class tmExtractTests {

    private $repositoryPath;
    private $svnTestLocation;
    private $relNamesToCopy;

    public function __construct($repositoryPath, $svnTestLocation) {
        $this->repositoryPath = $repositoryPath;
        $this->svnTestLocation = $svnTestLocation;
    }

    public function checkOut() {
        $current = realpath(getcwd());
        mkdir($this->svnTestLocation);
        chdir($this->svnTestLocation);
        shell_exec("svn checkout $this->repositoryPath");
        chdir($current);
         
    }

    public function update() {
        $current = realpath(getcwd());
        chdir($this->svnTestLocation."/testfest");
        shell_exec("svn update");
        chdir($current);
    }


    public function setFilesToCopy() {
        $this->relNamesToCopy = array();
        $current = realpath(getcwd());
        chdir($this->svnTestLocation."/testfest");
        $dirs = scandir($this->svnTestLocation."/testfest");

        $fileList = shell_exec("find . -name \"*.phpt\"");
        $phptFiles = explode('./', $fileList);

        $fileList = shell_exec("find . -name \"*.inc\"");
        $incFiles = explode('./', $fileList);

        $fileList = shell_exec("find . -name \"*.xsd\"");
        $xsdFiles = explode('./', $fileList);

        $fileList = shell_exec("find . -name \"*.xml\"");
        $xmlFiles = explode('./', $fileList);

        $fileList = shell_exec("find . -name \"*.xsl\"");
        $xslFiles = explode('./', $fileList);

        $relNamesToCopy = array_merge($phptFiles, $incFiles, $xsdFiles,  $xmlFiles, $xslFiles);
      
        //remove any bogus entries
        foreach ($relNamesToCopy as $filename) {
            $filename = trim($filename);
            if (substr($filename, -strlen('.phpt')) == '.phpt') {
                $this->relNamesToCopy[] = $filename;
            }
            if (substr($filename, -strlen('.inc')) == '.inc') {
                $this->relNamesToCopy[] = $filename;
            }
            if (substr($filename, -strlen('.xsd')) == '.xsd') {
                $this->relNamesToCopy[] = $filename;
            }
            if (substr($filename, -strlen('.xml')) == '.xml') {
                $this->relNamesToCopy[] = $filename;
            }
            if (substr($filename, -strlen('.xsl')) == '.xsl') {
                $this->relNamesToCopy[] = $filename;
            }
        }

    }

    public function targetFileName($name) {
        if(preg_match('/(\w+)\/(.*).phpt/', $name, $matches)) {
            $target = $matches[2]."_".$matches[1].".phpt";
        } else if(preg_match('/(\w+)\/(.*).inc/', $name, $matches)) {
            $target = $matches[2].".inc";
        } else if(preg_match('/(\w+)\/(.*).xsd/', $name, $matches)) {
            $target = $matches[2].".xsd";
        } else if(preg_match('/(\w+)\/(.*).xml/', $name, $matches)) {
            $target = $matches[2].".xml";
        } else if(preg_match('/(\w+)\/(.*).xsl/', $name, $matches)) {
            $target = $matches[2].".xsl";
        } else {
            $target = $name;
        }
        return $target;
    }

    public function getFilesToCopy()
    {
        return $this->relNamesToCopy;
    }
}
?>
