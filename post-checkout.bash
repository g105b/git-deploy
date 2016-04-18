#!/bin/bash
#
# Required param $1: Name of the repository with any
# slashes replaced with underscores
set -e
dir="$(dirname "$0")"
exec "$dir/db.php $1"