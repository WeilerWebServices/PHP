<?php 
      $processes = shell_exec("ps -ef | grep Paris");
      $process_list = explode("risUG.php", $processes);
      foreach ($process_list as $line) { 
        if (preg_match("#zoe\s+(\d+)\s+.*Pa$#", $line, $matches)) {
		shell_exec("kill " . $matches[1]);
	}
	}
?>
