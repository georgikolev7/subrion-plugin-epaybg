<?php

class iaEpay extends abstractCore
{
	const DEMO_URL = 'https://demo.epay.bg/';
	const PAYMENT_URL = 'https://www.epay.bg/';
	
	protected $_url = self::PAYMENT_URL;
	
	public function init()
    {
        parent::init();
		
		$this->_url = (!$this->iaCore->get('epay_demo_mode')) ? self::PAYMENT_URL : self::DEMO_URL;

		$this->_merchantCode = $this->iaCore->get('epay_api_merchant_code');
        $this->_currencyCode = $this->iaCore->get('epay_currency_code');
		$this->_apiToken = $this->iaCore->get('epay_api_token');
		$this->_apiSecret = $this->iaCore->get('epay_api_secret');
		$this->_apiEmail = $this->iaCore->get('epay_api_user');
    }
	
	public function setPaymentURL()
	{
		return (!$this->iaCore->get('epay_demo_mode')) ? self::PAYMENT_URL : self::DEMO_URL;
	}
	
	public function getPaymentInfo($data)
	{
		$_SECRET = $this->_apiSecret;
		$_MIN = $this->_merchantCode;
		
		$_INVOICE = sprintf("%.0f", $data['INVOICE_ID']);
		$_SUM = $data['SUM'];
		$_EXPDATE  = '01.08.2020';
		$_DESCR = $data['DESCR'];
		
		$_DATA = "
		MIN={$_MIN}
		MERCHANTCODE={$this->_merchantCode}
		INVOICE={$_INVOICE}
		CURRENCY=BGN
		AMOUNT={$_SUM}
		EXP_TIME={$_EXPDATE}
		DESCR={$_DESCR}
		";
		
		$ENCODED  = base64_encode($_DATA);
		$CHECKSUM = self::hmac('sha1', $ENCODED, $_SECRET);
	}
	
	public function getPaymentData(array $planInfo, $description, $returnURL, $cancelURL, $transaction)
	{
		$_SECRET = $this->_apiSecret;
		$_MIN = $this->_merchantCode;
		
		$_INVOICE = $transaction['item_id'];
		$_SUM = $transaction['amount'];
		$_EXPDATE  = '01.08.2020';
		$_DESCR = $description;
		
		$_DATA = "
		MIN={$_MIN}
		MERCHANTCODE={$this->_merchantCode}
		INVOICE={$_INVOICE}
		CURRENCY=BGN
		AMOUNT={$_SUM}
		EXP_TIME={$_EXPDATE}
		DESCR={$_DESCR}
		";
		
		$ENCODED  = base64_encode($_DATA);
		$CHECKSUM = self::hmac('sha1', $ENCODED, $_SECRET);
		
		$post = [
			'PAGE' => 'paylogin',
			'ENCODED' => $ENCODED,
			'CHECKSUM' => $CHECKSUM,
			'URL_OK' => $data['URL_OK'],
			'URL_CANCEL' => $data['URL_CANCEL']
		];
		
		return $post;
	}
	
	public function createPayment(array $planInfo, $description, $returnURL, $cancelURL, $transaction)
    {
        $params = [
            'URL_OK' => $returnURL,
            'URL_CANCEL' => $cancelURL,
            'SUM' => (float)$planInfo['cost'],
            'CURRENCY' => $this->_currencyCode,
            'DESCR' => $description,
            'URL_CANCEL' => $cancelURL,
            'URL_OK' => $returnURL,
			'INVOICE_ID' => $transaction['id']
        ];

        if (iaUsers::hasIdentity()) {
            $userInfo = iaUsers::getIdentity(true);

            $params['email'] = $userInfo['email'];
        }

        return $this->_apiCall($params);
    }
	
	public function getError()
    {
        return '';
    }
	
	public function _apiCall($data)
	{
		
	}
	
	public function handleIpn($data)
	{
		$status = $data['status'];
		
		$iaTransaction = $this->iaCore->factory('transaction');
		$iaBooking = $this->iaCore->factory('booking');
		
		$transaction_id = $iaTransaction->getBy('item_id', $data['item_id']);
		$transactionData = [];
		
		switch ($status)
		{
			case 'PAID':
			
				$transactionData = array(
					'status' => iaTransaction::PASSED
				);
				
				$this->iaDb->update(array('status' => 'paid'), iaDb::convertIds($data['item_id']), null, 'booking');
				$iaBooking->sendVoucherToClient($data['item_id']);
			
			break;
			
			case 'DENIED':
			
				$transactionData = array(
					'status' => iaTransaction::FAILED
				);
			
			break;
			
			case 'EXPIRED':
			
				$transactionData = array(
					'status' => iaTransaction::FAILED
				);
			
			break;
		}
		
		$iaTransaction->addIpnLogEntry('epay', $data, $status);
		$iaTransaction->update($transactionData, $transaction_id);
	}
	
    public static function hmac($algo, $data, $passwd)
	{
	    $algo = strtolower($algo);
	    $p = array('md5' => 'H32','sha1' => 'H40');
	    if(strlen($passwd)>64) $passwd = pack($p[$algo],$algo($passwd));
	    if(strlen($passwd)<64) $passwd = str_pad($passwd,64,chr(0));
	
	    $ipad = substr($passwd,0,64) ^ str_repeat(chr(0x36),64);
	    $opad = substr($passwd,0,64) ^ str_repeat(chr(0x5C),64);
	
	    return ($algo($opad.pack($p[$algo],$algo($ipad.$data))));
	}
}