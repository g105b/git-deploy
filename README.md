# git-deploy

## Rough notes.

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