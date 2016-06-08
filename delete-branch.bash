#!/bin/bash
set -e
dir="$(dirname "$0")"

echo "Deleting branch from $1"
echo "Branch to delete: $2"
echo "Repo dir: $3"
echo "Destination path $4"
echo "--------------"

if [ -d $3 ]; then
	cmd="rm -rf $3"
	echo "Command: $cmd"
	eval "$cmd"
fi

if [ -d $4 ]; then
	cmd="rm -rf $4"
	echo "Command: $cmd"
	eval "$cmd"
fi

eval "$dir/db.php $1 delete"