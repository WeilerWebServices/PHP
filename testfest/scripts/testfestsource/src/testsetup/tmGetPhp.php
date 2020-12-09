<?php
class tmGetPhp
{
    protected $snapsSite = "http://snaps.php.net";
    protected $buildType;
    protected $buildName;
    protected $buildDate;
    protected $buildFileName;
    

    public static function getInstance($buildType, $os='unix')  
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
        $this->setBuildName();
        $this->setBuildFileName();
        $this->setBuildDate();
    }  

    public function getBuildName()
    {
        return $this->buildName;
    }   
    public function getBuildFileName()
    {
        return $this->buildFileName;
    }  
    public function getBuildDate()
    {
        return $this->buildDate;
    }  
    
}
?>