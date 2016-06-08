#!/usr/bin/php
<?php
$repoNameNoSlashes = $argv[1];

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
			. $e->getMessage() . PHP_EOL
		);
	}

	$migrationTableName = getenv("db_table");
	if(empty($migrationTableName)) {
		$migrationTableName = "db_migration";
	}

	$db_name = getenv("db_name");
	$db_name = strtolower($db_name);

	if(isset($argv[2]) && $argv[2] === "delete") {
		try {
			$dbh->exec("drop database if exists `$db_name`");
			echo "Completed db removal script." . PHP_EOL;
			exit;
		}
		catch(PDOException $e) {
			die("Failed deleting database: $db_name" . PHP_EOL);
		}
	}
	try {
		$dbh->exec("create database if not exists `$db_name`");
		$dbh->exec("use `$db_name`");
	}
	catch(PDOException $e) {
		die("Failed setting database name to: $db_name" . PHP_EOL);
	}

	try {
		// Ensure table is created.
		$dbh->exec(implode("\n", [
			"create table if not exists `$migrationTableName` (",
			"`project` varchar(64) primary key,",
			"`version` int",
			")",
		]));
	}
	catch(PDOException $e) {
		die("Failed creating migration table. "
			. $e->getMessage() . PHP_EOL);
	}

	try {
		$stmt = $dbh->query(implode("\n", [
			"select `version`",
			"from `$migrationTableName`",
			"where `project` = '$repoNameNoSlashes'",
			"limit 1",
		]));
		$result = $stmt->fetch();
	}
	catch(PDOException $e) {
		die("Failed fetching migration version. "
			. $e->getMessage() . PHP_EOL);
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

	if(empty($queryArray)) {
		echo "No new queries staged." . PHP_EOL;
	}

	foreach ($queryArray as $scriptFileName => $query) {
		$fileName = pathinfo($scriptFileName, PATHINFO_FILENAME);
		preg_match("/^([0-9]+)/", $fileName, $matches);
		$number = $matches[1];

		if($number <= $currentMigrationValue) {
			continue;
		}

		echo "Applying migration: $number" . PHP_EOL;

		$subQuery = 0;

		try {
			$queryLines = explode(";", $query);
			echo "Total subqueries: " . count($queryLines) . PHP_EOL;

			foreach ($queryLines as $q) {
				$q = trim($q, " \n\t");

				if(empty($q)) {
					continue;
				}

				$subQuery++;
				echo "Subquery $subQuery ... ";
				$dbh->exec($q) . ";";
				echo "complete. ";
			}

			echo PHP_EOL;

			$currentMigrationValue = $number;
			$dbh->exec(implode("\n", [
				"replace `$migrationTableName`",
				"set `project` = '$repoNameNoSlashes',",
				"`version` = $currentMigrationValue",
			]));
		}
		catch(PDOException $e) {
			die("Error applying migration $number. "
				. $e->getMessage() . PHP_EOL . PHP_EOL
				. $q);
		}
	}

	echo "Completed db script." . PHP_EOL;
}