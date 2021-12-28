<?php
$url = "../authorize.php?".$_SERVER['QUERY_STRING'];
header("HTTP/1.1 301 Moved Permanently"); 
header("Location: $url");
?>