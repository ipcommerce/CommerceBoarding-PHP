<?php

class SimpleCurlResponse
{
 	const SOAP = 1;
 	const REST = 0;
	var $header_text = "";
	var $body= "";
	var $code= 0;
	var $data= "";
	var $error = "";
	var $rule_message = "";
	var $curl_info= array();
	
	function first($object, $times=1)
	{
			
		foreach($object as $result)
		{
			if ($times > 1)
				return $this->first($result, --$times);
			else
				return $result;
		}
	}
	
	//
	function rule_message_in($error) {
		if ($error == false) {
		    return false;
		}
		$element_name = "RuleMessage";
		$found = preg_match('#<'.$element_name.'(?:\s+[^>]+)?>(.*?)'.
		        '</'.$element_name.'>#s', $error, $matches);
		if ($found != false) {
			return $matches[1];  //ignore the enclosing tags
		}
		
		// Next best thing, seen in the txn endpoints
		$element_name = "a:string";
		$found = preg_match('#<'.$element_name.'(?:\s+[^>]+)?>(.*?)'.
		        '</'.$element_name.'>#s', $error, $matches);
		if ($found != false) {
			return $matches[1];  //ignore the enclosing tags
		}
		
		// No match found: return the original response body.
		return htmlspecialchars($error);
	}

	function __construct($curl_handle, $expects="200", $received=  SimpleCurlResponse::REST)
	{
		$this->body= curl_exec($curl_handle);
		$this->curl_info = curl_getinfo($curl_handle);
		$this->code = $this->curl_info['http_code'];
		
		if ($this->code == $expects)
		{
			if ($this->code != "204")
			{
				if ($this->body[0] == "{" || $this->body[0] == "\"" || $this->body[0] == "[")
					$this->data = json_decode($this->body);
					
				if (is_object($this->data))
					$this->data->Success= true;
				else
					$this->data = (object) array("Results"=> $this->data, "Success" => true);
			}
			else
			{
				$this->data = (object) array("Success"=>true);
			}
		}
		else
		{
			$this->error="<h1>UNHANDLED REST FAULT.</h1>";
			$this->error.="<p>Expected {$expects} response, but received {$this->code}.</p>";
			$this->error.="<p>".htmlspecialchars($this->body)."</p>";
			$this->rule_message = $this->rule_message_in ($this->body);
			$this->data = (object) array ("Success" => false, "ErrorBody"=>$this->rule_message);
		
			var_dump($this->curl_info);
		}
	}
	
}
?>
