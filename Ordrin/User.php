<?php

/* User API */
class User extends OrdrinApi {
    function __construct($key,$base_url){
      $this->_key = $key;
      $this->base_url = $base_url;
    }

    function create($email, $password, $fName, $lName) {
        
    	$_errors = array();
    	$validation = new Validation();
    	$validation -> validateEmail($email);
    	$validation -> validateRequiredField($password, 'password');
    	$validation -> validateRequiredField($fName, 'First Name');
    	$validation -> validateRequiredField($lName, 'Last Name');
        if(!empty($validation -> errors)) {
          throw new OrdrinExceptionBadValue($_errors);
        }
        
        return $this->_call_api('POST',
                                array(
                                 'u',
                                 $email
                                ),
                                array(
                                 'pw' => $password,
                                 'first_name' => $fName,
                                 'last_name' => $lName
                             )
                        );
    }

    function getAccountInfo() {
        return $this->_call_api('GET',
                               array(
                                 'u',
                                 self::$_email
                             ),
                             array(
                                'password' => self::$_password
                             ),
                             true
                        );
    }

    function getAddress($addrNick = '') {
        if (!empty($addrNick)) {
            return $this->_call_api('GET',
                                    array(
                                      'u',
                                     self::$_email,
                                     'addrs',
                                     $addrNick,
                                    ),
                                    null,
                                    true
                            );
        } else {
            return $this->_call_api('GET',
                                   array(
                                       'u',
                                  self::$_email,
                                      'addrs',
                                 ),
                                 null,
                                 true
                            );

        }
    }

    function setAddress($nick, $addr) {
        $addr->validate();

        return $this->_call_api('PUT',
                               array(
                                'u',
                                self::$_email,
                                'addrs',
                                $nick
                             ),
                             array(
                                 'addr' => $addr->street,
                                 'addr2' => $addr->street2,
                                 'city' => $addr->city,
                                 'state' => $addr->state,
                                 'zip' => $addr->zip,
                                 'phone' => $addr->phone,
                             ),
                             true
                        );
    }

    function deleteAddress($addrNick) {
        return $this->_call_api('DELETE',
                               array(
                                    'u',
                                    self::$_email,
                                    'addrs',
                                    $addrNick
                              ),
                              null,
                              true
                        );
    }

    function getCard($cardNick = '') {
        if (!empty($cardNick)) {
            return $this->_call_api('GET',
                                    array(
                                      'u',
                                      self::$_email,
                                      "ccs",
                                      $cardNick
                                 ),
                                 null,
                                 true
                            );
        } else {
            return $this->_call_api('GET',
                                   array(
                                     'u',
                                     self::$_email,
                                     'ccs'
                                  ),
                                 null,
                                 true
                            );
        }
    }

    function setCard($cardNick, $name, $number, $cvc, $expiryMonth, $expiryYear, $addr) {
        $validation = new Validation();
        $validation -> validateExpirationDate($expiryMonth . '/' . $expiryYear);
        $validation -> validateCVC($this->cvc);
        $validation -> validateCardNumber($this->number);
    	$_errors = $validation->errors;
        try {
          $addr->validate();
        } catch (OrdrinExceptionBadValue $ex) {
          $_errors[] = $ex.__toString();
        }
        
        if(!empty($_errors)) {
          throw new OrdrinExceptionBadValue($_errors);
        }
        return $this->_call_api('PUT',
                               array(
                                 'u',
                                 self::$_email,
                                 'ccs',
                                 $cardNick,
                             ),
                             array(
                                 'name' => $name,
                                 'number' => $number,
                                 'cvc' => $cvv,
                                 'expiry_month' => $expiryMonth,
                                 'expiry_year' => $expiryYear,
                                 'bill_addr' => $addr->street,
                                 'bill_addr2' => $addr->street2,
                                 'bill_city' => $addr->city,
                                 'bill_state' => $addr->state,
                                 'bill_zip' => $addr->zip,
                             ),
                             true
                        );
    }

    function deleteCard($cardNick) {
        return $this->_call_api('DELETE',
                                array(
                                  'u',
                                  self::$_email,
                                  'ccs',
                                  $cardNick
                                ),
                                null,
                                true
                        );
    }

    function getOrderHistory($orderID='') {
        if (!empty($orderID)) return $this->_call_api('GET',
                                                       array('u',
                                                       self::$_email,
                                                       'order',
                                                       $orderID
                                                   ),
                                                   null,
                                                   true
                                              );
        else return $this->_call_api('GET',
                                     array(
                                        'u',
                                        self::$_email,
                                        'orders'
                                    ),
                                    null,
                                    true
                             );
    }

    function updatePassword($password) {
        return $this->_call_api('PUT',
                                array(
                                 'u',
                                 self::$_email,
                                 'password'
                                ),
                                array(
                                 'password' => $password
                               ),
                               true
                        );
    }
}

