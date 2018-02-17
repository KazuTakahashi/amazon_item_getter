<?php
//namespace AmazonItemGetter;
// define("ACCESS_KEY_ID"     , 'AKIAJELL6GKEYPNFJGHQ');//アクセスキー
// define("secretAccessKey" , 'KFMuAY/OSYkt0eN6cJZ3h0lirc41jxdh33D1Q8A1');
// define("ASSOCIATE_TAG"     , 'hgau0ghao-22');

include_once 'ChromePhp.php';

Class Code {
    private $code = '';

    public function __construct($code=null) {
        if($code!=null) {
            //与えられたコードが数値型だった場合、文字列に変換
            if(is_int($code)) $this->code = strval($code);
            else $this->code = $code;
        }
    }

    public function getCode() {
        return $this->code;
    }
    public function setCode($value) {
        //与えられたコードが数値型だった場合、文字列に変換
        if(is_int($value)) $this->code = strval($value);
        else $this->code = $value;
    }
    public function isValid() {
        if(preg_match("/^[A-Z0-9]{10}$/", $this->code) == 1 ) {
            ChromePhp::log('asinかISBNかも^^');
            if(preg_match("/^[0-9]{10}$/", $this->code) == 1 ) {
                ChromePhp::log('ISBN-10かも^^');
            } else {
                ChromePhp::log('asinかも^^');
            }

            return true;
        } else if(preg_match("/^[0-9]{13}$/", $this->code) == 1 ) { 
            ChromePhp::log('JANコードかも^^');
            if(validateForJAN($this->code))
            return true;
        } else {
            ChromePhp::log('asinじゃない;;');
            return false;
        }
    }
    public function isEAN() {
        if(preg_match("/^[0-9]{13}$/", $this->code) == 1 ) {
            if($this->validateMod($this->code), 10, 3, true) {
                $header = substr($this->code, 0, 3);
                if(substr($header, 0, 1) == '0') {
                    substr($header, 1, 1) == '0';
                    if() {

                    }
                    if($this->validateMod($this->code), 10, 3, true) return true;
                }

                return true;
            }
            else return false;
        }
    }
    // モジュラス/ウェイト
    // number, modulus, weight, isEven(偶数ならtrue)
    protected function validateMod($num, $modulus, $weight, $isEven) {
        $code = $num;
        //与えられたコードが数値型だった場合、文字列に変換
        if(is_int($num)) $code = strval($num);

        $arr = str_split($code);
        $origincd = array_pop($arr);// 元のチェックデジットを取り出す

        //チェックデジットの計算
        $odd = 0;
        $mod = 0;
        for($i=0;$i<count($arr);$i++){
            if(($i+1) % 2 == 0) $mod += intval($arr[$i]);//偶数の総和
            else $odd += intval($arr[$i]);//奇数の総和
        }
        //偶数の和を3倍+奇数の総和を加算して、下1桁の数字を10から引く
        if($isEven) $cd = $modulus - intval(substr((string)($mod * $weight) + $odd,-1));
        else $cd = $modulus - intval(substr((string)($odd * $weight) + $mod,-1));
        //10なら1の位は0なので、0を返す。
        $cd === $modulus ? 0 : $cd;

        if($cd == intval($origincd)) return true;
        else return false;
    }
    public function isJAN() {
        if($this->isEAN()) {
            $national = substr($this->code, 0, 2);
            if($national == '45' || $national == '49') return true;
            else return false;
        } else {
            return false;
        }
    }
    public function isISBN10() {
        if(preg_match("/^[0-9]{10}$/", $this->code) == 1 ) return true;
        else return false;
    }
    public function isISBN13() {
        if(preg_match("/^[0-9]{13}$/", $this->code) == 1 ) {
            $header = substr($this->code, 0, 3);
            if($header == '978' || $header == '979') {
                if($this->validateMod($this->code), 10, 3, true) return true;
            }
            else return false;
        }
        else return false;
    }
    public function isUPC() {
        if(preg_match("/^[0-9]{13}$/", $this->code) == 1 ) return true;
        else return false;
    }

    public function isASIN() {
        if(preg_match("/^[A-Z0-9]{10}$/", $this->code) == 1 ) return true;
        else return false;
    }
}

