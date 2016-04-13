<?php
if($_SERVER["REQUEST_METHOD"] === "GET") {
	http_response_code(200);
	echo "Webhook script installed successfully";
	exit;
}

foreach(parse_ini_file(__DIR__ . "/config.ini") as $key => $value) {
	putenv("$key=$value");
}

$headers = getallheaders();
$event = $headers["X-Github-Event"];

$payload_raw = file_get_contents("php://input");
if(!$payload_raw) {
	http_response_code(400);
	throw new Exception("Reading payload failed");
}

list($algo, $hash) = explode("=", $headers["X-Hub-Signature"], 2);
$payload_hash = hash_hmac($algo, $payload_raw, getenv("webhook_secret"));

$payload = json_decode($payload_raw);
if(empty($payload)) {
	http_response_code(500);
	throw new Exception("Failure parsing payload");
}

if($hash !== $payload_hash) {
	http_response_code(401);
	throw new Exception("Authentication failure");
}

if($event === "ping") {
	http_response_code(200);
	echo "pong";
	exit;
}

$eventToContinue = getenv("webhook_event");
if(!$eventToContinue) {
	$eventToContinue = "push";
}

if($payload->ref !== "refs/heads/master"
|| $event !== $eventToContinue) {
	http_response_code(204);
	exit;
}

if($eventToContinue === "status"
&& $payload->state !== "success") {
	http_response_code(204);
	exit;
}

$pullCheckoutScriptPath = __DIR__ . "/pull-checkout.bash "
	. $payload->repository->full_name;
$response = shell_exec($pullCheckoutScriptPath . " 2>&1");

$logPath = getenv("webhook_log_path");
if($logPath !== false) {
	file_put_contents(
		$logPath,
		date("Y-m-d H:i:s") . PHP_EOL . $response . PHP_EOL,
		FILE_APPEND
	);
}

echo $response;