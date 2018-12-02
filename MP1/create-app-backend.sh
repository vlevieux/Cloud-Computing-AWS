#!/bin/bash
# PHP composer install link hhttps://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
sudo apt-get update
sudo apt-get install -y apache2 git curl php php-simplexml unzip zip libapache2-mod-php php-xml php-mysql

# download and install php composer - https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
#wget -q -O - https://composer.github.io/installer.sig

cd /home/ubuntu
sudo php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --quiet

# download and install aws-skp-php library and package
sudo php -d memory_limit=-1 composer.phar require aws/aws-sdk-php 1>> /home/ubuntu/out.log 2>> /home/ubuntu/err.log

# move vendor to /home/ubuntu
sudo mv vendor/ /home/ubuntu

su - ubuntu -l -c "git clone git@github.com:illinoistech-itm/vlevieux.git"  1>> /home/ubuntu/out.log 2>> /home/ubuntu/err.log

sudo cp /home/ubuntu/vlevieux/itmd-544/MP2/*.php /var/www/html/
sudo rm /var/www/html/index.html

sudo systemctl reload apache2
