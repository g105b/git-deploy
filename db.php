#!/usr/bin/php
<?php
foreach(parse_ini_file(__DIR__ . "/config.ini") as $key => $value) {
	putenv("$key=$value");
}

$repoNameNoSlashes = $argv[1];

if(is_dir(__DIR__ . "/config.d")) {
	$iniFile = __DIR__ . "/config.d/$repoNameNoSlashes.ini";

	if(file_exists($iniFile)) {
		foreach(parse_ini_file($iniFile) as $key => $value) {
			putenv("$key=$value");
		}
	}
}

$dbMigrationPath = getenv("db_migration_path");
if(!empty($dbMigrationPath)) {
	try {
		$dbh = new PDO(
			getenv("db_dsn") . ":dbname=" . getenv("db_name"),
			getenv("db_user"),
			getenv("db_pass")
		);
	}
	catch(PDOException $e) {
		die("Error connecting to database to perform migrations. "
			. $e->getMessage()
		);
	}

	$stmt = $dbh->query(implode("\n", [
		"select `version`",
		"from `db_migration`",
		"where `project` = '$db_migration_path'",
		"limit 1",
	]));

	$result = $stmt->fetch();
	var_dump($result);die();
}