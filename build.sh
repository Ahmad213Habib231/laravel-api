#!/bin/bash

# تحديث الحزم
apt-get update

# تثبيت Python 3 و pip3
apt-get install -y python3 python3-pip

# تثبيت مكتبات البايثون المطلوبة (غير المكتبات دي حسب مشروعك)
pip3 install -r requirements.txt


# تثبيت مكتبات PHP
composer install --no-dev --optimize-autoloader

# كاش الكونفيج والـ migrations
php artisan config:cache
php artisan migrate --force
