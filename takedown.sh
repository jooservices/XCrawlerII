#! /bin/bash

cd ~/XCrawlerII

echo 'Taking down'
php artisan down
sudo systemctl stop supervisor.service
