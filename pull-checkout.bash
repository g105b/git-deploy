#!/bin/bash
dir="$(dirname "$0")"
source "$dir/config.ini"

cd $repo_url
git pull $repo_url
git --work-tree=$destination_path --git-dir=$repo_dir.git checkout -f
./post-checkout.bash
