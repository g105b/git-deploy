#!/bin/bash
cd $REPO_DIR
git pull $REPO_URL
git --work-tree=$DESTINATION_PATH --git-dir=$REPO_DIR.git checkout -f
./post-checkout.bash
