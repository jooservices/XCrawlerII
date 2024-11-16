#! /bin/bash

cd ~/XCrawlerII

echo 'Taking down'
php artisan down
sudo systemctl stop supervisor.service

echo 'Updating'
git pull
composer install
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan optimize:clear

echo 'Migrating'
php artisan config:cache
php artisan route:cache
php artisan optimize
php artisan migrate --force

echo 'Bringing up'
php artisan queue:restart
php artisan up

sudo systemctl start supervisor.service
