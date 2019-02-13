<form name="epay_form" id="epay_form" action="{$paymentURL}" method="POST">
	<input type="hidden" name="PAGE" value="paylogin">
	<input type="hidden" name="ENCODED" value="{$ENCODED}">
	<input type="hidden" name="CHECKSUM" value="{$CHECKSUM}">
	<input type="hidden" name="URL_OK" value="{$URL_OK}">
	<input type="hidden" name="URL_CANCEL" value="{$URL_CANCEL}">
</form>