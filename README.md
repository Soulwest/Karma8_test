# Karma8_test
Karma8 test task

## Install 

- install mysql and create schema (.provision/schema.sql) 
- install php pcntl-fork
- install php shmop
- create config/db_credentials.php
- add to minutely cron
 ```* * * * * php /srv/www/script.php```

## Run

- Open http://localhost/script.php
- ```php script.php```

## TODO

- composer
- unit tests