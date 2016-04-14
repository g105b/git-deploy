#!/bin/bash
set -e
dir="$(dirname "$0")"
source "$dir/config.ini"

if [ -d "$dir/config.d" ]; then
	if [ -f "$dir/config.d/$1.ini" ]; then
		source "$dir/config.d/$1.ini"
	fi
fi

if [ -z "$repo_dir" -o -z "$repo_url" ]; then
	echo "Config value not set"
	exit;
fi

if [ ! -f $repo_dir ]; then
	echo "Cloning $repo_url into $repo_dir on branch $webhook_branch"
	git_cmd="git clone -b $webhook_branch --single-branch $repo_url $repo_dir"

	if [ -n $ssh_private_key ]; then
		git_cmd="GIT_SSH_COMMAND='ssh -i $ssh_private_key' $git_cmd"
	fi

	$git_cmd
fi

cd $repo_dir
git_cmd="git pull $repo_url"
if [ -n $ssh_private_key ]; then
	git_cmd="GIT_SSH_COMMAND='ssh -i $ssh_private_key' $git_cmd"
fi
$git_cmd

git --work-tree=$destination_path --git-dir=$repo_dir.git checkout -f

if [ -f "$dir/post-checkout.bash" ]; then
	"$dir/post-checkout.bash $1"
fi

if [ -d "$dir/post-checkout.d" ]; then
	if [ -f "$dir/post-checkout.d/$1.bash" ]; then
		"$dir/post-checkout.d/$1.bash"
	fi
fi