<?php
class tmCvsCheck
{
    protected $cvsDir;
    protected $runDir;
    protected $fileList = array();
    protected $normalisedFileNames = array();
    protected $relativeFileNames = array();

    public function __construct($runDir, $cvsDir) {
      $this->runDir = $runDir;
      $this->cvsDir = $cvsDir;
      $this->getFiles();
      $this->cvsUp();
      $this->normaliseFileNames();
    }


    public function cvsUp() {
       $current = realpath(getcwd());
       chdir($this->cvsDir);
       shell_exec("cvs up");
       chdir($current);
    }
       
   
    public function getFiles() {
      $fileString = shell_exec("find $this->runDir -name \"*.phpt\" -print");
      foreach(explode("\n", $fileString) as $file) {
        if (substr($file, -strlen(".phpt")) == ".phpt") {
            $this->fileList[] = $file;
        }
      }
    }

    public function normaliseFileNames() {
      foreach ($this->fileList as $fileName) {
        if(preg_match("#$this->runDir(.*)(_\w+\.phpt)$#", $fileName, $matches)) {
          $this->normalisedFileNames[] = $matches[1] . ".phpt";
          $this->relativeFileNames[] = $matches[1] . $matches[2];
        }
      }
    }

    public function isCommitted($name) {
        if(file_exists($this->cvsDir . "/" . $name)) {
          return true;
        }
    return false;
    }

    public function makeCommitList() {
       $committed = array();
       for($i = 0; $i < count($this->fileList); $i++) {
         $file = $this->normalisedFileNames[$i];
         if($this->isCommitted($file)) {
           $committed[] =  $this->relativeFileNames[$i];
         }
       }
     return $committed;
     }


}
?>

