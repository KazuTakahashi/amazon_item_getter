<?php
/**
 * Product Advertising API から商品データを取得する。
 * 
 * @package AmazonItemGetter/APAGetter
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 */
//namespace AmazonItemGetter;

include_once 'ChromePhp.php';

// APA用アクセスキー読み込み、環境変数に格納
$path = "{$_SERVER['DOCUMENT_ROOT']}/env_vars/amazon_item_getter.pass";
//'support@sakura.ad.jp' == $_SERVER['SERVER_ADMIN']
if (file_exists($path)) $_SERVER = array_merge($_SERVER, parse_ini_file($path));

ChromePhp::log($path);

/**
 * チェックデジットが含まれたコードに対してのチェックデジット格納、及び検査
 *
 * 各種チェックデジットクラスへ継承される抽象クラス。
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
abstract Class CheckDigit {
    /**
    * チェックデジットの値、通常はString型の数字か文字が一文字、初期値は空
    *
    * @var String
    */
    protected $value = '';

    public function __construct($value) {
        if($value!==null) $this->setValue($value);
    }

    /**
     * "value"の値を返します
     *
     * @return String
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function getValue() {return $this->value;}

    /**
     * "value"の値をセットします
     *
     * @param String $value 
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function setValue($value) {$this->value = $value;}

    /**
     * 受け取ったコードのチェックデジットを計算して"value"へ格納する
     * 
     * このメソッドは抽象メソッドとし、子クラスに引き継ぐ
     *
     * @param String $code コード文字列
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    abstract public function calc($code);

    /**
     * 受け取ったコードのチェックデジットと格納されているチェックデジットを比べる
     *
     * @param String $code コード文字列
     * @return bool 真ならtrue
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function isSame($code) {
        if($this->getValue() === substr($code, -1)) return true;
        else return false;
    }
}

/**
 * チェックデジットが含まれたコードに対してのチェックデジット格納、及び検査
 *
 * モジュラス演算を使用するチェックデジットクラスへ継承される抽象クラス。
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
abstract Class CheckDigitModulus extends CheckDigit{
    /**
    * モジュラス値を格納
    *
    * @var Int
    */
    protected $modulus;

    public function __construct($value=null, $modulus=null) {
        parent::__construct($value);
        if($modulus!==null) $this->setModulus($modulus);
    }
    /**
     * "modulus"の値を返します
     *
     * @return Int
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function getModulus() {return $this->modulus;}

    /**
     * "modulus"の値をセットします
     *
     * @param Int $modulus 
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function setModulus($value) {$this->modulus = $value;}
    //abstract public function calc($code);
}

/**
 * チェックデジットが含まれたコードに対してのチェックデジット格納、及び検査
 *
 * モジュラスx/ウェイトy:z、奇数・偶数にそれぞれ重みをかけ、合計とのモジュラスの剰余からモジュラスを引いたものがチェックデジット
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
Class CheckDigitModulusMutual extends CheckDigitModulus{
    /**
    * 奇数部の重み
    *
    * @var Int
    */
    private $weightOdd;

    /**
    * 奇数部の重み
    *
    * @var Int
    */
    private $weightEven;

    public function __construct($value=null, $modulus=null, $weightOdd=null, $weightEven=null) {
        parent::__construct($value, $modulus);

        if($weightOdd!==null) $this->setWeightOdd($weightOdd);
        if($weightEven!==null) $this->setWeightEven($weightEven);
        // コードを受取チェックデジットを計算して格納
        //if($code==null && $modulus==null && $weightOdd==null && $weightEven==null) $this->calc($code);
    }

    /**
     * "weightOdd"の値を返します
     *
     * @return Int
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function getWeightOdd() {return $this->weightOdd;}

    /**
     * "weightOdd"の値をセットします
     *
     * @param Int $weightOdd 
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function setWeightOdd($value) {$this->weightOdd = $value;}

    /**
     * "weightEven"の値を返します
     *
     * @return Int
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function getWeightEven() {return $this->weightEven;}

    /**
     * "weightEven"の値をセットします
     *
     * @param Int $weightEven 
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function setWeightEven($value) {$this->weightEven = $value;}
    
    /**
     * 受け取ったコードのチェックデジットを計算して"value"へ格納する
     * 
     * モジュラスx/ウェイトy:z、モジュラスが11の場合で剰余が10の場合チェックデジットはX
     *
     * @param String $code コード文字列
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
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
            if(($i+1) % 2 === 0) $even += intval($arr[$i])*$evenw;//偶数の総和*偶数の係数
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

/**
 * チェックデジットが含まれたコードに対してのチェックデジット格納、及び検査
 *
 * モジュラスx/ウェイトy->z、先頭から指定の重みを減らしながらかけた合計とのモジュラスの剰余からモジュラスを引いたものがチェックデジット
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
final Class CheckDigitModulusOrder extends CheckDigitModulus{
    /**
    * 重み
    *
    * @var Int
    */
    private $weight;

    public function __construct($value=null, $modulus=null, $weight=null) {
        parent::__construct($value, $modulus);
        if($weight!==null) $this->setWeight($weight);

        //if($code!=null && $modulus!=null && $weight!=null)$this->calc($code);
    }

    /**
     * "weight"の値を返します
     *
     * @return Int
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function getWeight() {return $this->weight;}

    /**
     * "weight"の値をセットします
     *
     * @param Int $weight 
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function setWeight($value) {$this->weight = $value;}

    /**
     * 受け取ったコードのチェックデジットを計算して"value"へ格納する
     * 
     * モジュラスx/ウェイトy->z、モジュラスが11の場合で剰余が10の場合チェックデジットはX
     *
     * @param String $code コード文字列
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
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

/**
 * 各種コードを格納
 *
 * 各種コードクラスへ継承される抽象クラス。
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
abstract Class Code {
    /**
    * コードの値、初期値は空
    *
    * @var String
    */
    protected $value = '';

    public function __construct($value=null) {
        if($value!==null) {
            $this->setValue($value);
        }
    }

    /**
     * "value"の値を返します
     *
     * @return String
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function getValue() {return $this->value;}

    /**
     * "value"の値をセットします
     * 
     * 数値が与えられた場合、文字列に変換
     *
     * @param String|Int $value 
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function setValue($value) {
        //与えられたコードが数値型だった場合、文字列に変換
        if(is_int($value)) $this->value = strval($value);
        else $this->value = $value;
    }
}

/**
 * ASINコードを格納
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
Class CodeASIN extends Code{
    public function __construct($value=null) {
        parent::__construct($value);
    }
}

/**
 * チェックデジットを含んだコードを格納
 * 
 * チェックデジットを含んだ各種コードクラスへ継承される抽象クラス。
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
abstract Class CodeWidhCheckDigit extends Code{
    /**
    * CeckDigit型オブジェクト
    *
    * @var CeckDigit
    */
    protected $checkDigit = null;

    public function __construct($value=null) {
        parent::__construct($value);
    }

    /**
     * "checkDigit"オブジェクトの参照を返します
     *
     * @return CeckDigit
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function getCheckDigit() {return $this->checkDigit;}

    /**
     * "checkDigit"オブジェクトのクローンをセットします
     *
     * @param CeckDigit $checkDigit 
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function setCheckDigit($obj) {$this->checkDigit = clone $obj;}

    public function getValue() {
        $cd = $this->getCheckDigit();
        $cdValue = $cd->getValue();
        return $this->value . $cdValue;
    }

    /**
     * "value"の値をセットします
     * 
     * 与えられたコードのチェックデジットを検査した後、チェックデジットをcheckDegit、
     * 値をvalueにそれぞれセット、チェックデジットが間違っている場合WrongValueExceptionを投げる
     *
     * @param String $value 
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
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

/**
 * ISBN10コードを格納
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
Class CodeISBN10 extends CodeWidhCheckDigit{
    public function __construct($value=null) {
        parent::__construct();
        // 予めチェックデジットオブジェクトを格納
        $this->setCheckDigit(new CheckDigitModulusOrder(null, 10, 10));
        if($value!==null) {
            $this->setValue($value);
        }
    }

    /**
     * "value"の値をセットします
     * 
     * チェックデジットがない場合、再計算、チェックデジットがある場合、親のsetValue()を呼び出す
     *
     * @param String $value 
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function setValue($value) {
        if(mb_strlen($value) === 9) {// チェックデジットがない
            $cd = $this->getCheckDigit();
            $cd->calc($value);
            $this->value = $value;
        } else {
            parent::setValue($value);
        }
    }
}

/**
 * ISBN13コードを格納
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
Class CodeISBN13 extends CodeWidhCheckDigit{
    public function __construct($value=null) {
        parent::__construct();

        // 予めチェックデジットオブジェクトを格納
        $this->setCheckDigit(new CheckDigitModulusMutual(null, 10, 1, 3));
        if($value!==null) {
            $this->setValue($this->deleteHyphen($value));
        }
    }

    /**
     * ハイフンの除去
     *
     * @param String $value 
     * @return String
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    private function deleteHyphen($value) {
        return preg_replace('/(-)/', '', $value);
    }
}

/**
 * EANコードを格納
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
Class CodeEAN extends CodeWidhCheckDigit{
    public function __construct($value=null) {
        parent::__construct();
        // 予めチェックデジットオブジェクトを格納
        $this->setCheckDigit(new CheckDigitModulusMutual(null, 10, 1, 3));
        if($value!=null) {
            $this->setValue($value);
        }
    }
}

/**
 * JANコードを格納
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
Class CodeJAN extends CodeEAN{
    public function __construct($value=null) {
        parent::__construct();
        // 予めチェックデジットオブジェクトを格納
        $this->setCheckDigit(new CheckDigitModulusMutual(null, 10, 1, 3));
        if($value!==null) {
            $this->setValue($value);
        }
    }
}

/**
 * 不明なコードを格納
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
Class CodeUnknown extends Code{
    public function __construct($value=null) {
        parent::__construct($value);
    }
}

/**
 * コードを予め検査するクラス
 * 
 * valueには検査前のコードを、codeには検査後のコードオブジェクトを格納
 *
 * @access public
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
Class CodeInspector {
    // コードタイプ
    const CODE_ASIN = 'CodeASIN';
    const CODE_ISBN10 = 'CodeISBN10';
    const CODE_ISBN13 = 'CodeISBN13';
    const CODE_EAN = 'CodeEAN';
    const CODE_JAN = 'CodeJAN';
    const CODE_UNKNOUN = 'CodeUnknown';

    /**
    * 検査前のコード
    *
    * @var String
    */
    protected $value = '';

    /**
    * 検査後のコードオブジェクト
    *
    * @var Code
    */
    protected $code = null;

    public function __construct($value=null) {
        if($value!==null) {
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
        if(preg_match("/^[A-Z0-9]{10}$/", $this->getValue()) === 1) return true;
        else return false;
    }
    public function isISBN10() {
        if(preg_match("/^[0-9]{9}[0-9Xx]$/", $this->getValue()) === 1 ) return true;
        else return false;
    }
    public function isISBN13() {
        if(preg_match("/^(978|979|978-|979-)[0-9]{10}$/", $this->getValue()) === 1 ) return true;
        else return false;
    }
    public function isEAN() {
        if(preg_match("/^[0-9]{13}$/", $this->getValue()) === 1 ) return true;
        else return false;
    }
    public function isJAN() {
        if(preg_match("/^(45|49)[0-9]{11}$/", $this->getValue()) === 1 ) return true;
        else return false;
    }
    // ISBN13をISBN10に変換
    public function toISBN10(CodeISBN13 $obj) {
        // 先頭978|979とチェックデジットの除去
        $arr = preg_split("/^(978|979)/", $obj->getValue());
        $this->setCode(new CodeISBN10($arr[1]));
    }
}

