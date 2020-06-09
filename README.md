### The Reliable Php setup

Run docker services
    `docker-compose up`

Stop docker services
    `docker-compose down`

Shell into maria_db
    `docker-compose run maria_db /bin/sh`

### Shell into apache env, to do laravel stuff
    Run: 
    `docker-compose run -p 4000:8000 apache /bin/bash`.

    Then in the shell run `cd /var/www/html/` to acccess the shell.

    From there you'll be in the source files, and be able to run your laravel
    stuff.