Class APAGetter {
    //アクセスキー
    const ACCESS_KEY_ID = 'AKIAJELL6GKEYPNFJGHQ';
    //シークレットキー
    const SECRET_ACCESSKEY = 'KFMuAY/OSYkt0eN6cJZ3h0lirc41jxdh33D1Q8A1';
    //アソシエイトタグ
    const ASSOCIATE_TAG = 'hgau0ghao-22';
    //APIエンドポイントURL
    const END_POINT = 'http://ecs.amazonaws.jp/onca/xml';

    const NUMBER_OF_TRIALS = 5;// 試行回数(503対策)
    const TRIALS_MILLSECOND = 500;// 試行間隔ミリ秒(503対策)

    private $response = null;

    public function __construct($itemId=null) {
        if($itemId!=null) {
            $itemIdStr = null;
            //与えられたコードが数値型だった場合、文字列に変換
            if(is_int($itemId)) $itemIdStr = strval($itemId);

            if(preg_match("/^[A-Z0-9]{10}$/", $itemIdStr) == 1 ) {

                ChromePhp::log('asinかも^^');

            } else if(preg_match("/^[0-9]{13}$/", $itemIdStr) == 1 ) {

                ChromePhp::log('JANコードかも^^');
            } else {
                ChromePhp::log('asinじゃない;;');
                throw new RuntimeException('400 Bad Request', 400);
            }
            $this->response = $this->fetch($itemId);
        }
    }

    public function getResponse() {
        return $this->response;
    }

    // curlにてAPAへリクエストを送り、レスポンスを取得
    protected function getHttpContent($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3
        ]);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        ChromePhp::log($info);

        $errno = curl_errno($ch);
        ChromePhp::log($errno);
        $error = curl_error($ch);
        ChromePhp::log($error);
        ChromePhp::log(CURLE_OK);
        curl_close($ch);

        if(CURLE_OK !== $errno) {
            throw new RuntimeException($error, $errno);
        }

        // http code が503の場合、指定秒待って指定の回数取得しに行く
        if($info['http_code'] == 503) {
            ChromePhp::log('503');
            for ($cnt = self::NUMBER_OF_TRIALS; $cnt > 0; $cnt--){
                usleep(self::TRIALS_MILLSECOND);
                return $this->getHttpContent($url);
            }
        }

        if(!($info['http_code'] == 200 || $info['http_code'] == 201)) {
            $xml = simplexml_load_string($body);
            throw new RuntimeException($xml->Error->Code.': '.$xml->Error->Message, $info['http_code']);
        }

        return $body;
    }

    // 値をRFC3986エンコード 
    protected function rawurlencodeRFC3986($str) {
        // PHP5.3.4移行ではチルダ'~'文字をエンコードしないため
        return str_replace('%7E', '~', rawurlencode($str));
    }

    // Amazon Product Advertising API用のURLを取得
    protected function getRequestURLForAmazonPA($params) {
        //パラメータと値のペアをバイト順？で並べかえ。
        ksort($params);

        $canonicalString = 'AWSAccessKeyId='.self::ACCESS_KEY_ID;
        // //RFC 3986?でURLエンコード
        foreach ($params as $k => $v) {
            $canonicalString .= '&'.$this->rawurlencodeRFC3986($k).'='.$this->rawurlencodeRFC3986($v);
        }
        //URL分解
        $parseUrl = parse_url(self::END_POINT);
        //署名対象のリクエスト文字列を作成。
        $stringToSign = "GET\n{$parseUrl["host"]}\n{$parseUrl["path"]}\n$canonicalString";
        //RFC2104準拠のHMAC-SHA256ハッシュ化しbase64エンコード（これがsignatureとなる）
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, self::SECRET_ACCESSKEY, true));
        //URL組み立て
        $url = self::END_POINT.'?'.$canonicalString.'&Signature='.$this->rawurlencodeRFC3986($signature);
        return $url;
    }

    public function fetch($itemid) {
        $url = $this->getRequestURLForAmazonPA(array(//　パラメーター
            //共通↓
            'Service' => 'AWSECommerceService',
            'AssociateTag' => self::ASSOCIATE_TAG,
            //リクエストにより変更↓
            'Operation' => 'ItemLookup',
            'ItemId' => $itemid,
            'ResponseGroup' => 'ItemAttributes,Images',
            //署名用タイムスタンプ
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ));
        // xml取得
        $res = $this->getHttpContent($url);
        $xml = simplexml_load_string($res);
        return $xml;
    }

    //**********************************
    // XML ⇒ JSONに変換する関数
    //**********************************
    public function getResponseJson() {
        // コロンをアンダーバーに（名前空間対策）
        $this->response = preg_replace("/<([^>]+?):([^>]+?)>/", "<$1_$2>", $this->response);
        // プロトコルのは元に戻す
        $this->response = preg_replace("/_\/\//", "://", $this->response);
        // XML文字列をオブジェクトに変換（CDATAも対象とする）
        $objXml = simplexml_load_string($this->response, NULL, LIBXML_NOCDATA);
        // 属性を展開する
        $this->xmlExpandAttributes($objXml);
        // JSON形式の文字列に変換
        $json = json_encode($objXml, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        // "\/" ⇒ "/" に置換
        return preg_replace('/\\\\\//', '/', $json);
    }

    //**********************************
    // XMLタグの属性を展開する関数
    //**********************************
    protected function xmlExpandAttributes($node){
        if($node->count() > 0) {
            foreach($node->children() as $child) {
                foreach($child->attributes() as $key => $val) {
                    $node->addChild($child->getName()."@".$key, $val);
                }
                xmlExpandAttributes($child); // 再帰呼出
            }
        }
    }

    protected function isValidForItemId($num) {
        if(preg_match("/^[A-Z0-9]{10}$/", $num) == 1 ) {

            ChromePhp::log('asinかISBNかも^^');
            if(preg_match("/^[0-9]{10}$/", $num) == 1 ) {

                ChromePhp::log('ISBNかも^^');
            } else {
                
                ChromePhp::log('asinかも^^');
            }

            return true;
        } else if(preg_match("/^[0-9]{13}$/", $num) == 1 ) {

            ChromePhp::log('JANコードかも^^');

            if(validateForJAN($num))
            return true;
        } else {
            ChromePhp::log('asinじゃない;;');
            return false;
        }
    }
    
    protected function isValidForJAN($num) {
        $code = $num;
        //与えられたコードが数値型だった場合、文字列に変換
        if(is_int($num)) $code = strval($num);

        $arr = str_split($code);
        $origincd = array_pop($arr);// 元のチェックデジットを取り出す

        //チェックデジットの計算
        $odd = 0;
        $mod = 0;
        for($i=0;$i<count($arr);$i++){
            if(($i+1) % 2 == 0) $mod += intval($arr[$i]);//偶数の総和
            else $odd += intval($arr[$i]);//奇数の総和
        }
        //偶数の和を3倍+奇数の総和を加算して、下1桁の数字を10から引く
        $cd = 10 - intval(substr((string)($mod * 3) + $odd,-1));
        //10なら1の位は0なので、0を返す。
        $cd === 10 ? 0 : $cd;

        if($cd == intval($origincd)) return true;
        else return false;
    }
}

