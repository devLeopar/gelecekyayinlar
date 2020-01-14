<?php

require 'quickstart.php';
require __DIR__.'/htmlparser/vendor/autoload.php';
use PHPHtmlParser\Dom;

$months = [
    "January" => "Ocak",
    "February" => "Şubat",
    "March" => "Mart",
    "April" => "Nisan",
    "May " => "Mayıs",
    "June" => "Haziran",
    "July" => "Temmuz",
    "August" => "Ağustos",
    "September" => "Eylül",
    "October" => "Ekim",
    "November" => "Kasım",
    "December" => "Aralık",
];
function getTrDate($date){
    global $months;
    $split = explode(" ", $date);
    return $split[0]." ".$months[$split[1]]." ".$split[2];
}

$james_dum = $uevents[0]['ev_id']; //should delete

//$textString = file_get_contents("https://docs.google.com/spreadsheets/d/e/2PACX-1vR0nPvH3DckCCKtVy605uRIrtSIskGfEeCoa3GzWTDhxozeXtyjCM20JRPKKmXtydhoXNhQ-MHiA6nS/pubhtml?gid=0&single=true");
$textString = file_get_contents("https://docs.google.com/spreadsheets/d/e/2PACX-".$uevents[0]['ev_id']."/pubhtml?gid=0&single=true");

$dom = new Dom;
//$dom->setOptions(["removeStyles" => true]);
$dom->load($textString);
$tr = $dom->find('tr');
$count = count($tr);
$xmlString = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<list xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
';
for($i = 3; $i < $count; $i++){
    $td = $tr[$i]->find("td");
    if(count($td) == 16){
        $title = $td[2]->innerHtml;
        if($title == "") continue;
        $title = preg_replace('/<[^>]*>/', '', $title);
        $date = getTrDate($td[0]->innerHtml);
        $forLink = new Dom;
        $forLink->loadStr($td[8]->innerHtml);
        $findA = $forLink->find("a");
        $imageDriveLink = $findA->innerHtml;
        $split = explode("?id=", $imageDriveLink);
        $id = $split[count($split) - 1];
        $imageOrjLink = "https://drive.google.com/uc?export=view&id=".$id;
    //    if(!file_exists($i."-".$id.".jpg"))copy($imageOrjLink, $i."-".$id.".jpg"); //copying images dir should be commented now
        $xmlString .= "    <item>
    <title>".$title."</title>
    <image>".$i."-".$id.".jpg"."</image>
    <description>".$date."</description>
</item>
";
    }
}
$xmlString .= "</list>";
$f = fopen("list.xml","w");
fputs($f, $xmlString);
fclose($f);

?>