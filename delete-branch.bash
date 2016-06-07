#!/bin/bash
set -e
dir="$(dirname "$0")"

echo "Config values:"
echo "webhook_event=$webhook_event"
echo "webhook_branch=$webhook_branch"
echo "received_branch=$received_branch"
echo "webhook_log_path=$webhook_log_path"
echo "repo_url=$repo_url"
echo "repo_dir=$repo_dir"
echo "destination_path=$destination_path"
echo "ssh_private_key=$ssh_private_key"
echo "--------------"
echo "db_dsn=$db_dsn"
echo "db_migration_path=$db_migration_path"
echo "db_host=$db_host"
echo "db_name=$db_name"
echo "db_user=$db_user"
echo "db_pass=$db_pass"
echo "db_table=$db_table"
echo "--------------"

if [ -d $repo_dir ]; then
	rm -rf $repo_dir
fi

if [ -d $destination_path ]; then
	rm -rf $destination_path
fi