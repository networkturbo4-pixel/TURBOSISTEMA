<?php
$c = file_get_contents('includes/RouterManager.php');
$c = str_replace("<?php\r\n// Extracted ZTE", "// Extracted ZTE", $c);
$c = str_replace("<?php\n// Extracted ZTE", "// Extracted ZTE", $c);
file_put_contents('includes/RouterManager.php', $c);
