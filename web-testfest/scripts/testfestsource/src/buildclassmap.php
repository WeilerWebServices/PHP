<?php
/*
 * This is stand alone code to build the file tmClassMap used by tmAutoload to find classes.
 * It assumes that all classes have the same name at the file name (less .php)  and all are prefixed by 'tm'
 * 
 */

$map = new BuildClassMap();
$map->buildMap();

class BuildClassMap
{
    public function buildMap()
    {
        $thisDir = getcwd();   

        $sourceFiles = $this->getSourceList($thisDir);

        $mapString = '<?php'."\n";
        $mapString .= '  $tmClassMap = array('."\n";

        sort($sourceFiles);

        foreach ($sourceFiles as $class) {
            $relativeLocation = substr($class, strlen($thisDir.'/'));
          
            $className = basename($class, '.php');
          
            $spaces = $this->getSpaces(strlen($className));
          
            $mapString .= "    ".'\''.$className.'\''. $spaces. " => ".'\''.$relativeLocation.'\','."\n";
        }

        $mapString .= '  );'."\n";
        $mapString .= '?>'."\n";

        file_put_contents($thisDir.'/tmClassMap.php', $mapString);
    }

    public function getSpaces($length)
    {
        $spaces = "";
        $nspaces = 40 - $length;

        for ($i=0; $i < $nspaces; $i++) {
            $spaces .=" ";
        }

        return $spaces;
    }

    public function getSourceList($aDirectory)
    {
        $files = array();

        foreach (new PhpFilterIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($aDirectory))) as $item) {
            $files[] = $item->getPathname();
        }

        return $files;
    }
}

class PhpFilterIterator extends FilterIterator
{
    public function accept()
    {
        if (substr($this->current(), -strlen('.php')) == '.php') {
            if (substr(basename($this->current()), 0, strlen('tm')) == 'tm') {
                return true;
            }
        }

        return false;
    }
}
?>
