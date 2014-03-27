<?php
header('Content-Type: text/html; charset=utf-8');
require './SISParser.php';



$url = "http://sis-handball.de/web/Mannschaft/default.aspx?view=Mannschaft&Liga"
        . "=001514000000000000000000000000000001003&clear=1";
$title = "DKB HBL";

try {
    $parser = new sis2ics\SISParser($url, $title);
} catch (UnexpectedValueException $ex) {
    die($ex->getMessage());
}
 
$parser->execute();

