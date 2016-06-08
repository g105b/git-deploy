# github-deploy

Automatically deploy one, multiple or all branches to a server from Github. Either update the server when a push is made, or when existing CI tests pass.

## Usage.

Note that the webhook can be called on `push` events or `status` events. Status events will be automatically dispatched by Github if a CI system is correctly set up. When using the status event, only success statuses will be used.

### Step by step server setup.

+ Make directory `/var/git`.
+ Clone git-deploy.
	+ `git clone https://github.com/g105b/git-deploy /var/git/git-deploy`.
+ Serve `webhook.php` on `https://you-server-ip/github-deploy`.
	+ See `nginx.patch` for default server block settings.
	+ You should see "Webhook script installed successfully".
+ Generate SSH deploy key for your repository.
	+ `ssh-keygen -t rsa -b 4096 -C "repo@server-name"`.
	+ Name the keys according to the repository name.
	+ Ensure webserver's user can read private key file.
	+ Add public key to Github repo's deploy key list.
+ Add Github webook to repository settings on same URL as above.
	+ Create a secret and paste into the repo's config file.
	+ Choose "Send everything".
	+ Webhook will show "pong" response to Github's "ping".
	+ Copy the repo name from the response.
+ Create a configuration file for your repository.
	+ `cp config.ini config.d/YourOrgName_RepoName.ini`.
	+ Name of the ini file is the coppied repo name from webhook response.
	+ Set path to keys in the config file.
	+ Configure database if required.
+ Ensure webserver can write to `/var/git` and `/var/www`
+ Install `composer` globally (for PHP projects that use Composer).

#### Deploy all branches.

+ Add `webhook_branch=*` to listen on all branches.
+ Set `repo_dir` to the path to the github repo on the server.
	+ Use {repo} and {branch} placeholders.
	+ e.g. `repo_dir=/var/git/{repo}/{branch}`
- Set `destination_path` to public location of served files.
	+ e.g. `destination_path=/var/www/{repo}/{branch}`

### Config files.

If the server only has one repository, all configuration can go into `config.ini`. Otherwise, individual configuration files can be put into the `config.d/` directory where the filenames match the repository's name (with any slashes replaced with underscores).

Configuration options:

+ `webhook_event`
	+ `push` or `status`. Push will update every time the branch is pushed to, status will update every time time branch passes its CI tests.
+ `webhook_branch`
	+ Name of branch to match. e.g. `master`, or `*` to match all branches.
+ `webhook_secret`
	+ Secret to authenticate Github's request. This needs pasting into Github too. e.g. `a long secret(that nobody should know!)`
+ `webhook_log_path`
	+ Absolute path on disk of file to write logs to. e.g. `/var/log/webhook.log`
+ `repo_url`
	+ The remote URL to pull. e.g. `git@github.com:username/repo.git`
+ `repo_dir`
	+ Absolute path on disk where the repo should be cloned. e.g. `/var/git/repo`
+ `destination_path`
	+ Absolute path on disk where the repo should be checked out after pulling. e.g. `/var/www/repo`

### Github administration.

+ Go to the settings screen for the particular repository, or organisation (to configure all repositories at once).
+ Choose the `Webhooks & services` tab.
+ Add webhook:
	+ URL: `http://xx.xx.xx.xx/github-deploy` (as configured in steps above)
	+ Content type: application/json
	+ Secret: _the same as what's stored in config.ini_
	+ Which events: Send everything
	+ Active: true
+ Github's ping should be responded with a pong from the webhook script.

### Post checkout scripts.

By default, `after-checkout.bash` will be executed directly after the checkout succeeds. The script will receive the repo name (with slashes removed) as the first argument.

It is possible to provide a separate bash script for each individual repository by putting them in the `after-checkout.d/` directory, as scripts named as the repository's name (with slashes replaced with underscores), e.g. `after-checkout.d/MyAccount_RepoName.bash`.

## Database.

Included within this repository is a database migration script, allowing you to keep your database in version control.

To run the database script, call it using the after-checkout script for your project, passing the name of the project as either the first argument (cli) or the `project` query string parameter (web).

e.g. `php db.php username_repo`

### Version table.

To utilise migrations, a table will be created within the project's database schema called `db_migration` (configurable in config.ini). This table has two columns: `project` (varchar 64) and `version` (int 10).

### Migration scripts.

Every alteration to the database, including the original table creation scripts, should be placed within a directory as numerically named files which will be run in order by db.php. db.php will record the number of script that has last been ran, so any changes will be kept in sync with local development and live.

The location of the migration scripts directory can be configured in `config.ini` using the `db_migration_path` key.
