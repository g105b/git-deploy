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
git checkout $received_branch
git_cmd="git pull $repo_url"
if [ -n $ssh_private_key ]; then
	git_cmd="GIT_SSH_COMMAND='ssh -i $ssh_private_key -o StrictHostKeyChecking=no' $git_cmd"
fi
echo "Running git command: $git_cmd"
eval $git_cmd

if [ ! -f $destination_path ]; then
	mkdir -p $destination_path
fi
echo "Running git command: git --work-tree=$destination_path --git-dir=$repo_dir/.git checkout $received_branch -f"

git --work-tree=$destination_path --git-dir=$repo_dir/.git checkout $received_branch -f

echo "-----------------------------"
echo "Running post-checkout scripts"

if [ -f "$dir/post-checkout.bash" ]; then
	echo "Running generic post-checkout.bash"
	eval "$dir/post-checkout.bash $1"
else
	echo "Generic post-checkout.bash not found ( $dir/post-checkout.bash )"
fi

if [ -d "$dir/post-checkout.d" ]; then
	if [ -f "$dir/post-checkout.d/$1.bash" ]; then
		echo "Running post-checkout $1"
		eval "$dir/post-checkout.d/$1.bash"
	fi
fi