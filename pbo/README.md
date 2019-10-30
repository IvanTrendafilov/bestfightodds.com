1. Setup
* Check out the latest code
* Install Node.JS (used for node package mangement)
* Install Composer (used for php package management)
* In the root of the code directory, run: **npm install** , this will install all JS dependencies such as gulp
* In the root of the code directory, run: **composer install** alt. **php composer.phar install** , this will install all PHP dependencies such as gulp

2. During development
* Ensure that **gulp watch** is running to ensure that Javascript minification and SASS compile is active

3. Deployment
* Important: **Do not deploy the /config directory**