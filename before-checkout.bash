#!/bin/bash
#
# Required param $1: Name of the repository with any
# slashes replaced with underscores
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

if [ -z "$repo_dir" -o -z "$repo_url" ]; then
	echo "Config value not set"
	exit;
fi

if [ ! -d $repo_dir ]; then
	mkdir -p $repo_dir
	echo "Cloning $repo_url into $repo_dir on branch $received_branch"
	git_cmd="git clone $repo_url $repo_dir"

	if [ -n $ssh_private_key ]; then
		git_cmd="GIT_SSH_COMMAND='ssh -i $ssh_private_key -o StrictHostKeyChecking=no' $git_cmd"
	fi

	echo "Git command: $git_cmd"
	eval $git_cmd
fi

cd $repo_dir
git_cmd="git fetch --all"
if [ -n $ssh_private_key ]; then
	git_cmd="GIT_SSH_COMMAND='ssh -i $ssh_private_key -o StrictHostKeyChecking=no' $git_cmd"
fi
echo "Running git command: $git_cmd"
eval $git_cmd

git_cmd="git checkout $received_branch"
echo "Running git command: $git_cmd"
eval $git_cmd

git_cmd="git pull"
if [ -n $ssh_private_key ]; then
	git_cmd="GIT_SSH_COMMAND='ssh -i $ssh_private_key -o StrictHostKeyChecking=no' $git_cmd"
fi
echo "Running git command: $git_cmd"
eval $git_cmd

destination_path=$(echo "$destination_path" | tr '[:upper:]' '[:lower:]')
if [ ! -f $destination_path ]; then
	mkdir -p $destination_path
fi
echo "Running git command: git --work-tree=$destination_path --git-dir=$repo_dir/.git checkout -f"

git --work-tree=$destination_path --git-dir=$repo_dir/.git checkout $received_branch -f

echo "-----------------------------"
echo "Running after-checkout scripts"

vars="webhook_event=$webhook_event webhook_branch=$webhook_branch received_branch=$received_branch webhook_log_path=$webhook_log_path repo_url=$repo_url repo_dir=$repo_dir destination_path=$destination_path ssh_private_key=$ssh_private_key"

if [ -f "$dir/after-checkout.bash" ]; then
	echo "Running generic after-checkout.bash"
	cmd="$vars $dir/after-checkout.bash $1"
	echo "Command: $cmd"
	eval $cmd;
else
	echo "Generic after-checkout.bash not found ( $dir/after-checkout.bash )"
fi

if [ -d "$dir/after-checkout.d" ]; then
	if [ -f "$dir/after-checkout.d/$1.bash" ]; then
		echo "Running after-checkout"
		cmd="$vars $dir/after-checkout.d/$1.bash"
		echo "Command: $cmd"
		eval $cmd
	fi
fi

echo "Completed before-checkout"