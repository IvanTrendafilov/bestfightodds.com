#!/bin/sh

# This file will install all components required by the BFO platform. Note that this only needs to be done once per server (not once per site like bestfightodds.com or proboxingodds.com)
# It is safe to run this multiple times
#
# Before running, ensure that you are a user that has sudo rights

# Add Ondrej PPA for latest PHP versions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y

# Update Package Index
sudo apt update

# Install nginx
sudo apt install -y nginx

# Install python-pip (required for aws cli), 
sudo apt install -y python-pip

# Install apache utils (for .htpasswd generation)
sudo apt install -y apache2-utils

# Install PHP 7.2
#sudo apt install -y php7.4 php7.4-common php7.4-cli
# Install PHP extensions (Mysqli, GD, Mysql, Curl, XML, mbstring)
#sudo apt install -y php7.4-fpm php7.4-mysqli php7.4-gd php7.4-mysql php7.4-curl php7.4-xml php7.4-mbstring php7.4-zip

# Install PHP 8
sudo apt install -y php8.0 php8.0-fpm php8.0-common php8.0-mysql php8.0-gmp php8.0-curl php8.0-mbstring php8.0-xmlrpc php8.0-gd php8.0-xml php8.0-readline php8.0-cli php8.0-zip

# Install Composer PHP
curl -sS https://getcomposer.org/installer -o ~/composer-setup.php
sudo php ~/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f ~/composer-setup.php

# Install AWS CLI via PIP
sudo pip install -y awscli

# Install Mysql client for accessing mysql from ssh
sudo apt install -y mysql-client

# Enable PHP modules
sudo phpenmod mysqli pdo_mysql curl gd

# Install zip and unzip
sudo apt install -y zip unzip

# Install ChromeDriver for Symfony Panther
sudo apt install chromium-chromedriver