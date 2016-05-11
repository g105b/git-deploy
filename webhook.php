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
$event = $headers["X-GitHub-Event"];

$payload_raw = file_get_contents("php://input");
if(!$payload_raw) {
	http_response_code(400);
	throw new Exception("Reading payload failed");
}

$payload = json_decode($payload_raw);
if(empty($payload)) {
	http_response_code(500);
	throw new Exception("Failure parsing payload");
}

$repoName = $payload->repository->full_name;
$repoNameNoSlashes = str_replace("/", "_", $repoName);

if(is_dir(__DIR__ . "/config.d")) {
	$iniFile = __DIR__ . "/config.d/$repoNameNoSlashes.ini";

	if(file_exists($iniFile)) {
		foreach(parse_ini_file($iniFile) as $key => $value) {
			putenv("$key=$value");
		}
	}
}

list($algo, $hash) = explode("=", $headers["X-Hub-Signature"], 2);
$payload_hash = hash_hmac($algo, $payload_raw, getenv("webhook_secret"));

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

$branch = getenv("webhook_branch");
$receivedBranch = isset($payload->ref) ? $payload->ref : null;
if($receivedBranch) {
	$receivedBranch = substr(
		$receivedBranch,
		strrpos($receivedBranch, "/") + 1
	);
}
else {
	foreach ($payload->branches as $b) {
		if($payload->sha === $b->commit->sha) {
			$receivedBranch = $b->name;
			break;
		}
	}
}

if($receivedBranch !== $branch) {
	http_response_code(200);
	echo "Waiting for $branch - $receivedBranch received.";
	exit;
}

if($event !== $eventToContinue) {
	http_response_code(200);
	echo "Waiting for $eventToContinue - $event received.";
	exit;
}

if($eventToContinue === "status"
&& $payload->state !== "success") {
	http_response_code(204);
	exit;
}

$pullCheckoutScriptPath = __DIR__ . "/pull-checkout.bash $repoNameNoSlashes";
exec($pullCheckoutScriptPath . " 2>&1", $responseArray);

$response = implode("\n", $responseArray);

$logPath = getenv("webhook_log_path");
if($logPath !== false) {
	file_put_contents(
		$logPath,
		date("Y-m-d H:i:s") . PHP_EOL . $response . PHP_EOL,
		FILE_APPEND
	);
}

echo $response;
