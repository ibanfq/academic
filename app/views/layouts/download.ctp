<?php
header('Content-Type: text/csv; charset=iso-8859-1');
header('Content-Disposition: attachment; filename="'.$filename.'"');
echo iconv("UTF-8", "ISO-8859-1//IGNORE", $content_for_layout);
die();
?>