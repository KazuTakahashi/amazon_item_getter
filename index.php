<?php
//namespace AmazonItemGetter;
include_once 'APAGetter.php';

$itemId = null;
if(isset($_GET['itemId'])) {
    $itemId = $_GET['itemId'];
}


$apaGetter = new APAGetter('B013JDJLDQ');
$xml = $apaGetter->getResponse();
//結果を表示する
ChromePhp::log($xml);

?>