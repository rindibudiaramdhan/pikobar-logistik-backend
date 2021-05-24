#!/bin/sh
app=${DOCKER_APP:-app}

if [ "$app" = "app" ]; then
    #!/bin/bash
    printf "Checking database connection...\n\n"
    mysql_ready() {
        /usr/bin/mysqladmin ping --host=$DB_HOST --user=$DB_USERNAME --password=$DB_PASSWORD > /dev/null 2>&1
    }

    while !(mysql_ready)
    do
        sleep 3
        echo "Waiting for database connection ..."
    done

    php composer.phar dump-autoload
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan migrate --no-interaction -vvv --force

    echo "Running the app..."
    /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

elif [ "$app" = "queue" ]; then

    echo "Running the queue..."
    php artisan queue:work --queue=default --sleep=3 --tries=3

else
    echo "Could not match the container app \"$app\""
    exit 1
fi
