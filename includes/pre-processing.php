<?php

$iaEpay = $iaCore->factoryModule('epay', 'epay', 'common');

$returnURL = IA_RETURN_URL . 'completed/';
$cancelURL = IA_RETURN_URL . 'canceled/';

	$iaView->assign('paymentURL', $iaEpay->setPaymentURL());
	
	$paymentData = $iaEpay->getPaymentData($planInfo, $description, $returnURL, $cancelURL, $transaction);
	
	$iaView->assign('ENCODED', $paymentData['ENCODED']);
	$iaView->assign('CHECKSUM', $paymentData['CHECKSUM']);
	$iaView->assign('URL_OK', $paymentData['URL_OK']);
	$iaView->assign('URL_CANCEL', $paymentData['URL_CANCEL']);