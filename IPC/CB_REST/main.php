<?php
# It is not safe to rely on the system's timezone settings according to PHP.
# This will tame the beast.
date_default_timezone_set('America/Denver');



#Your Solutions Consultant may have commented out some of the lines below to match your implementation.
	
require('endpointCB.php');

require_once(dirname(dirname(__file__)).'/SimpleCurlResponse.php');

require_once(dirname(dirname(__file__)).'/array_merge_overwrite.php');
require_once(dirname(dirname(__file__))."/array_merge_if_defined.php");


class HttpMethod
{
	const Get = "GET"; 
	const Post = "POST";
	const Put = "PUT";
	const Delete = "DELETE";
}


class IpcCBClient
{
	var $do_log= true;
	var $do_proxy = false;

	var $session_token;
	// Required for almost all requests
	
	function send($path, $body, $httpMethod, $expected_response=null)
	{
		var_dump($this->session_token);
		if ($httpMethod != HttpMethod::Get)
		{
			//if ($this->session_token == "") return false;
	        $this->convert_dates($body);
		}
		$url='cws-01.cert.ipcommerce.com';
		$path = '/rest/2.0.17'.$path;
				
		$curl = curl_init();
		if ($this->do_proxy == true)
		{		
			$protocol= "https://";
			$url = "localhost";
		}
		else
		{
			$protocol= "https://";
		}
		curl_setopt($curl, CURLOPT_URL, $protocol.$url.$path);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			#Please verify the connection when you go to production!
			#cURL will naiively accept any certificate in this sample code.
		if ($httpMethod == HttpMethod::Post)
		{	
			if ($body == null)
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
			else
				curl_setopt($curl, CURLOPT_POST, true);
		}
		else if ($httpMethod == HttpMethod::Put)
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		else if ($httpMethod == HttpMethod::Delete)
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		
		/*
		A developer once used this code to see the http request headers.
		*/
		/*
		$error_log= fopen('request.txt', 'w');
		curl_setopt($curl, CURLOPT_STDERR, $error_log);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		fclose($error_log);		
		/**/
		$session_token = $this->session_token;
		
		$headers=array(
			'Authorization: Basic '. base64_encode($session_token.":"), 
			'Content-Type: application/json', 
	 		'Accept: ', // Known issue: defining this causes server to reply with no content.
	 		'Host: '.$url
	 	);
		
		if ($body != null)
			curl_setopt($curl, CURLOPT_POSTFIELDS, (string)json_encode($body));
		else
		{
			$headers[] = 'Content-Length: 0';
		}
			
	 	var_dump($headers);	
	 	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	 	
		if ($expected_response == null)
			if ($httpMethod == HttpMethod::Delete)
				$expected_response = "204";
			elseif ($httpMethod == HttpMethod::Post)
				$expected_response = "201";
			else
				$expected_response = "200";
			
		$simpleCurlResponse = new SimpleCurlResponse($curl, $expected_response);

		curl_close($curl);
		if ($this->do_log)
		{
			echo ("\n\n\n".$httpMethod." ".$protocol.$url.$path."\n");
			//$headers[0]= substr($headers[0], 0, 32)."...";
			//echo (join("\n", $headers));
			echo ("\n\n".(string)json_encode($body)); // Use htmlspecialchars if html is necessary
			echo ("\n\n".$simpleCurlResponse->error); // Use htmlspecialchars if html is necessary
			echo ("\n\n".$simpleCurlResponse->header_text);
			echo ("\n\n><".$simpleCurlResponse->body."><\n\n"); // Use htmlspecialchars if html is necessary
			
		}
		return $simpleCurlResponse;
	}
	
	# Careful, super coder!
	# The identity_token is a base64'd saml assertion that lasts 3 years.
	# The session_token is a base64'd saml assertion that lasts 30 minutes.
	# The SvcInfo/token endpoint /SvcInfo/token only accepts the 3 year saml assertion, 
	#  and replies with a session token in quotes.
	# All of the endpoints outside of SvcInfo expect a valid session_token.
	
	function sign_on($identity_token)
	{
		//We do this temporarily to send this token to SvcInfo->signOnWithToken
	 	$this->session_token= $identity_token;
	 	
	 	$protocol = 'https://';
	 	$url = 'stsp-02.cert.ipcommerce.com';
	 	$path = '/ssoapis/rest/signonservice/tokencred/token';
	 	
		var_dump($this->session_token);
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $protocol.$url.$path);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		#Please verify the connection when you go to production!
		#cURL will naiively accept any certificate in this sample code.
			
		$session_token = $this->session_token;
		
		$headers=array(
			'Authorization: '.$identity_token, 
			'Content-Type: application/json', 
	 		'Accept: ', // Known issue: defining this causes server to reply with no content.
	 		'Expect: 100-continue',
	 		'Host: '.$url
	 	);
	 	var_dump($headers);	
	 	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
			
		$simpleCurlResponse = new SimpleCurlResponse($curl, "200");

		curl_close($curl);
	 	
	 	if ($simpleCurlResponse->code != "200")
		{
			# This error is unconditionally displayed because its an indicator of a bigger underlying problem that must be fixed.
			echo "\n\nIt seems we didn't get a session token. Expected a 200 response, and a base64'd saml assertion. Response code=". $simpleCurlResponse->code. " Check your config file has been updated to contain your identity token(s).";
			$this->session_token = "";
			return false;
		}
		// Currently the session token does not have any slashes that would be escaped to \/.
		// This is safe as the generated saml is functionally confined to the ASCII character set.
		$this->session_token= trim($simpleCurlResponse->body, "\"");
		# Protip: The saml assertion can be read and verified of its duration.
		# It would be prudent to make a type of check_session() function to be called before any calls to check the $this->session_expires is not < time.now()   -- and if so, call sign on.
		# Or, for a constant connection do a 25 minute cron job that gets the latest session token to *securely* share among all your servers.

		#Pseudocode:
		#match_expires="/(?<=NotOnOrAfter=\")[\s\S]*?(?=\")/"
		#match= preg_match(match_expires,Base64::decode64($this->session_token), matches)
		#if (match) {
		#	$this->session_expires=matches[0];
		#	echo "Security Token Expires on: ".matches[0];
		#}
		
		return $this->session_token;
	}
	
	function convert_dates(&$body)
	{
		if (!is_array($body)) return;
		foreach ($body as &$the_element)
        {
            if (is_array($the_element))
                $this->convert_dates($the_element);
            else if ($the_element instanceof DateTime)
                $the_element = $the_element->format('c');
        }
        unset($the_element); # A good habit to do this.
	}

}

?>
