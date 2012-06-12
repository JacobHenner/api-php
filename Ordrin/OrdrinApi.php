<?php
ini_set("display_errors",1);

function __autoload($name) {
    require_once($name . '.php');
}

/**
 * Ordr.in API wrapper.
 *
 * @author   Ricky Robinett <ricky@ordr.in>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 */
class OrdrinApi {
    const CUSTOM_SERVERS = -1;
    const TEST_SERVERS = 0;
    const PROD_SERVERS = 1; 

    private $_key, $_server; 

    protected $userAgent = "ordrin-php/2.0";
    protected $restaurant_url, $user_url, $order_url, $_email, $_password;
    

    /**
     * Constructor.
     *
     * @param string    $key              Developer API Key
     * @param string    $servers          Servers to use [CUSTOM_SERVER|TEST_SERVERS|PROD_SERVERS]
     * @param string    $restaurant_url   Custom restaurant URL to use
     * @param string    $user_url         Custom user URL to use
     * @param string    $order_url        Customer order URL to use
     */
    function __construct($key, $servers, $restaurant_url = null, $user_url = null, $order_url = null) {
        $this->_key = $key;

        if(!isset($servers)){
          //TODO: Throw exception
        }

        switch($servers) {
          case self::CUSTOM_SERVERS:
            if(empty($restaurant_url) || empty($user_url) || empty($order_url)) {
              //TODO: Throw error if no urls set
            }
            
            $this->restaurant_url = $restaurant_url;
            $this->user_url = $user_url;
            $this->order_url = $order_url;
            break;
          case self::PROD_SERVERS:
            $this->restaurant_url = "https://r.ordr.in";
            $this->user_url = "https://u.ordr.in";
            $this->order_url = "https://o.ordr.in";
            break;
          case self::TEST_SERVERS:
            $this->restaurant_url = "https://r-test.ordr.in";
            $this->user_url = "https://u-test.ordr.in";
            $this->order_url = "https://o-test.ordr.in";
            break;
        }

        $this->restaurant = new Restaurant($key, $this->restaurant_url);
        $this->user = new User($key, $this->user_url);
        $this->order = new Order($key, $this->order_url);
    }


    /**
     * Get the configuration information the wrapper's using (for debug purposes).
     *
     * @return Array  An array containing the API Key, Restaurant URL, User URL and Order URL the wrapper is using.
     */
    public function getConfig() {
      return Array("API key"=>$this->_key,
                   "Restaurant URL"=>$this->restaurant_url,
                   "User URL"=>$this->user_url,
                   "Order URL"=>$this->order_url);
    }

    /**
     * Make a call to the Ordr.in REST API.
     *
     * @param string $method    The method being used for the request [GET,POST,PUT,DELETE]   
     * @param array  $params    Url parmas to be used for the request
     * @param array  $data      Data to be posted with the request
     * @param BOOL   $login     Whether to use user authentication for this request
     *
     * @return object An object containing the response information
     */
    protected function _call_api($method, $params, $data=null, $login=null) {
      $uri = '';
      foreach($params as $param) {
        $uri .= "/".rawurlencode($param);
      }
      $request_url = $this->base_url.$uri;
      rtrim($request_url,"//");

      $headers = array();
      if($this->_key) {
        $headers[] = 'X-NAAMA-CLIENT-AUTHENTICATION: id="'.$this->_key.'", version="1"';
      }

      if($login) {
        $headers[] = 'X-NAAMA-AUTHENTICATION: username="' . $this->_email . '", response="' . hash('sha256', $this->_password . $this->_email . $uri) . '", version="1"';
      }

      $headers[] = 'Content-Type: application/x-www-form-urlencoded';
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_USERAGENT,$this->userAgent);
      curl_setopt($ch, CURLOPT_URL, $request_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLINFO_HEADER_OUT, true);

      curl_setopt($ch, CURLOPT_VERBOSE, 1);

      if ($method == 'GET') {
        $respBody = curl_exec($ch);
        $respInfo = curl_getinfo($ch);
      }

      if ($method == 'POST') {
        $post_fields='';
        if(isset($data)){
          $post_fields  = http_build_query($data);
          curl_setopt($ch,CURLOPT_POST,true);
          curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);
        }

        $respBody = curl_exec($ch);
        $respInfo = curl_getinfo($ch);
      }

      if($method == 'PUT') {
        $put_fields = http_build_query($data);
        $reqLen = strlen($put_fields);
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, $put_fields);
        rewind($fh);

        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $reqLen);
        curl_setopt($ch, CURLOPT_PUT, true);

        $respBody = curl_exec($ch);
        $respInfo = curl_getinfo($ch);
      }

      if($method == 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $respBody = curl_exec($ch);
        $respInfo = curl_getinfo($ch);
      }

      curl_close($ch);

      return json_decode($respBody);
    }

    /* formatting helpers */
    public function format_datetime($date_time) {
      if(strtoupper($date_time) == 'ASAP') {
        return 'ASAP';
      } else {
        $timestamp = strtotime($date_time);
        return date('m-d+H:i',$timestamp);
      }
    }

    public function format_date($date) {
      if(strtoupper($date) == 'ASAP') {
        return 'ASAP';
      } else {
        $timestamp = strtotime($date);
        return date('m-d',$timestamp);
      }
    }

    public function format_time($time) {
      if(!empty($time)) {
        $timestamp = strtotime($time);
        return date('H:i',$timestamp);
      }
    }

    /* Data Structure Helpers */
    static public function address($addr, $city, $state, $zip, $phone, $addr2 = null) {
      return new Address($addr, $city, $state, $zip, $phone, $addr2);
    }

    static public function creditCard($name, $expMonth, $expYear, $address, $number, $cvc) {
      return new CreditCard($name, $expMonth, $expYear, $address, $number, $cvc);
    }

    static public function trayItem($itemId, $quantity, $options = null) {
      return new TrayItem($itemId, $quantity, $options);
    }

    static public function tray($items = null) {
      return new Tray($items);
    }
}
