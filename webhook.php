<?php
if($_SERVER["REQUEST_METHOD"] === "GET") {
	http_response_code(200);
	echo "Webhook script installed successfully";
	exit;
}

$config = [];

foreach(parse_ini_file(__DIR__ . "/config.ini") as $key => $value) {
	$config[$key] = $value;
}

$headers = [];

foreach($_SERVER as $key => $value) {
	if(substr($key, 0, 5) === "HTTP_") {
		$fullKey = str_replace("_", " ", substr($key, 5));
		$fullKey = ucwords(strtolower($fullKey));
		$fullKey = str_replace(" ", "-", $fullKey);

		$headers[$fullKey] = $value;
	}
}

$event = $headers["X-Github-Event"];
echo "Github event received: $event" . PHP_EOL;

$payload_raw = file_get_contents("php://input");
if(!$payload_raw) {
	http_response_code(400);
	echo "ERROR: No payload attached to request." . PHP_EOL;
	throw new Exception("Reading payload failed");
}

$payload = json_decode($payload_raw);
if(empty($payload)) {
	http_response_code(500);
	echo "ERROR: Payload not valid JSON" . PHP_EOL;
	throw new Exception("Failure parsing payload");
}

$repoName = $payload->repository->full_name;
$repoNameNoSlashes = str_replace("/", "_", $repoName);

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

if(is_dir(__DIR__ . "/config.d")) {
	$iniFile = __DIR__ . "/config.d/$repoNameNoSlashes.ini";

	if(file_exists($iniFile)) {
		foreach(parse_ini_file($iniFile) as $key => $value) {
			$value = str_replace("{repo}", $repoNameNoSlashes, $value);
			if($config["webhook_branch"] === "*") {
				$value = str_replace("{branch}", $receivedBranch, $value);
			}

			$config[$key] = $value;
		}
	}
}

$branchToAction = $config["webhook_branch"];

echo "Repo name: $repoNameNoSlashes" . PHP_EOL;
echo "Received branch: $receivedBranch" . PHP_EOL;
echo "Branch to action: $branchToAction" . PHP_EOL;

list($algo, $hash) = explode("=", $headers["X-Hub-Signature"], 2);
$payload_hash = hash_hmac($algo, $payload_raw, $config["webhook_secret"]);

if($hash !== $payload_hash) {
	http_response_code(401);
	if(empty($config["webhook_secret"])) {
		echo "ERROR: webhook_secret environment variable is not set."
			. PHP_EOL;
	}
	else {
		echo "ERROR: webhook_secret does not match request signature."
			. PHP_EOL;
	}
	throw new Exception("Authentication failure");
}

if($event === "ping") {
	http_response_code(200);
	echo "pong";
	exit;
}

$eventToContinue = $config["webhook_event"];
if(!$eventToContinue) {
	$eventToContinue = "push";
}

if($branchToAction !== "*" && $receivedBranch !== $branchToAction) {
	http_response_code(200);
	echo "Waiting for branch $branchToAction - $receivedBranch received.";
	exit;
}

if($event !== $eventToContinue) {
	http_response_code(200);
	echo "Waiting for $eventToContinue - $event received.";
	exit;
}

if($eventToContinue === "status"
&& $payload->state !== "success") {
	echo "Waiting for event $eventToContinue - {$payload->state} received.";
	http_response_code(204);
	exit;
}

$pullCheckoutScriptPath = "";

unset($config["webhook_secret"]);
foreach ($config as $key => $value) {
	$value = escapeshellarg($value);
	$value = "\"$value\"";
	$pullCheckoutScriptPath .= "$key=$value ";
}

echo "OK: Executing pull-checkout.bash" . PHP_EOL;
echo $pullCheckoutScriptPath . PHP_EOL;
echo str_repeat("-", 80) . PHP_EOL;

$pullCheckoutScriptPath .= __DIR__ . "/pull-checkout.bash $repoNameNoSlashes";
exec($pullCheckoutScriptPath . " 2>&1", $responseArray);

$response = implode("\n", $responseArray);

$logPath = $config["webhook_log_path"];
if($logPath !== false) {
	file_put_contents(
		$logPath,
		date("Y-m-d H:i:s") . PHP_EOL . $response . PHP_EOL,
		FILE_APPEND
	);
}

echo $response;
