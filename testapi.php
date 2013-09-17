<?php

define ('PUBLIC_ROOT', dirname(__file__)."/");
ini_set("log_errors", 1);

require_once(PUBLIC_ROOT."config.php");
require_once(PUBLIC_ROOT."IPC/CBClient.php");


function isCli()
{
    // If STDIN was not defined and PHP is running as CGI module
    // we can test for the environment variable TERM. This
    // should be a right way how to test the circumstance under 
    // what mode PHP is running.
    if(!defined('STDIN') && self::isCgi()) {
        // STDIN was not defined, but if the environment variable TERM 
        // is set, it is save to say that PHP is running from CLI.
        if(getenv('TERM')) {
            return true;
        }
        // Now return false, because TERM was not set.
        return false;
    }
    return defined('STDIN');
}


function test_assert ($test, $client, $rule_text="")
{
	if ($rule_text != "")
		$rule_text = ": ".$rule_text;
	if (isset($client->client) && isset($client->client->last_call))
		$rule_text= " IN ".$client->client->last_call. $rule_text. ".";
	if (!$test)
	{
		echo ("\n\nFAILED".$rule_text);
		exit();
	}else
	{
		echo ("\n\nPASSED".$rule_text);
	}
}
function hr()
{
	echo ("\n\n-------------------------------------------------------------\n\n");
}


print "PHP is running ". ((isCli()) ? "from the CLI" : "")." with".((defined('STDIN')?"" :"out")." stdin.");
print "\n";

$client = new CBClient($identity_token, $fieldsRequired);
/* */
$result =$client->create_application();

var_dump($result);

$result =$client->get_validation_questions();

var_dump($result);

$questions = $result->data->Questions;

foreach($questions as $i => $question)
{
	foreach($question->Answers as $a => $answer)
	{
		if (substr($answer, 0, 2) == "99")
			continue;
		$questions[$i]->SelectedAnswer = $a;
		break;
	}
}

$result =$client->update_validation_questions($questions);

var_dump($result);

$result =$client->start_application();

var_dump($result);

sleep(60);

$result =$client->get_application_result();

var_dump($result);

echo ("\n\nTESTS COMPLETE.\n");
/* */

$result = $client->list_applications(array(
	"ApplicationStatus" => 1
));

var_dump($result);
?>
