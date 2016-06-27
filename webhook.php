<?php
/**
 * Serve this script as the webhook's Payload URL.
 *
 * Configure the webhook with config.ini for generic settings, or individually
 * per repository using .ini files within the config.d directory.
 */
if($_SERVER["REQUEST_METHOD"] === "GET") {
	http_response_code(200);
	echo "Webhook script installed successfully";
	exit;
}

$config = [];

foreach(parse_ini_file(__DIR__ . "/config.ini") as $key => $value) {
	if(trim($key)[0] === "#") {
		continue;
	}

	$config[$key] = $value;
}
if(isset($config["db_name"])) {
	$config["db_name"] = strtolower($config["db_name"]);
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
if($event === "ping") {
	http_response_code(200);
	echo "pong";
	exit;
}

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
	if(strstr($receivedBranch, "/")) {
		$receivedBranch = substr(
			$receivedBranch,
			strrpos($receivedBranch, "/") + 1
		);
	}
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
			$config[$key] = $value;
		}
	}
}

foreach ($config as $key => $value) {
	$value = str_replace("{repo}", $repoNameNoSlashes,
		preg_replace("/[\/\\ ]/", "_", $value));
	$value = str_replace("{branch}", $receivedBranch, 
		preg_replace("/[\/\\ ]/", "_", $value));

	$config[$key] = $value;
}

$activeBranch = $config["webhook_branch"];

echo "Repo name: $repoNameNoSlashes" . PHP_EOL;
echo "Received branch: $receivedBranch" . PHP_EOL;
echo "Branch to action: $activeBranch" . PHP_EOL;

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

unset($config["webhook_secret"]);
$config["received_branch"] = $receivedBranch;
$configString = "";
foreach ($config as $key => $value) {
	$value = escapeshellarg($value);
	$configString .= "$key=$value ";
}

$config["destination_path"] = strtolower($config["destination_path"]);

if($event === "delete") {
	$deleteBranchScriptPath = $configString
		. __DIR__ . "/delete-branch.bash $repoNameNoSlashes "
		. "$receivedBranch $config[repo_dir] $config[destination_path]";

	echo "OK: Executing delete-branch.bash" . PHP_EOL;
	echo $deleteBranchScriptPath . PHP_EOL;
	echo str_repeat("-", 80) . PHP_EOL;

	exec($deleteBranchScriptPath . " 2>&1", $responseArray);
	$response = implode("\n", $responseArray);
	echo $response . PHP_EOL;
	exit;
}

$eventToContinue = $config["webhook_event"];
if(!$eventToContinue) {
	$eventToContinue = "push";
}

if($activeBranch !== "*" && $receivedBranch !== $activeBranch) {
	http_response_code(200);
	echo "Waiting for branch $activeBranch - $receivedBranch received.";
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

$pullCheckoutScriptPath = $configString
	. __DIR__ . "/before-checkout.bash $repoNameNoSlashes";

echo "OK: Executing before-checkout.bash" . PHP_EOL;
echo $pullCheckoutScriptPath . PHP_EOL;
echo str_repeat("-", 80) . PHP_EOL;

exec($pullCheckoutScriptPath . " 2>&1", $responseArray);

$response = implode("\n", $responseArray);

$logPath = $config["webhook_log_path"];
if(!empty($logPath)) {
	file_put_contents(
		$logPath,
		date("Y-m-d H:i:s") . PHP_EOL . $response . PHP_EOL,
		FILE_APPEND
	);
}

echo $response . PHP_EOL;

echo PHP_EOL . "All scripts completed successfully." . PHP_EOL;