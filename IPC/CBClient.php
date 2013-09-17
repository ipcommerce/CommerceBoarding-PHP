<?php
require_once(PUBLIC_ROOT."IPC/CB_REST/main.php");

class CBClient {

	var $client;

	var $applicationGuid = "";
	var $questionsGuid = "";
	
	var $fieldsDefault = array(
		"ApplicantEmailAddress" => "test@test.com",
		"IpAddress" => "1.0.0.0",
		"ApplicationType" => "Loan",
		"BusinessName" => "Test Business Name",
		"BusinessLegalName" => "Test Legal Business Name",
		"BusinessDbaName" => "Test Legal DBA Business Name",
		"TypeOfEntity" => "LLC",
		"BusinessUrl" => "mybiz@biz.com",
		"BusinessAddress1" => "111 Test Street",
		"BusinessAddress2" => "Suite 100",
		"BusinessCity" => "Denver",
		"BusinessState" => "CO",
		"BusinessZip" => "80202",
		"BusinessPhone" => "3033334444",
		"HomeAddress1" => "111 Home Street",
		"HomeAddress2" => "Apt. 100",
		"HomeCity" => "Denver",
		"HomeState" => "CO",
		"HomeZip" => "80202",
		"HomePhone" => "3334445555",
		"ApplicantFirstName" => "John",
		"ApplicantLastName" => "Doe",
		"DateOfBirth" => "10/31/1971",
		"SocialSecurityNumber" => "111223333"
	);
	
	var $fieldsRequired = array(
		"ApplicantEmailAddress",
		"IpAddress",
		"ApplicationType",
		"BusinessName",
		"BusinessLegalName",
		"BusinessDbaName",
		"TypeOfEntity",
		"BusinessUrl",
		"BusinessAddress1",
		"BusinessAddress2",
		"BusinessCity",
		"BusinessState",
		"BusinessZip",
		"BusinessPhone",
		"HomeAddress1",
		"HomeAddress2",
		"HomeCity",
		"HomeState",
		"HomeZip",
		"HomePhone",
		"ApplicantFirstName",
		"ApplicantLastName",
		"DateOfBirth",
		"SocialSecurityNumber"
	);
	
	
	function __construct ($identity_token, $fieldsRequired= array()) {
		$this->client=new IpcCBClient();

		
		echo ("Using Identity: ".substr($identity_token, 0, 30)."...");

		$this->client->sign_on($identity_token);
		
		if (is_string($fieldsRequired))
		{
			$fieldsRequired = explode("|", $fieldsRequired);
		}
		
		if (count($fieldsRequired) > 0)
			$this->fieldsRequired = $fieldsRequired;
		$this->client->do_proxy=false;
		$this->client->do_log=true;
	}
	
	function create_application ($request=array()) {
	    $defaults = array(
			"ApplicationGuid" => "00000000-0000-0000-0000-000000000000",
			"ApplicationProfileId" => "Base Bundle Plus Business Risk",
			"Created" => null,
			"ExternalApplicationId" => new_v4_guid(),
			"Fields" => $this->generate_fields($request),
			"Validation" => null
      	);

        $request = array_merge_overwrite($defaults, $request);
        $this->client -> last_call = __CLASS__."->".__FUNCTION__;

        $result =  $this->client -> send('/commerceboarding/applications', $request, HttpMethod::Post);
		// We expect a 201-created with [fields, validation] now defined.
    	if ($result->data->Success == true)
    	{
    		$this->applicationGuid = $result->data->ApplicationGuid;
    	}
    	return $result;
	}

	function generate_fields($keyVals) {
		$fields = array();
		
		foreach ($this->fieldsRequired as $field)
		{
			if (!isset($keyVals[$field]))
				$keyVals[$field] = $this->fieldsDefault[$field];
			$fields[] = array(
				"Key" => $field,
				"Value" => $keyVals[$field]
			);
		}
		return $fields;
	}
	
    function get_validation_questions() {
        $this->client -> last_call = __CLASS__."->".__FUNCTION__;
		$app_id = $this->applicationGuid;
        $result = $this->client -> send('/commerceboarding/applications/'.$app_id.'/idquestions', null, HttpMethod::Post);
    	if ($result->data->Success == true)
    	{
    		$this->questionsGuid = $result->data->QuestionsId;
    	}
    	return $result;
    }

	// Sends the questions and answers back.
    function update_validation_questions($request) {
        $this->client -> last_call = __CLASS__."->".__FUNCTION__;
 	   
		$app_id = $this->applicationGuid;
		
		$questions_id = $this->questionsGuid;
		$request= array("Questions"=> $request, "QuestionsId"=>$questions_id);
        return $this->client -> send('/commerceboarding/applications/'.$app_id.'/idquestions/'.$questions_id, $request, HttpMethod::Put);
    }

    function start_application() {
        $this->client -> last_call = __CLASS__."->".__FUNCTION__;
	
		$app_id = $this->applicationGuid;
        return $this->client -> send('/commerceboarding/applications/'.$app_id.'/result', null, HttpMethod::Post, "200");	
    }
    
    function get_application_result() {
        $this->client -> last_call = __CLASS__."->".__FUNCTION__;
		
		$app_id = $this->applicationGuid;
        return $this->client -> send('/commerceboarding/applications/'.$app_id.'/result', "", HttpMethod::Get);
    }
    
	function select_application($app_id) {
		$this->applicationGuid = $app_id;
	}
	
	function select_questions($questions_id) {
		$this->questionsGuid = $questions_id;
	}
	
	function list_applications ($search_parameters= array()) {
		$params= array();
		if ($search_parameters["ApplicationProfileId"])
			$params[] = "appProfileId=".$search_parameters["ApplicationProfileId"];
		if ($search_parameters["ApplicationStatus"])
			$params[] = "appStatus=".$search_parameters["ApplicationStatus"];
		if ($search_parameters["FirstName"])
			$params[] = "fName=".$search_parameters["FirstName"];
		if ($search_parameters["LastName"])
			$params[] = "lName=".$search_parameters["LastName"];
		if ($search_parameters["EmailAddress"])
			$params[] = "email=".$search_parameters["EmailAddress"];
		if ($search_parameters["QualificationStatus"])
			$params[] = "qualStatus=".$search_parameters["QualificationStatus"];
		if ($search_parameters["IdentityStatus"])
			$params[] = "identStatus=".$search_parameters["IdentityStatus"];
		if ($search_parameters["PageNumber"])
			$params[] = "pageNum=".$search_parameters["PageNumber"];
		if ($search_parameters["PageSize"])
			$params[] = "pageSize=".$search_parameters["PageSize"];
	
		$qs = join("&", $params);
		
		return $this->client -> send('/commerceboarding/applications?'.$qs, "", HttpMethod::Get);
	}
	
	function list_applications_date ($from, $to=null) {
		$params= array();
		
		if ($to == null) 
			$to = time();
		
		$params[] = "fromDate=".$from;
		$params[] = "toDate=".$to;
		
		$qs = join("&", $params);
		
		return $this->client -> send('/commerceboarding/applications?'.$qs, "", HttpMethod::Get);
	}
}

?>
