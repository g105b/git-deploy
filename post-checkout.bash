#!/bin/bash
set -e
dir="$(dirname "$0")"
exec "$dir/db.php $1"