docker-compose build
docker-compose up

phpmyadmin login:
user: root
pass: root

# vorbereitungen
sudo apt install iproute2
sudo apt install nmap

curl -sL https://deb.nodesource.com/setup_12.x | bash -
sudo apt update && sudo apt install nodejs

curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt update && sudo apt install yarn

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
