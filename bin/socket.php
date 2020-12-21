#!/usr/local/bin/php -q
<?php
print_r($_SERVER);

$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
  $txt = json_encode($_SERVER);
fwrite($myfile, $txt);
fclose($myfile);
?>