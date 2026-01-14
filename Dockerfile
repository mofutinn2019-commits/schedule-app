FROM php:8.2-apache

# Apacheでindex.phpを有効にする
RUN docker-php-ext-install pdo pdo_mysql

# index.php を Apache の公開フォルダへコピー
COPY index.php /var/www/html/

# Apache用ポート
EXPOSE 80