//ASIN
// $asin = 'B013JDJLDQ';

// $url = getRequestURLForAmazonPA(ACCESS_KEY_ID, secretAccessKey, array(//　パラメーター
//     //共通↓
//     'Service' => 'AWSECommerceService',
//     'AssociateTag' => ASSOCIATE_TAG,
//     //リクエストにより変更↓
//     'Operation' => 'ItemLookup',
//     'ItemId' => $asin,
//     'ResponseGroup' => 'ItemAttributes,Images',
//     //署名用タイムスタンプ
//     'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
// ));
// // xml取得
// $res = getHttpContent($url);

// //結果を表示する
// ChromePhp::log($res);

// $xml = simplexml_load_string($res);
// //結果を表示する
// ChromePhp::log($xml);

// //$json = json_encode($xml);

// $strJson = xml_to_json($res);

// ChromePhp::log($strJson);


// $item = $xml->Items->Item;
// echo "画像URL：".$item->LargeImage->URL."\n";





// Amazon Product Advertising API用のURLを取得
// function getRequestURLForAmazonPA($accessKeyId, $secretAccessKey, $params) {
//     //APIエンドポイントURL
//     $endpoint = 'http://ecs.amazonaws.jp/onca/xml';
//     //パラメータと値のペアをバイト順？で並べかえ。
//     ksort($params);
    
