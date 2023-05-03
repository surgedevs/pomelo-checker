# pomelo-checker

Site for checking availability of Pomelo names.

# ATTENTION
# THIS SITE SHUT DOWN AT DISCORDS REQUEST
# IM NOT RESPONSIBLE FOR ANYTHING YOU DO WITH THIS

## Requirements

- At least PHP 8.2
- Default PHP Extensions
- MySQL Database
- Apache2 Webserver
- Apache RewriteEngine

## Deployment

Add .htaccess to root (if required)

Example .htaccess
```
RewriteEngine On

RewriteCond %{REQUEST_URI} !^/static/ [NC]
RewriteRule ^(.*)$ index.php [L]

RewriteCond %{REQUEST_URI} ^/static/ [NC]
RewriteRule ^(.*)$ - [L]
```

Add Database dump to your database (pomelo.sql)

Fill in config.json

token: Discord account token (must be a valid, non-bot token)

database:
host: Database host IP
dbname: Database name
username: Database username
password: Database password

Set up Apache vHost pointing to the directory of the site.
