<?php
include 'ChromePhp.php';

$url = 'https://sellercentral.amazon.co.jp/fba/profitabilitycalculator/productmatches?searchKey=4798149810&language=ja_JP&profitcalcToken=ibHsrDQYkpn6kkFIiBxJFTFw21gj3D';

$ch = curl_init();

//URLとオプションを指定する
curl_setopt($ch, CURLOPT_URL, $url);

//URLの情報を取得する
$res =  curl_exec($ch);

//結果を表示する
ChromePhp::log($res);

//セッションを終了する
curl_close($ch);
?>