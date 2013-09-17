<?php
require_once(dirname(dirname(__file__))."/new_v4_guid.php");

class IpcCB_endpoint_cb
{
	// Changes key value pairs into a list of "Field"


    function create_application($ipcCBClient, $request= array()) {
      	$defaults = array(
			"ApplicationGuid" => "00000000-0000-0000-0000-000000000000",
			"ApplicationProfileId" => "Base Bundle Plus Business Risk",
			"Created" => null,
			"ExternalApplicationId" => new_v4_guid(),
			"Fields" => IpcCB_endpogenerate_fields($ipcCBClient, $request),
			"Validation" => null
      	);

        $request = array_merge_overwrite($defaults, $request);
        $ipcCBClient -> last_call = __CLASS__."->".__FUNCTION__;

        return $ipcCBClient -> send('/commerceboarding/applications', $request, HttpMethod::Post);
		// We expect a 201-created with [fields, validation] now defined.
    }

    function get_validation_questions($ipcCBClient, $app_id= null) {
        $ipcCBClient -> last_call = __CLASS__."->".__FUNCTION__;
	
        return $ipcCBClient -> send('/commerceboarding/applications/'.$app_id.'/idquestions', null, HttpMethod::Post);
    }

	// Sends the questions and answers back.
    function update_validation_questions($ipcCBClient, $questions_id, $questions, $app_id= null) {
        $ipcCBClient -> last_call = __CLASS__."->".__FUNCTION__;
 	   
        return $ipcCBClient -> send('/commerceboarding/applications/'.$app_id.'/idquestions/'.$questions_id, $questions, HttpMethod::Put);
    }

    function start_application($ipcCBClient, $app_id= null) {
        $ipcCBClient -> last_call = __CLASS__."->".__FUNCTION__;
	
        return $ipcCBClient -> send('/commerceboarding/applications/'.$app_id.'/result', null, HttpMethod::Post);	
    }
    
    function get_application_result($ipcCBClient, $app_id = null) {
        $ipcCBClient -> last_call = __CLASS__."->".__FUNCTION__;
		
        return $ipcCBClient -> send('/commerceboarding/applications/'.$app_id.'/result', "", HttpMethod::Get);
    }
    
}
?>
