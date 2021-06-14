1. Setup
* Check out the latest code
* Install Node.JS (used for node package mangement)
* Install Composer (used for php package management)
* In the root of the code directory, run: **npm install** , this will install all JS dependencies such as gulp
* In the root of the code directory, run: **composer install** alt. **php composer.phar install** , this will install all PHP dependencies

2. During development
* Ensure that **gulp watch** is running to ensure that Javascript minification and SASS compile is active

3. Deployment
* Important: **Do not commit the /config/inc.config.php file** (is excluded in .gitignore)

4. Additionals to notice:
* MySQL stored procedures will not be included in the dump that is imported. Create these manually using the scripts in /db


How to install locally:
1. Install Nginx
2. Point nginx config file in /env in nginx config:
    
    http {

    include c:/dev/bfo/bfo/env/nginx/localhost.conf;

3. Install PHP and enable modules for mysqli, gd, etc.
4. Create a start_php.bat script containing:
    @ECHO OFF
    ECHO Starting PHP FastCGI...
    set PATH=C:\dev\PHP;%PATH%
    C:\dev\PHP\php-cgi.exe -b 127.0.0.1:9123
5. Start the start_php.bat script and nginx

Handy Git stuff:
- Merging changes in master (origin) to feature branch
    1. Checkout the feature branch
    2. Run git merge origin
- Reverting to a commit
    1. Run this on dev computer: git revert --no-commit c6018e8..HEAD     (replacing c6018e8 with the commit to revert to)
- Totally resetting an environment
    1. git fetch --all
    2. git reset --hard origin/master
