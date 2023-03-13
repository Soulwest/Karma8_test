FROM php:8.1-fpm

# Install MySQL client
RUN apt-get update && \
    apt-get install -y default-mysql-client

# Install PHP extensions
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install shmop
RUN docker-php-ext-install pcntl


# Copy the project files to the container
COPY . /var/www/html/
WORKDIR /var/www/html/

# Start Bult-in PHP server
CMD ["php", "-S", "0.0.0.0:8000"]

#Install Cron
RUN apt-get update
RUN apt-get -y install cron

# Add the cron job
RUN crontab -l | { cat; echo "* * * * * php /var/www/html/script.php"; } | crontab -

# Run the command on container startup
CMD cron

EXPOSE 80