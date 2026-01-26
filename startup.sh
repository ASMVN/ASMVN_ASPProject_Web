#!/bin/bash
# Copy file nginx.conf từ wwwroot sang chỗ Nginx thật sự
cp /home/site/wwwroot/nginx.conf /etc/nginx/sites-available/default

# Restart lại Nginx để áp dụng config mới
service nginx reload

# Cuối cùng, chạy PHP-FPM
php-fpm
