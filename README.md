# Karma8_test
Karma8 test task

## Install 

First of add copy and properly fill .env file from [.env.example](.env.example)

```docker-compose up```

OR 

- install mysql and create schema (.provision/docker/initdb.d/schema.sql) 
- install php pcntl-fork
- install php shmop
- add to minutely cron
 ```* * * * * php /srv/www/script.php```

## Run

- Open http://localhost/script.php
- login to docker container and run ```php script.php```

## TODO

- composer
- unit tests