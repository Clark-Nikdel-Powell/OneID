<?php


class OneIDAPI {

	/**
	 * Generate OneID Login Button.
	 *
	 * @param  string $attr Requested user info returned from OneID
	 * @param  string $callback Url for OneID callback
	 * @return string
	 */
	function OneID_Button($attr, $callback) {
		return '<img 
			class="oneidlogin" 
			id="oneidlogin" 
			src="https://my.oneid.com/api/images/oneid_signin.png" 
			style="cursor:pointer;"
			onclick="OneId.login()" 
			CHALJ=\'{"NONCE":"'. self::OneID_MakeNonce() .'","ATTR":"'. $attr .'","CALLBACK":"'. $callback . '"}\' />';
	}
	
	/**
	 * Generates nonce.
	 *
	 * @return string
	 */
	function OneID_MakeNonce() {
		$arr =  self::_call_OneID("make_nonce");
		return $arr['NONCE'];
	}
	
	/**
	 * Validate the user against OneID
	 *
	 * @return array
	 */
	function OneID_Response() {
		$resp = file_get_contents('php://input');
		$validate = self::_call_OneID("validate", $resp);
		if ($validate["errorcode"] != 0) { return FALSE; } 
		$arr = json_decode($resp, true);
		return $arr;
	}
	
	/**
	 * Tell OneID service where to send the logged in user.
	 *
	 * @param string $page Redirect user to url
	 */
	function OneID_Redirect($page) {
		return ('{"error":"success","url":"'. $page .'"}');
	}

	/**
	 * Make call to OneID service.
	 *
	 * @param string $method OneID service method
	 * @param array $post (defaul: null)
	 * @return array
	 */
	function _call_OneID($method, $post = null) {
		$oneid_server = 'https://keychain.oneid.com';
		if ( $options = get_option( 'oneid_options' ) ) {
			extract($options);
		}
		$scope = "";
		$ch = curl_init($oneid_server . $scope. "/" . $method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, $oneid_api_id . ":" . $oneid_api_key);
		if ($post !== null) {
			curl_setopt($ch, CURLOPT_POST, 1);                                                                             
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);  
		}
		$json = curl_exec($ch);
		curl_close($ch);
		return json_decode($json, true);
	}

}

?>