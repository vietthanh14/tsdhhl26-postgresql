<?php
$html = file_get_contents('http://tuyensinhdhhl.asapvn.com/');
echo "BODY:\n";
echo $html;
echo "\nHEADERS:\n";
print_r($http_response_header);
