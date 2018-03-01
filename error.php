<?php
/**
 * Exceptionサブクラス群。
 * 
 * @package AmazonItemGetter/APAGetter
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 */


/**
 * WrongValueExceptionクラス
 * 
 * 間違った値が格納されたとき。RuntimeExceptionを継承する
 *
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
class WrongValueException extends RuntimeException {
    /**
     * @constructor 
     * @param string $message
     * @param integer $code
     * @param Exception $previous
     */
    public function __construct($message, $code = 0, Exception $previous = null){
      parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

/**
 * HttpExceptionクラス
 * 
 * サーバーからのHttpステータスコード4xx系・5xx系の場合に使用
 *
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
class HttpException extends Exception {
    /**
    * チェックデジットの値、通常はString型の数字か文字が一文字、初期値は空
    *
    * @var integer
    */
    private $httpCode;

    /**
     * @constructor 
     * @param string $message
     * @param integer $code
     * @param Exception $previous
     */
    public function __construct($message, $httpCode=0, $code = 0, Exception $previous = null){
      parent::__construct($message, $code, $previous);
      $this->setHttpCode($httpCode);
    }

    /**
     * "httpCode"の値を返します
     *
     * @return string
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function getHttpCode() {return $this->value;}

    /**
     * "httpCode"の値をセットします
     *
     * @param string $httpCode 
     * @return void
     * @author Kazu Takahashi <kazuki@send.mail>
     */
    public function setHttpCode($value) {$this->httpCode = $value;}

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

/**
 * HttpClientExceptionクラス
 * 
 * サーバーからのHttpステータスコード4xx系の場合に使用
 *
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
class HttpClientException extends HttpException {
    /**
     * エラーメッセージ連想配列定数
     * 
     * @const string
     */
    const MESSAGE = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request ',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        451 => 'Unavailable For Legal Reasons'
    ];

    /**
     * @constructor 
     * @param string $message
     * @param integer $httpCode
     * @param integer $code
     * @param Exception $previous
     */
    public function __construct($httpCode=0, $code = 0, Exception $previous = null){
      parent::__construct(self::MESSAGE($httpCode), $httpCode, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

/**
 * HttpServerExceptionクラス
 * 
 * サーバーからのHttpステータスコード5xx系の場合に使用
 *
 * @author Kazu Takahashi <kazuki@send.mail>
 * @copyright Copyright (c) 2018 Kazuki Takahashi
 * @package AmazonItemGetter/APAGetter
 */
class HttpServerException extends HttpException {
    /**
     * エラーメッセージ連想配列定数
     * 
     * @const string
     */
    const MESSAGE = [
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended'
    ];

    /**
     * @constructor 
     * @param string $message
     * @param integer $httpCode
     * @param integer $code
     * @param Exception $previous
     */
    public function __construct($httpCode=0, $code = 0, Exception $previous = null){
        parent::__construct(self::MESSAGE($httpCode), $httpCode, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
 
?>