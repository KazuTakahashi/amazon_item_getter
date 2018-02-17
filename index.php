<?php
//namespace AmazonItemGetter;
include_once 'APAGetter.php';

function main() {
    ChromePhp::log(getallheaders());

    $apaGetter = null;
    try {
        // itemIdがの送信有無、なければ http status code 400 を投げる
        if (!isset($_GET['itemId']) || !is_string($_GET['itemId']) || $_GET['itemId'] === '') {
            header('Status: 400 Bad Request');
            throw new RuntimeException('400 Bad Request');
        }

        // ASINにマッチするかどうか、しなければ http status code 400 を投げる
        // if(preg_match("/^[A-Z0-9]{10}$/",$_GET['itemId']) == 1 ) {

        //     ChromePhp::log('asinかも^^');

        // } else if(preg_match("/^[0-9]{13}$/",$_GET['itemId']) == 1 ) {

        //     ChromePhp::log('JANコードかも^^');
        // } else {
        //     ChromePhp::log('asinじゃない;;');
        //     throw new RuntimeException('400 Bad Request');
        // }
        
        $apaGetter = new APAGetter($_GET['itemId']);

    } catch (Exception $e) {
        echo $e->getMessage();
        return;
    }
    
    
    $xml = $apaGetter->getResponse();
    //結果を表示する
    ChromePhp::log($xml);
    
    
    header('Status: 201 Created');
}



main();


?>