<?php 

// Unirest PHP HTTP library - http://unirest.io/#php
require 'vendor/autoload.php';

// API configuration
require 'config.php';

// ghetto routing
$request = $_GET['request'];
if ($request == 'new_user') {
	$username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
	$language = filter_var($_POST['language'], FILTER_SANITIZE_STRING);
	new_user($username, $language);
}
elseif ($request == 'new_message') {
	$username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
	$language = filter_var($_POST['language'], FILTER_SANITIZE_STRING);
	$message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
	if ($username && $language && strlen($message) >= 1) {
		new_message($username, $language, $message);
	}
}
elseif ($request == 'translate') {
	$id = filter_var($_POST['id'], FILTER_SANITIZE_STRING);
	$message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
	$from = filter_var($_POST['from'], FILTER_SANITIZE_STRING);
	$to = filter_var($_POST['to'], FILTER_SANITIZE_STRING);
	translate($id, $message, $from, $to);
}

// add new user to firebase
function new_user($name, $language) {
    $response = Unirest::delete(firebase_db."users/".$name.".json");
    $response = Unirest::post(firebase_db."users/".$name.".json", array( "Accept" => "application/json" ),
	  json_encode(array(
	    "username" => $name,
	    "language" => $language
	  ))
	);
}

// add new message to firebase
function new_message($user, $language, $message) {
	$translations = array( $language => $message );
	$response = Unirest::post(firebase_db."messages.json", array( "Accept" => "application/json" ),
	  	json_encode(array(
		    "username" => $user,
		    "language" => $language,
			"message" => $translations
	  	))
	);
	echo json_encode($response->body->name);
}

// translate message and patch firebase
function translate($id, $message, $from, $to){

	// translation powered by MyMemory translation API
	// https://www.mashape.com/translated/mymemory-translation-memory
	$message = rawurlencode($message);
	$message_request  = 'https://translated-mymemory---translation-memory.p.mashape.com/api/get?langpair='; 
	$message_request .= $from . '|' . $to . '&q=' . $message . '&mt=1&of=json&v=1';
	$response = Unirest::get($message_request, array("X-Mashape-Authorization" => mashape_key))->body;
	$response = $response;
	$translated_message = $response->responseData->translatedText;

	if ($translated_message != "NO QUERY SPECIFIED. EXAMPLE REQUEST: GET?Q=HELLO&LANGPAIR=EN|IT") {
		// Update firebase
		Unirest::patch(firebase_db."messages/".$id."/message.json", array( "Accept" => "application/json" ),
		  	json_encode(array(
				$to => $translated_message
		  	))
		);

		// send translated message back
		echo $translated_message;
	}
}

?>