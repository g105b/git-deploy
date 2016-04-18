# git-deploy

Deploy to live using Git. Update the live server when the live branch is updated, or when CI tests pass.

## Usage.

Note that the webhook can be called on `push` events or `status` events. Status events will be automatically dispatched by Github if a CI system is correctly set up. When using the status event, only success statuses will be used.

### Live server administration.

+ Make directory `/var/git`
+ Clone git-deploy.
	+ `git clone https://github.com/g105b/git-deploy /var/git/git-deploy`
+ Add environment variables to `/var/git/config.ini`, e.g. :
	+ `webhook_secret=secret-to-give-to-github`
	+ `webhook_log_path=/var/git/webhook.log`
+ Configure webserver to respond on `/webhook` path.
	+ Default nginx patch available in `nginx.patch` file.
	+ `patch /etc/nginx/sites-available/default < /var/git/git-deploy/nginx.patch`
+ Reload webserver configuration.
	+ `service nginx reload`
+ Test the webhook script is accessable by loading the server's IP browser with the path:
	+ `http://xx.xx.xx.xx/webhook`

### Config files.

If the server only has one repository, all configuration can go into `config.ini`. Otherwise, individual configuration files can be put into the `config.d/` directory where the filenames match the repository's name (with any slashes replaced with underscores).

Configuration options:

+ `webhook_event`
	+ `push` or `status`. Push will update every time the branch is pushed to, status will update every time time branch passes its CI tests.
+ `webhook_branch`
	+ Name of branch to match. e.g. `master`
+ `webhook_secret`
	+ Secret to authenticate Github's request. This needs pasting into Github too. e.g. `a long secret(that nobody should know!)`
+ `webhook_log_path`
	+ Absolute path on disk of file to write logs to. e.g. `/var/log/webhook.log`
+ `repo_url`
	+ The remote URL to pull. e.g. `https://github.com/username/repo.git`
+ `repo_dir`
	+ Absolute path on disk where the repo should be cloned. e.g. `/var/git/repo`
+ `destination_path`
	+ Absolute path on disk where the repo should be checked out after pulling. e.g. `/var/www/repo`

### Github administration.

+ Go to the settings screen for the particular repository.
+ Choose the `Webhooks & services` tab.
+ Add webhook:
	+ URL: `http://xx.xx.xx.xx/webhook`
	+ Content type: application/json
	+ Secret: _the same as what's stored in config.ini_
	+ Which events: Just the push event
	+ Active: true
+ Github's ping should be responded with a pong from the webhook script.

### Post checkout scripts.

By default, `post-checkout.bash` will be executed directly after the checkout succeeds. The script will receive the repo name (with slashes removed) as the first argument.

It is possible to provide a separate bash script for each individual repository by putting them in the `post-checkout.d/` directory, as scripts named as the repository's name (with slashes replaced with underscores), e.g. `post-checkout.d/MyAccount_RepoName.bash`.

## Database.

Included within this repository is a database migration script, allowing you to keep your database in version control.

To run the database script, call it using the post-checkout script for your project, passing the name of the project as either the first argument (cli) or the `project` query string parameter (web).

e.g. `php db.php username_repo`

### Version table.

### Migration scripts.