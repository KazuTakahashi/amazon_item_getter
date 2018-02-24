<?php
//namespace AmazonItemGetter;
// define("ACCESS_KEY_ID"     , 'AKIAJELL6GKEYPNFJGHQ');//アクセスキー

include_once 'ChromePhp.php';

abstract Class CheckDigit {
    protected $value = '';

    public function __construct($value) {
        if($value!=null) $this->setValue($value);
    }
    public function getValue() {return $this->value;}
    public function setValue($value) {$this->value = $value;}
    // 受け取ったコードのチェックデジットを計算して格納する
    abstract public function calc($code);
    // 受け取ったコードのチェックデジットと格納されているチェックデジットを比べる、同じならtrue
    public function isSame($code) {
        if($this->getValue() == substr($code, -1)) return true;
        else return false;
    }
}
abstract Class CheckDigitModulus extends CheckDigit{
    protected $modulus;

    public function __construct($value=null, $modulus=null) {
        parent::__construct($value);
        if($modulus!=null) $this->setModulus($modulus);
    }
    public function getModulus() {return $this->modulus;}
    public function setModulus($value) {$this->modulus = $value;}
    abstract public function calc($code);
}
// モジュラスx/ウェイトy:z
// 交互に指定の重みを掛ける
Class CheckDigitModulusMutual extends CheckDigitModulus{
    private $weightOdd;
    private $weightEven;

    public function __construct($value=null, $modulus=null, $weightOdd=null, $weightEven=null) {
        parent::__construct($value, $modulus);

        if($weightOdd!=null) $this->setWeightOdd($weightOdd);
        if($weightEven!=null) $this->setWeightEven($weightEven);
        // コードを受取チェックデジットを計算して格納
        //if($code==null && $modulus==null && $weightOdd==null && $weightEven==null) $this->calc($code);
    }
    public function getWeightOdd() {return $this->weightOdd;}
    public function setWeightOdd($value) {$this->weightOdd = $value;}
    public function getWeightEven() {return $this->weightEven;}
    public function setWeightEven($value) {$this->weightEven = $value;}
    public function calc($code){
        //与えられたコードが数値型だった場合、文字列に変換
        if(is_int($code)) $code = strval($code);
        preg_replace('/(-)/', '', $code);// -(ハイフン)の除去

        $arr = str_split($code);// コード(文字列)を配列に分割して格納
        //array_pop($arr);// 元のチェックデジットを取り出す

        //チェックデジットの計算
        $odd = 0;
        $oddw = $this->getWeightOdd();
        $even = 0;
        $evenw = $this->getWeightEven();
        for($i=0;$i<count($arr);$i++){
            if(($i+1) % 2 == 0) $even += intval($arr[$i])*$evenw;//偶数の総和*偶数の係数
            else $odd += intval($arr[$i])*$oddw;//奇数の総和*奇数の係数
        }

        //偶数(係数含)+奇数(係数含)を加算して、下1桁の数字をモジュラス数から引く
        $modulus = $this->getModulus();
        $cd = $modulus-intval(substr($even+$odd,-1));
        
        //余りが10の場合
        if($modulus === 11) $cd = ($cd === 10) ? 'X' : strval($cd);// モジュラスが11の場合Xを
        else $cd = ($cd === 10) ? '0' : strval($cd);// モジュラスが10の場合0を

        $this->setValue($cd);
    }
}
// Luhn formula
// 交互に指定の重みを掛けて、各桁の合計が二桁の場合分割して加算
// final Class CheckDigitLuhnFormula extends CheckDigitModulusMutual{
//     public function __construct($value=null) {
//         parent::__construct($value);
//     }
// }
// モジュラスx/ウェイトy->z
// 先頭から指定の重みを減らしながら掛ける
final Class CheckDigitModulusOrder extends CheckDigitModulus{
    private $weight = 0;

    public function __construct($value=null, $modulus=null, $weight=null) {
        parent::__construct($value, $modulus);
        if($weight!=null) $this->setWeight($weight);

        //if($code!=null && $modulus!=null && $weight!=null)$this->calc($code);
    }
    public function getWeight() {return $this->weight;}
    public function setWeight($value) {$this->weight = $value;}
    public function calc($code){
        //与えられたコードが数値型だった場合、文字列に変換
        if(is_int($num)) $code = strval($num);
        $code = preg_replace('/(-)/', '', $code);// ハイフンの除去

        $arr = str_split($code);// コード(文字列)を配列に分割して格納
        //array_pop($arr);// 元のチェックデジットを取り出す

        //チェックデジットの計算
        $cd = 0;
        $weight = $this->getWeight();
        for($i=0, $j=$weight; $i<count($arr); $i++, $j--){
            $cd += intval($arr[$i]) * $j;
        }

        //偶数(係数含)+奇数(係数含)を加算して、下1桁の数字をモジュラス数から引く
        $modulus = $this->getModulus();
        $cd = $cd % $modulus;
        
        //モジュラスが11で余りが10の場合
        if($modulus === 11) $cd = ($cd === 10) ? 'X' : $cd;// モジュラスが11の場合Xを
        else $cd = ($cd === 10) ? '0' : $cd;// モジュラスが10の場合0を
        
        $this->setValue($cd);
    }
}