Class APAGetter {
    //APIエンドポイントURL
    const END_POINT = 'http://ecs.amazonaws.jp/onca/xml';
    // 試行回数(503対策)
    const NUMBER_OF_TRIALS = 5;
    // 試行間隔ミリ秒(503対策)
    const TRIALS_MILLSECOND = 500;

    private $response = null;// xmlのrowデータを格納
    private $code = null;

    public function __construct($itemId=null) {
        if($itemId!==null) {
            // コードの種類を検査
            $codeInspector = new CodeInspector($itemId);
            // CodeINspectorからコードオブジェクトを取得して格納
            $code = $codeInspector->getCode();

            $typeName = get_class($code);// クラス名を取得
            

            // もしISBN-13ならISBN-10に変換を試みる
            if($typeName === 'CodeISBN13') {

            }
            $this->setCode($code);

            if($typeName === ('CodeASIN' || 'CodeISBN10')) {
                // ASINかISBN10ならAPAから商品コードを使いデータを取りに行く
                $this->fetch();
            } else {
                throw new RuntimeException('400 Bad Request', 400);
            }
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
        if($info['http_code'] === 503) {
            ChromePhp::log('503');
            for ($cnt = self::NUMBER_OF_TRIALS; $cnt > 0; $cnt--){
                usleep(self::TRIALS_MILLSECOND);
                return $this->getHttpContent($url);// 再帰呼出し
            }
        }

        if(!($info['http_code'] === 200 || $info['http_code'] == 201)) {
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

        $canonicalString = 'AWSAccessKeyId='.$_SERVER['ACCESS_KEY_ID'];
        // //RFC 3986?でURLエンコード
        foreach ($params as $k => $v) {
            $canonicalString .= '&'.$this->rawurlencodeRFC3986($k).'='.$this->rawurlencodeRFC3986($v);
        }
        //URL分解
        $parseUrl = parse_url(self::END_POINT);
        //署名対象のリクエスト文字列を作成。
        $stringToSign = "GET\n{$parseUrl["host"]}\n{$parseUrl["path"]}\n$canonicalString";
        //RFC2104準拠のHMAC-SHA256ハッシュ化しbase64エンコード（これがsignatureとなる）
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $_SERVER['SECRET_ACCESSKEY'], true));
        //URL組み立て
        $url = self::END_POINT.'?'.$canonicalString.'&Signature='.$this->rawurlencodeRFC3986($signature);
        return $url;
    }

    public function fetch() {
        $url = $this->getRequestURLForAmazonPA(array(//　パラメーター
            //共通↓
            'Service' => 'AWSECommerceService',
            'AssociateTag' => $_SERVER['ASSOCIATE_TAG'],
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

    // XML ⇒ JSONに変換
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

    // XMLタグの属性を展開する
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