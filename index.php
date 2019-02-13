<?php

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaEpay = $iaCore->factoryModule('epay', 'epay', 'common');
	
	$ENCODED = $_POST['encoded'];
	$CHECKSUM = $_POST['checksum'];
	
	$secret = $iaCore->get('epay_api_secret');
	$hmac = iaEpay::hmac('sha1', $ENCODED, $secret);
	
	if ($hmac == $CHECKSUM) {
		
		$data = base64_decode($ENCODED);
		$lines_arr = split("\n", $data);
		$info_data = '';
		
		foreach ($lines_arr as $line) {
			if (preg_match("/^INVOICE=(\d+):STATUS=(PAID|DENIED|EXPIRED)(:PAY_TIME=(\d+):STAN=(\d+):BCODE=([0-9a-zA-Z]+))?$/", $line, $regs)) {
				$invoice  = $regs[1];
				$status   = $regs[2];
				$pay_date = $regs[4]; # XXX if PAID
				$stan     = $regs[5]; # XXX if PAID
				$bcode    = $regs[6]; # XXX if PAID
				
				$data = array(
					'item_id' => $invoice,
					'status' => $status,
					'pay_date' => $pay_date,
					'stan' => $stan,
					'bcode' => $bcode
				);

				$iaEpay->handleIpn($data);
			}
		}
		
	} else {
		echo "ERR=Not valid CHECKSUM\n";
	}

    $iaView->disableLayout();
    $iaView->display(iaView::NONE);
}