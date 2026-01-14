FROM php:8.2-apache

# Apacheの設定
RUN a2enmod rewrite

# ファイルを公開ディレクトリにコピー
COPY . /var/www/html/

# 権限調整
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

