# Online file explorer

This website is designed for linux servers, running it on windows will require changes in code.

To set up this website you need to execute:
```shell
# install composer
wget https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer -O - -q | php -- --quiet
# initialize composer modules
php website/composer.phar install
# make sql database
mysql --host="mysql_server" --user="user_name" --password="user_password" < "create-database.sql"
```
Additionally you have to set our webserver and php.ini to allow large file uploads.

Explanation of database table fields is in collumns' comments
