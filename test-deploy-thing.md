# test-deploy-thing

* Clone `github-deploy` to `/var/git/github-deploy`.
* Serve webhook.php on `http://server.ip.addr/github-deploy` (`nginx.patch`).
* "Webhook script installed successfully" message.
* Create new empty Github repo called `test-deploy-thing`.
* cp `config.ini` `config.d/EvolutionFunding_MBMCRM`
* `ssh-keygen -t rsa -b 4096 -C "repo@test-env"`` (`/var/git/id_rsa_repo`.)
* Make sure webserver can read private key (put it in a group).
* Add public key to Github repo's deploy key.
* Add path to private key to config.ini (uncomment).
* Add webhook to Github repo settings to same URL as above.
* Create a secret to paste into the config file on the server.
* Choose "send everything".
* Webhook will 401 because webhook_secret is not yet set.
* Set the `webhook_secret` the same as in the webhook settings screen.
* Copy the repo name from the response.
* `mkdir -p /var/git/github-deploy/config.d`.
* Copy config.ini into config.d directory, rename to `copied-repo-name.ini`.
* Edit repo's config - `webhook_branch=*` for all repos - to make dynamic.
* `repo_dir=/var/git/{repo}/{branch}` for dynamic clones branches.
* `destination_path=/var/www/{repo}/{branch}` for dynamic checkouts.
* Or, use `/var/www/www.example.com/{branch}` if necessary.
* Ensure webserver can write to /var/git and /var/www.
* Install composer globally (for projects the use composer).
* When a commit is pushed, the webhook will checkout to /var/www.