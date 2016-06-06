#!/bin/bash
#
# Required param $1: Name of the repository with any
# slashes replaced with underscores
set -e
dir="$(dirname "$0")"

export webhook_event=$webhook_event
export webhook_branch=$webhook_branch
export received_branch=$received_branch
export webhook_log_path=$webhook_log_path
export repo_url=$repo_url
export repo_dir=$repo_dir
export destination_path=$destination_path
export ssh_private_key=$ssh_private_key

eval "$dir/db.php $1"