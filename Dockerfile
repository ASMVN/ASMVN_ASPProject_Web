FROM php:8.0-fpm-bullseye

# Cài Nginx
RUN apt-get update && apt-get install -y nginx \
    && rm -rf /var/lib/apt/lists/*

# Copy source code
COPY . /var/www/html

# Copy config Nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Set quyền
RUN chown -R www-data:www-data /var/www/html

# Expose port 8080 (Azure App Service dùng 8080)
EXPOSE 8080

# Start PHP-FPM + Nginx
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
