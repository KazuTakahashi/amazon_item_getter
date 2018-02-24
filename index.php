<?php
//namespace AmazonItemGetter;
include_once 'APAGetter.php';

function main() {
    ChromePhp::log(getallheaders());

    $apaGetter = null;
    try {
        // itemIdがの送信有無、なければ http status code 400 を投げる
        if (!isset($_GET['itemId']) || !is_string($_GET['itemId']) || $_GET['itemId'] === '') {
            http_response_code(400);
            //header('HTTP/1.1 400 Bad Request');
            throw new RuntimeException('400 Bad Request');
        }
        
        $apaGetter = new APAGetter($_GET['itemId']);// B013JDJLDQ 9784569838960

    } catch (Exception $e) {
        http_response_code(400);
        //header('HTTP/1.1 400 Bad Request');
        echo $e->getMessage();
        return;
    }
    
    
    //結果を表示する
    $json = $apaGetter->getResponseToJson();
    ChromePhp::log($json);
    echo $json;

    
    http_response_code(201);
    //header('HTTP/1.1 201 Created');
    header('content-type: application/json; charset=utf-8');

    header_register_callback(function(){
        // X-Powered-Byヘッダの削除 ※php.iniの'expose_php = Off'でも同様
        header_remove('X-Powered-By');
    });
}

function setHeader() {
    http_response_code(201);
    //header('HTTP/1.1 201 Created');
    header('content-type: application/json; charset=utf-8');

    
    header_register_callback(function(){
        // X-Powered-Byヘッダの削除 ※php.iniの'expose_php = Off'でも同様
        header_remove('X-Powered-By');
    });
}


main();


?>