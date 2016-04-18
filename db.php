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
			getenv("db_dsn")
				. ":dbname=" . getenv("db_name")
				. ";host=" . getenv("db_host"),
			getenv("db_user"),
			getenv("db_pass")
		);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch(PDOException $e) {
		die("Error connecting to database to perform migrations. "
			. $e->getMessage()
		);
	}

	$migrationTableName = getenv("db_table");
	if(empty($migrationTableName)) {
		$migrationTableName = "db_migration";
	}

	try {
		// Ensure table is created
		$dbh->exec(implode("\n", [
			"create table if not exists `$migrationTableName` (",
			"`project` varchar(64) primary key,",
			"`version` int",
			")",
		]));
	}
	catch(PDOException $e) {
		die("Failed creating migration table. "
			. $e->getMessage());
	}

	try {
		$stmt = $dbh->query(implode("\n", [
			"select `version`",
			"from `$migrationTableName`",
			"where `project` = '$dbMigrationPath'",
			"limit 1",
		]));
		$result = $stmt->fetch();
	}
	catch(PDOException $e) {
		die("Failed fetching migration version. "
			. $e->getMessage());
	}

	var_dump($result);die();
}