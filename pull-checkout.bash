#!/bin/bash
#
# Required param $1: Name of the repository with any
# slashes replaced with underscores
set -e
dir="$(dirname "$0")"
source "$dir/config.ini"

if [ -d "$dir/config.d" ]; then
	if [ -f "$dir/config.d/$1.ini" ]; then
		source "$dir/config.d/$1.ini"
	fi
fi

echo "Config values:"
echo "webhook_event=$webhook_event"
echo "webhook_branch=$webhook_branch"
echo "webhook_secret=$webhook_secret"
echo "webhook_log_path=$webhook_log_path"
echo "repo_url=$repo_url"
echo "repo_dir=$repo_dir"
echo "destination_path=$destination_path"
echo "ssh_private_key=$ssh_private_key"
echo "--------------"

if [ -z "$repo_dir" -o -z "$repo_url" ]; then
	echo "Config value not set"
	exit;
fi

if [ ! -f $repo_dir ]; then
	echo "Cloning $repo_url into $repo_dir on branch $webhook_branch"
	git_cmd="git clone $repo_url $repo_dir"

	if [ -n $ssh_private_key ]; then
		git_cmd="GIT_SSH_COMMAND='ssh -i $ssh_private_key -o StrictHostKeyChecking=no' $git_cmd"
	fi

	echo "Git command: $git_cmd"
	eval $git_cmd
fi

cd $repo_dir
git_cmd="git pull $repo_url"
if [ -n $ssh_private_key ]; then
	git_cmd="GIT_SSH_COMMAND='ssh -i $ssh_private_key -o StrictHostKeyChecking=no' $git_cmd"
fi
echo "Running git command: $git_cmd"
eval $git_cmd

echo "Running git command: git --work-tree=$destination_path --git-dir=$repo_dir/.git checkout -f"
if [ ! -f $destination_path ]; then
	mkdir -p $destination_path
fi

git --work-tree=$destination_path --git-dir=$repo_dir/.git checkout -f

echo "Running post-checkout scripts"

if [ -f "$dir/post-checkout.bash" ]; then
	"$dir/post-checkout.bash $1"
fi

if [ -d "$dir/post-checkout.d" ]; then
	if [ -f "$dir/post-checkout.d/$1.bash" ]; then
		"$dir/post-checkout.d/$1.bash"
	fi
fi