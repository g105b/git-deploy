#!/bin/bash
set -e
dir="$(dirname "$0")"
source "$dir/config.ini"

if [ -d "$dir/config.d" ]; then
	if [ -f "$dir/config.d/$1.ini" ]; then
		source "$dir/config.d/$1.ini"
	fi
fi

if [ ! -f $repo_dir ]; then
	git clone -b $webhook_branch --single-branch $repo_url $repo_dir
fi

cd $repo_dir
git pull $repo_url
git --work-tree=$destination_path --git-dir=$repo_dir.git checkout -f
"$dir/post-checkout.bash $1"

if [ -d "$dir/post-checkout.d" ]; then
	if [ -f "$dir/post-checkout.d/$1.bash" ]; then
		"$dir/post-checkout.d/$1.bash"
	fi
fi