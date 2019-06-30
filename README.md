docker-compose build
docker-compose up

phpmyadmin login:
user: root
pass: root

# vorbereitungen
sudo apt install iproute2

sudo apt install nmap
sudo apt install yarn
yarn install
yarn add @symfony/webpack-encore --dev

# zum bilden der assets
encore dev

# build für prod
encore production

# watch der assets
encore dev --watch

# create database
php bin/console doctrine:database:create

php bin/console doctrine:migrations:migrate

# weitere informationen:
# anlegen einer initialen migration vom aktuellen datenbank schema:
php bin/console doctrine:migrations:dump-schema
