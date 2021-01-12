1. Setup
* Check out the latest code
* Install Node.JS (used for node package mangement)
* Install Composer (used for php package management)
* In the root of the code directory, run: **npm install** , this will install all JS dependencies such as gulp
* In the root of the code directory, run: **composer install** alt. **php composer.phar install** , this will install all PHP dependencies

2. During development
* Ensure that **gulp watch** is running to ensure that Javascript minification and SASS compile is active

3. Deployment
* Important: **Do not deploy the /config directory**

4. Additionals to notice:
* MySQL stored procedures will not be included in the dump that is imported. Create these manually using the scripts in /db



Changes in 2019-09-21 Build
- Added prop categories. Installation of new code base will require the new prop categories schema to be run and that props are properly assigned to categories (separate admin interface to be defined)



Useful commands:
* Merge master into feature> 
    git checkout feature1
    git merge master