//     $canonicalString = 'AWSAccessKeyId='.ACCESS_KEY_ID;
//     // //RFC 3986?でURLエンコード
//     foreach ($params as $k => $v) {
//         $canonicalString .= '&'.rawurlencodeRFC3986($k).'='.rawurlencodeRFC3986($v);
//     }
//     //URL分解
//     $parse_url = parse_url($endpoint);
//     //署名対象のリクエスト文字列を作成。
//     $stringToSign = "GET\n{$parse_url["host"]}\n{$parse_url["path"]}\n$canonicalString";
//     //RFC2104準拠のHMAC-SHA256ハッシュ化しbase64エンコード（これがsignatureとなる）
//     $signature = base64_encode(hash_hmac('sha256', $stringToSign, $secretAccessKey, true));
//     //URL組み立て
//     $url = 'http://ecs.amazonaws.jp/onca/xml'.'?'.$canonicalString.'&Signature='.rawurlencodeRFC3986($signature);


//     // $params['AWSAccessKeyId'] = $accessKeyId;
//     // $params['AssociateTag'] = $associateTag;
//     //APIエンドポイントURL
//     // $endpoint = 'http://ecs.amazonaws.jp/onca/xml';

//     // //パラメータと値のペアをバイト順？で並べかえ。
//     // ksort($params);
//     // //RFC 3986?でURLエンコード
//     // $string_request = str_replace(
//     //     //array('+', '%7E'),
//     //     //array('%20', '~'),
//     //     '%7E', '~',
//     //     http_build_query($params)
//     // );
//     // //URL分解
//     // $parse_url = parse_url($endpoint);
//     // //署名対象のリクエスト文字列を作成。
//     // $string_signature = "GET\n{$parse_url["host"]}\n{$parse_url["path"]}\n$string_request";
//     // //RFC2104準拠のHMAC-SHA256ハッシュ化しbase64エンコード（これがsignatureとなる）
//     // $signature = base64_encode(hash_hmac('sha256', $string_signature, $secretAccessKey, true));
//     // //URL組み立て
//     // $url = $endpoint . '?' . $string_request . '&Signature=' . $signature;
    


//     // ChromePhp::log($url);

    
//     // //パラメータを自然順序付け・昇順で並び替え
//     // ksort($params);
//     // $canonicalString;
//     // foreach ($params as $k => $v) {
//     //     $canonicalString .= '&'.rawurlencodeRFC3986($k).'='.rawurlencodeRFC3986($v);
//     // }
//     // $canonicalString = substr($canonicalString, 1);

//     // $parsed_url = parse_url('http://ecs.amazonaws.jp/onca/xml');
//     // $stringToSign = "GET\n{$parsed_url['host']}\n{$parsed_url['path']}\n{$canonicalString}";
//     // $signature = base64_encode(hash_hmac('sha256', $stringToSign, $secretAccessKey, true));

//     // $url = 'http://ecs.amazonaws.jp/onca/xml'.'?'.$canonicalString.'&Signature='.rawurlencodeRFC3986($signature);
//     //$url = 'http://ecs.amazonaws.jp/onca/xml'.'?'.$canonicalString.'&Signature='.$signature;


//     ChromePhp::log($url);

//     return $url;
// }

// function rawurlencodeRFC3986($str) {
//     // PHP5.3.4移行ではチルダ'~'文字をエンコードしないため
//     return str_replace('%7E', '~', rawurlencode($str));
// }


// function getHttpContent($url)
// {
//     try {
//         $ch = curl_init();
//         curl_setopt_array($ch, [
//             CURLOPT_URL => $url,
//             CURLOPT_RETURNTRANSFER => true,
//             CURLOPT_TIMEOUT => 3
//         ]);
//         $body = curl_exec($ch);
//         $errno = curl_errno($ch);
//         $error = curl_error($ch);
//         curl_close($ch);
//         if (CURLE_OK !== $errno) {
//             throw new RuntimeException($error, $errno);

//         }
//         return $body;
//     } catch (Exception $e) {
//         echo $e->getMessage();
//     }
// }





?>