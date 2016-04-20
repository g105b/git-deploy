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
			getenv("db_dsn"). ":host=" . getenv("db_host"),
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

	$db_name = getenv("db_name");
	try {
		$dbh->exec("drop database if exists `$db_name`");
		$dbh->exec("create database `$db_name`");
		$dbh->exec("use `$db_name`");
	}
	catch(PDOException $e) {
		die("Failed setting database name to: $db_name");
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

	if($result) {
		$currentMigrationValue = (int)$result["version"];
	}
	else {
		$currentMigrationValue = 0;
	}

	echo "Current db_migration value is: $currentMigrationValue" . PHP_EOL;

	echo "Checking path: $dbMigrationPath" . PHP_EOL;

	$queryArray = [];
	foreach (new DirectoryIterator($dbMigrationPath) as $fileInfo) {
		if($fileInfo->isDot()
		|| $fileInfo->isDir()) {
			continue;
		}

		$fileName = $fileInfo->getPathname();

		$contents = file_get_contents($fileName);
		$queryArray [$fileName]= $contents;
	}

	ksort($queryArray);

	foreach ($queryArray as $scriptFileName => $query) {
		$fileName = pathinfo($scriptFileName, PATHINFO_FILENAME);
		preg_match("/^([0-9]+)/", $fileName, $matches);
		$number = $matches[1];

		if($number <= $currentMigrationValue) {
			continue;
		}

		echo "Applying migration: $number" . PHP_EOL;

		try {
			$dbh->exec($query);

			$currentMigrationValue = $number;
			$dbh->exec(implode("\n", [
				"update `$migrationTableName`",
				"set `version` = $currentMigrationValue",
				"where `project` = '$dbMigrationPath'",
				"limit 1",
			]));
		}
		catch(PDOException $e) {
			die("Error applying migration $number. "
				. $e->getMessage());
		}
	}

	echo "Completed db script." . PHP_EOL;
}