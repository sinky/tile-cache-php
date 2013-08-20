<?php

$cacheDir = "./cache/";

switch($_GET["a"]) {
  case "purge":
    system("rm -R ".$cacheDir);    
    system("rm log.txt");    
    $output = "Cache purged";
    break;
  default:
    $output .= "find ".$cacheDir." -type f | wc -l\n";
    $output .= shell_exec("find ".$cacheDir." -type f | wc -l");
    $output .= "\n";
    $output .= "du -sch ".$cacheDir."\n";
    $output .= shell_exec("du -sch ".$cacheDir);
}
echo "<pre>$output</pre>";
?>
<a href="cache_admin.php">stats</a> - <a href="cache_admin.php?a=purge">purge cache</a>