Class Code {
    protected $value = '';

    public function __construct($value=null) {
        if($value!=null) {
            $this->setValue($value);
            //$this->categorize();// タイプ別にコードタイプを格納
        }
    }

    public function getValue() {return $this->value;}
    public function setValue($value) {
        //与えられたコードが数値型だった場合、文字列に変換
        if(is_int($value)) $this->value = strval($value);
        else $this->value = $value;
    }
    // public function getType() {
    //     return $this->type;
    // }
    // public function setType($value) {
    //     $this->type = $value;
    // }
    // public function categorize() {
    //     if($this->isASIN()) {
    //         $this->setType(self::TYPE_ASIN);
    //     } else if($this->isISBN10()) {
    //         $this->setType(self::TYPE_ISBN10);
    //     } else if($this->isISBN13()) {
    //         $this->setType(self::TYPE_ISBN13);
    //     } else if($this->isISBN13H()) {
    //         $this->setType(self::TYPE_ISBN13H);
    //     } else if($this->isEAN()) { 
    //         if($this->isJAN()) {
    //             $this->setType(self::TYPE_JAN);
    //         } else {
    //             $this->setType(self::TYPE_EAN);
    //         }
    //     } else {
    //         $this->setType(self::TYPE_UNKNOUN);
    //     }
    // }
    // public function isValid() {
    //     if($this->getType() == self::TYPE_UNKNOUN || $this->getType() == self::TYPE_NONE) {
    //         return false;
    //     } 
    //     return true;
    // }
    // public function isEAN() {
    //     if(preg_match("/^[0-9]{13}$/", $this->value) == 1 ) {
    //         if($this->validateMod($this->value, 10, 3, true)){
    //             return true;
    //         }
    //         else return false;
    //     } else {
    //         return false;
    //     }
    // }
    // // // モジュラス/ウェイト
    // // // number, modulus, weight, isEven(偶数ならtrue)
    // protected function validateMod($num, $modulus, $weight, $isEven) {
    //     $code = $num;
    //     //与えられたコードが数値型だった場合、文字列に変換
    //     if(is_int($num)) $code = strval($num);

    //     $arr = str_split($code);
    //     $origincd = array_pop($arr);// 元のチェックデジットを取り出す

    //     //チェックデジットの計算
    //     $odd = 0;
    //     $mod = 0;
    //     for($i=0;$i<count($arr);$i++){
    //         if(($i+1) % 2 == 0) $mod += intval($arr[$i]);//偶数の総和
    //         else $odd += intval($arr[$i]);//奇数の総和
    //     }

    //     //偶数の和を3倍+奇数の総和を加算して、下1桁の数字を10から引く
    //     if($isEven) $cd = $modulus - intval(substr((string)($mod * $weight) + $odd,-1));
    //     else $cd = $modulus - intval(substr((string)($odd * $weight) + $mod,-1));
        
    //     //10なら1の位は0なので、0を返す。
    //     $cd = ($cd === 10) ? 0 : $cd;

    //     if($cd == intval($origincd)) return true;
    //     else return false;
    // }
    // public function isJAN() {
    //     if($this->isEAN()) {
    //         $national = substr($this->value, 0, 2);
    //         if($national == '45' || $national == '49') return true;
    //         else return false;
    //     } else {
    //         return false;
    //     }
    // }
    // public function isISBN10() {
    //     if(preg_match("/^[0-9]{9}[0-9Xx]$/", $this->value) == 1 ) return true;
    //     else return false;
    // }
    // public function isISBN13() {
    //     $value = $this->value;
    //     if(preg_match("/^(978|979)[0-9]{10}$/", $value) == 1 ) {
    //         if($this->validateMod($value, 10, 3, true)) return true;
    //         else return false;
    //     }
    //     else return false;
    // }
    // public function isISBN13H() {
    //     $value = $this->value;
    //     if(preg_match("/^(978|979)-[0-9]{10}$/", $value) == 1) {
    //         $value = str_replace('-', '', $value);
    //         if($this->validateMod($value, 10, 3, true)) return true;
    //         else return false;
    //     }
    //     else return false;
    // }
    // public function isUPC() {
    //     if(preg_match("/^[0-9]{13}$/", $this->code) == 1 ) return true;
    //     else return false;
    // }

    // public function isASIN() {
    //     if(preg_match("/^[A-Z0-9]{10}$/", $this->value) == 1) return true;
    //     else return false;
    // }

    // ISBN13をISBN10に変換
    // public function toISBN10() {
    //     // タイプがISBN-13ではない場合そのまま抜ける
    //     if(!($this->type == (self::TYPE_ISBN13 || self::TYPE_ISBN13H))) return;

    //     $value = $this->value;
    //     $arr = null;
    //     if($this->type == self::TYPE_ISBN13H) {
    //         $arr = preg_split("/^(978-|979-)|([0-9]$)/", $value);
    //     } else {
    //         $arr = preg_split("/^(978|979)|([0-9]$)/", $value);
    //     }

    //     // モジュラス11 ウェイト10-2
    //     $arr = str_split($arr[1]);

    //     $cd = 0;
    //     for($i=0, $j=10; $i<count($arr); $i++, $j--){
    //         $cd += intval($arr[$i]) * $j;
    //     }
    //     $cd = $cd % 11;
    //     //10ならXを返す。
    //     $cd = ($cd === 10) ? 'X' : $cd;
        
    //     if(is_int($cd)) $cd = strval($cd);
    //     array_push($arr, $cd);

    //     $this->setValue(implode("", $arr));
    //     $this->setType(self::TYPE_ISBN10);
    // }

    // public function calcCheckDigit($code, $method) {
    //     $modulus = 0;
    //     if($method == self:CD_MOD10W3E) {

    //     }
    //     return $cd;
    // }
}

