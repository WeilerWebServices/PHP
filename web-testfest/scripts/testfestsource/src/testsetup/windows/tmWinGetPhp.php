<?php
class tmGetPhp
{
    private $snapsSite = "http://snaps.php.net";
    private $buildType;
    private $buildName;
    private $buildDate;
    private $buildFileName;
    

    public function getInstance($buildType, $os='unix')  
    {
     if ($os == 'Windows') {
            return new tmWinGetPhp($buildType);
        } else {
            return new tmUnixGetPhp($buildType);
        }
        
    }
    public function __construct($buildType)
    {
        $this->buildType = $buildType;
        $this->buildName = $this->setBuildName();
        $this->buildFileName = $this->setBuildFileName();
        $this->buildDate = $this->setBuildDate();
    }

    public function setBuildName($buildtype)
    {
        $snapsindex=file_get_contents($this->snapsSite);
        //this windows stuff is just here because I copied it from something else
        if (preg_match("/wphp5.2/",$buildtype)) {
            $buildname = "http://windows.php.net/downloads/snaps/php-5.2-nts-win32-VC6-x86-latest.zip";
        } else if (preg_match("/wphp5.3/",$buildtype)) {
            $buildname = "http://windows.php.net/downloads/snaps/php-5.3-nts-win32-VC6-x86-latest.zip";
        } else if (preg_match("/wphp6.0/",$buildtype)) {
            $buildname = "http://windows.php.net/downloads/snaps/php-6.0-win32-VC6-x86-latest.zip";
        } else {
            preg_match("/($buildtype-\d{12}.tar.gz)/", $snapsindex,$matches);
            $buildname = ($matches[1]);
        }
        return(trim($buildname));
    }
    
    public function getLatestBuildFile($buildName, $srcDir)
    {
        $current = realpath(getcwd());
        if(is_dir($srcDir)) {
            shell_exec("rm -r ".$srcDir);
            mkdir($srcDir);
        }
        chdir($srcDir);
        shell_exec("wget ".$this->snapsSite."/".$buildName);
        chdir($current); 
    }
    
    
    public function extractSource($buildFile, $buildType, $srcDir)
    {
        $current = realpath(getcwd());
        chdir($srcDir);
        shell_exec("tar -xvzf ".$buildFile);
        //works for linux, well all of this only works for linux :-)
        if(substr($buildFile, -7) == ".tar.gz") {
            $buildName = substr($buildFile, 0, -7);
            shell_exec("mv ".$buildName." ".$buildType);
        }
        chdir($current);
    }
    
    public function buildPHP ($buildType, $srcDir)
    {
        var_dump($srcDir."/".$buildType."/buildconf");
        
        shell_exec($srcDir."/".$buildType."/buildconf");
        shell_exec($srcDir."/".$buildType."/configure");
        shell_exec($srcDir."/".$buildType."/make");
    }
    
    
}
?>