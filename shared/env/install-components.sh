#!/bin/sh

# This file will install all components required by the BFO platform. Note that this only needs to be done once per server (not once per site like bestfightodds.com or proboxingodds.com)
# It is safe to run this multiple times
#
# Before running, ensure that you are a user that has sudo rights

# Update Package Index
sudo apt update

# Install nginx
sudo apt install -y nginx

# Install python-pip (required for aws cli), 
sudo apt install -y python-pip

# Install apache utils (for .htpasswd generation)
sudo apt install -y apache2-utils

# Install PHP 7.2
sudo apt install -y php7.2

# Install PHP extensions (Mysqli, GD, Mysql, Curl, XML, mbstring)
sudo apt install -y php7.2-mysqli php-gd php7.2-mysql php-curl php7.2-xml php-mbstring

# Install Composer PHP
curl -sS https://getcomposer.org/installer -o ~/composer-setup.php
sudo php ~/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f ~/composer-setup.php

# Install AWS CLI via PIP
sudo pip install awscli

# Install Mysql client for accessing mysql from ssh
sudo apt install -y mysql-client-core-5.7
