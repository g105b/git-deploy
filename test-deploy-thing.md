# test-deploy-thing

* Clone `github-deploy` to `/var/git/github-deploy`.
* Serve webhook.php on `http://server.ip.addr/github-deploy` (`nginx.patch`).
* "Webhook script installed successfully" message.
* Create new empty Github repo called `test-deploy-thing`.
* Generate ssh keys on server at `/var/git/id_rsa`.
* Add public key to Github repo's deploy key.
* Add webhook to Github repo settings to same URL as above.
* Create a secret to paste into the config file on the server.
* Choose "send everything".
* Webhook will 401 because webhook_secret is not yet set.
* Copy the repo name from the response.
* `mkdir /var/git/github-deploy/config.d`.
* Copy config.ini into the config.d directory, rename to `copied-repo-name.ini`.
* Edit repo's config - `webhook_branch=*` for all repos.
* `repo_dir=/var/git/{repo}/{branch}` for dynamic clones branches.
* `destination_path=/var/www/{repo}/{branch}` for dynamic checkouts.
* Or, use `/var/www/www.example.com/{branch}` if necessary.