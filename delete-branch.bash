#!/bin/bash
set -e
dir="$(dirname "$0")"

echo "Deleting branch from $1"
echo "Branch to delete: $2"
echo "Repo dir: $3"
echo "Destination path $4"
echo "--------------"

if [ -d $3 ]; then
	rm -rf $3
fi

if [ -d $4 ]; then
	rm -rf $4
fi

eval "$dir/db.php $1 delete"