Class CodeASIN extends Code{
    public function __construct($value=null) {
        if($value!=null) {
            parent::__construct($value);
        }
    }
}
abstract Class CodeWidhCheckDigit extends Code{
    protected $checkDigit = null;
    public function __construct($value=null) {
        if($value!=null) {
            parent::__construct($value);
        }
    }
    public function getCheckDigit() {return $this->checkDigit;}
    public function setCheckDigit($obj) {$this->checkDigit = clone $obj;}

    public function getValue() {
        $cd = $this->getCheckDigit();
        $cdValue = $cd->getValue();
        return $this->value . $cdValue;
    }
    public function setValue($value) {
        //与えられたコードが数値型だった場合、文字列に変換
        if(is_int($value)) $value = strval($value);
        // 元のコードからチェックデジットを除いて、再計算
        $valueNonCd = substr($value, 0, 12);
        $cd = $this->getCheckDigit();
        $cd->calc($valueNonCd);
        // 元のチェックデジットと計算されたチェックデジットの比較
        if(!$cd->isSame($value)) {
            throw new WrongValueException('This check degit is wrong');
        }
        // 親のsetterにて、チェックデジット無しコードをvalueに格納
        parent::setValue($valueNonCd);
    }

    //abstract protected function check($value);

    function __clone()
    {
        $this->checkDigit = clone $this->checkDigit;
    }
}
Class CodeISBN10 extends CodeWidhCheckDigit{
    public function __construct($value=null) {
        if($value!=null) {
            parent::__construct($value);
        }
    }
}
Class CodeISBN13 extends CodeWidhCheckDigit{
    public function __construct($value=null) {
        parent::__construct();

        // 予めチェックデジットオブジェクトを格納
        $this->setCheckDigit(new CheckDigitModulusMutual(null, 10, 1, 3));
        if($value!=null) {
            $this->setValue($this->deleteHyphen($value));
            // 元のコードからチェックデジットを除いて、再計算
            // $valueNonCd = substr($this->deleteHyphen($value), 0, 12);
            // $cd = $this->getCheckDigit();
            // $cd->calc($valueNonCd);
            // // 元のチェックデジットと計算されたチェックデジットの比較
            // if(!$cd->isSame($value)) {
            //     throw new WrongValueException('This check degit is wrong');
            // }
        }
    }
    // ハイフンの除去
    private function deleteHyphen($value) {
        return preg_replace('/(-)/', '', $value);
    }

    public function toISBN10($obj) {
        $value = $this->getValue();
        $arr = null;
        // 先頭978|979とチェックデジットの除去
        $arr = preg_split("/^(978|979)|([0-9]$)/", $value);

        $cd = new CheckDigitModulusMutual(null, 10, 1, 3);
        $cd->calc($arr[1]);

        // モジュラス11 ウェイト10-2
        $arr = str_split($arr[1]);

        $cd = 0;
        for($i=0, $j=10; $i<count($arr); $i++, $j--){
            $cd += intval($arr[$i]) * $j;
        }
        $cd = $cd % 11;
        //10ならXを返す。
        $cd = ($cd === 10) ? 'X' : $cd;
        
        if(is_int($cd)) $cd = strval($cd);
        array_push($arr, $cd);

        $this->setValue(implode("", $arr));
        $this->setType(self::TYPE_ISBN10);
    }
}
Class CodeEAN extends CodeWidhCheckDigit{
    public function __construct($value=null) {
        if($value!=null) {
            parent::__construct($value);
            // $this->setCheckDigit(new CheckDigitModulusMutual($value, 10, 1, 3));
        }

    }
}
Class CodeJAN extends CodeEAN{
    public function __construct($value=null) {
        if($value!=null) {
            parent::__construct($value);
            // $this->setCheckDigit(new CheckDigitModulusMutual($value, 10, 1, 3));
        }
       
    }
}
Class CodeUnknown extends Code{
    public function __construct($value=null) {
        if($value!=null) {
            parent::__construct($value);
        }
        
    }
}

Class CodeInspector {
    // コードタイプ
    const CODE_ASIN = 'CodeASIN';
    const CODE_ISBN10 = 'CodeISBN10';
    const CODE_ISBN13 = 'CodeISBN13';
    const CODE_EAN = 'CodeEAN';
    const CODE_JAN = 'CodeJAN';
    const CODE_UNKNOUN = 'CodeUnknown';

    // チェックデジットメソッド
    // const CD_MOD9W2O = 900;// Modulus9/wight2(odd)
    // const CD_MOD9W2E = 901;// Modulus9/wight2(even)
    // const CD_MOD10W2O = 1000;// Modulus10/wight2(odd)
    // const CD_MOD10W2E = 1001;// Modulus10/wight2(even)
    // const CD_LUHN = 1002;// LUHN formula
    // const CD_MOD10W3O = 1010;// Modulus10/wight3(odd)
    // const CD_MOD10W3E = 1011;// Modulus10/wight3(even), EAN/JAN/ISBN-13
    // const CD_MOD11W102 = 1102;// Modulus11/wight10-2, ISBN-10

    protected $value = '';
    protected $code = null;

    public function __construct($value=null) {
        if($value!=null) {
            $this->setValue($value);
        }
    }
    function __clone(){
        $this->code = clone $this->code;
    }
    public function getValue() {return $this->value;}
    public function setValue($value) {
        //与えられたコードが数値型だった場合、文字列に変換
        if(is_int($value)) $this->value = strval($value);
        else $this->value = $value;
        $this->categorize();// タイプ別にコードタイプを格納
    }
    public function getCode() {return $this->code;}
    public function setCode($obj) {$this->code = clone $obj;}

    public function categorize() {
        $value = $this->getValue();
        $valuenc = substr($value, -1);

        if($this->isASIN()) {
            $this->setCode(new CodeASIN($value));
        } else if($this->isISBN10()) {
            $cd = new CheckDigitModulusOrder(null, 10, 10);
            $cd->calc($valuenc);
            if($cd->isSame($value)) {
                $this->setCode(new CodeISBN10($value));
            }
        } else if($this->isISBN13()) {
            // $cd = new CheckDigitModulusMutual(null, 10, 1, 3, $valuenc);
            // if($cd->isSame($value)) {
            //     $this->setCode(new CodeISBN13($value));
            // }
            $this->setCode(new CodeISBN13($value));
        } else if($this->isEAN()) {
            $cd = new CheckDigitModulusMutual(null, 10, 1, 3);
            $cd->calc($valuenc);
            if($cd->isSame($value)) {
                if($this->isJAN()) {
                    $this->setCode(new CodeJAN($value));
                } else {
                    $this->setCode(new CodeEAN($value));
                }
            }
        } else {
            $this->setCode(new CodeUnknown($value));
        }
    }

    public function isASIN() {
        if(preg_match("/^[A-Z0-9]{10}$/", $this->value) == 1) return true;
        else return false;
    }
    public function isISBN10() {
        if(preg_match("/^[0-9]{9}[0-9Xx]$/", $this->value) == 1 ) return true;
        else return false;
    }
    public function isISBN13() {
        $value = $this->value;
        if(preg_match("/^(978|979|978-|979-)[0-9]{10}$/", $value) == 1 ) return true;
        else return false;
    }
    public function isEAN() {
        if(preg_match("/^[0-9]{13}$/", $this->value) == 1 ) return true;
        else return false;
    }
    public function isJAN() {
        if(preg_match("/^(45|49)[0-9]{11}$/", $this->value) == 1 ) return true;
        else return false;
    }
    // ISBN13をISBN10に変換
    public function toISBN10() {
        // $code = $this->getCode();
        // // タイプがISBN-13ではない場合そのまま抜ける
        // // $typeName = get_class($code);// クラス名を取得
        // // if(!($typeName == 'CodeISBN13')) return;

        // $value = $this->value;
        // $arr = null;
        // if($this->type == self::TYPE_ISBN13H) {
        //     $arr = preg_split("/^(978-|979-)|([0-9]$)/", $value);
        // } else {
        //     $arr = preg_split("/^(978|979)|([0-9]$)/", $value);
        // }

        // // モジュラス11 ウェイト10-2
        // $arr = str_split($arr[1]);

        // $cd = 0;
        // for($i=0, $j=10; $i<count($arr); $i++, $j--){
        //     $cd += intval($arr[$i]) * $j;
        // }
        // $cd = $cd % 11;
        // //10ならXを返す。
        // $cd = ($cd === 10) ? 'X' : $cd;
        
        // if(is_int($cd)) $cd = strval($cd);
        // array_push($arr, $cd);

        // $this->setValue(implode("", $arr));
        // $this->setType(self::TYPE_ISBN10);
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
    // 試行回数(503対策)
    const NUMBER_OF_TRIALS = 5;
    // 試行間隔ミリ秒(503対策)
    const TRIALS_MILLSECOND = 500;

    private $response = null;// xmlのrowデータを格納
    private $code = null;

    public function __construct($itemId=null) {
        if($itemId!=null) {
            // コードの種類を検査
            $codeInspector = new CodeInspector($itemId);
            // CodeINspectorからコードオブジェクトを取得して格納
            $code = $codeInspector->getCode();

            $typeName = get_class($code);// クラス名を取得
            

            // もしISBN-13ならISBN-10に変換を試みる
            if($typeName == 'CodeISBN13') {

            }
            $this->setCode($code);

            if($typeName == ('CodeASIN' || 'CodeISBN10')) {
                // ASINかISBN10ならAPAから商品コードを使いデータを取りに行く
                $this->fetch();
            } else {
                throw new RuntimeException('400 Bad Request', 400);
            }



            // $this->setCode(new Code($itemId));
            // $code = $this->getCode();
            // // 商品コードの有効性を検証
            // $type = $code->getType();

            // // もしISBN-13ならISBN-10に変換を試みる
            // if($type == (self::TYPE_ISBN13 || self::TYPE_ISBN13H)) {
            //     $code->toISBN10();
            // }
            // if($type == (self::TYPE_ASIN || self::TYPE_ISBN10)) {
            //     // ASINかISBN10ならAPAから商品コードを使いデータを取りに行く
            //     $this->fetch();
            // } else {
            //     throw new RuntimeException('400 Bad Request', 400);
            // }
        }
    }

    public function getResponse() {
        return $this->response;
    }
    public function getResponseToXML() {
        return simplexml_load_string($this->response);
    }
    public function getCode() {
        return $this->code;
    }
    public function setCode($value) {
        $this->code = clone $value;
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

        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if(CURLE_OK !== $errno) {
            throw new RuntimeException($error, $errno);
        }

        // http code が503の場合、指定秒待って指定の回数取得しに行く
        if($info['http_code'] == 503) {
            ChromePhp::log('503');
            for ($cnt = self::NUMBER_OF_TRIALS; $cnt > 0; $cnt--){
                usleep(self::TRIALS_MILLSECOND);
                return $this->getHttpContent($url);// 再帰呼出し
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

    public function fetch() {
        $url = $this->getRequestURLForAmazonPA(array(//　パラメーター
            //共通↓
            'Service' => 'AWSECommerceService',
            'AssociateTag' => self::ASSOCIATE_TAG,
            //リクエストにより変更↓
            'Operation' => 'ItemLookup',
            'ItemId' => $this->code->getValue(),
            'ResponseGroup' => 'ItemAttributes,Images',
            //署名用タイムスタンプ
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ));
        // xml取得
        $res = $this->getHttpContent($url);
        $this->response = $res;
        //$this->response = simplexml_load_string($res);// xmlオブジェクトに変換して格納
    }

    //**********************************
    // XML ⇒ JSONに変換する関数
    //**********************************
    public function getResponseToJson() {
        $xml = $this->response;// xmlの生データ
        // コロンをアンダーバーに（名前空間対策）
        $xml = preg_replace("/<([^>]+?):([^>]+?)>/", "<$1_$2>", $xml);
        // プロトコルのは元に戻す
        $xml = preg_replace("/_\/\//", "://", $xml);
        // XML文字列をオブジェクトに変換（CDATAも対象とする）
        $objXml = simplexml_load_string($xml, NULL, LIBXML_NOCDATA);
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
                $this->xmlExpandAttributes($child); // 再帰呼出
            }
        }
    }

    // protected function isValidForItemId($num) {
    //     if(preg_match("/^[A-Z0-9]{10}$/", $num) == 1 ) {

    //         ChromePhp::log('asinかISBNかも^^');
    //         if(preg_match("/^[0-9]{10}$/", $num) == 1 ) {

    //             ChromePhp::log('ISBNかも^^');
    //         } else {
                
    //             ChromePhp::log('asinかも^^');
    //         }

    //         return true;
    //     } else if(preg_match("/^[0-9]{13}$/", $num) == 1 ) {

    //         ChromePhp::log('JANコードかも^^');

    //         if(validateForJAN($num))
    //         return true;
    //     } else {
    //         ChromePhp::log('asinじゃない;;');
    //         return false;
    //     }
    // }
    
    // protected function isValidForJAN($num) {
    //     $code = $num;
    //     //与えられたコードが数値型だった場合、文字列に変換
    //     if(is_int($num)) $code = strval($num);

    //     $arr = str_split($code);
    //     $origincd = array_pop($arr);// 元のチェックデジットを取り出す

    //     //チェックデジットの計算
    //     $odd = 0;
    //     $mod = 0;
    //     for($i=0;$i<count($arr);$i++){
    //         if(($i+1) % 2 == 0) $mod += intval($arr[$i]);//偶数の総和
    //         else $odd += intval($arr[$i]);//奇数の総和
    //     }
    //     //偶数の和を3倍+奇数の総和を加算して、下1桁の数字を10から引く
    //     $cd = 10 - intval(substr((string)($mod * 3) + $odd,-1));
    //     //10なら1の位は0なので、0を返す。
    //     $cd === 10 ? 0 : $cd;

    //     if($cd == intval($origincd)) return true;
    //     else return false;
    // }
}

class WrongValueException extends RuntimeException {
    public function __construct($message, $code = 0, Exception $previous = null){
      parent::__construct($message, $code, $previous);
    }
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
  }

?>