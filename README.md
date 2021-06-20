# BFO Platform

Platform for sports betting odds aggregation

## Local installation

Follow the steps below to setup the platform in a local development environment. For details on how to install this on AWS Lightsail, see separate How to documentation on the topic

### Setup codebase and dependencies

Make sure you have Composer installed (used for php package management). Download [here](https://getcomposer.org/download/)

Make sure you have NodeJS installed (used for node package management). Download [here](https://nodejs.org/en/download/)

Clone the repository to check out the codebase

```
git clone https://<username>@bitbucket.org/capappsab/bfo.git
```

In the root of the repository run the following Composer command to install all necessary PHP dependencies

```
composer install
```

For each of the websites (www.bestfightodds.com, www.proboxingodds.com), access each associated site directory (bfo, pbo) and install the Node dependencies used in development:

```
npm install
```

### Install and configure Nginx

Install PHP 8. Download [here](https://windows.php.net/download#php-8.0). Make sure you have the following extensions enabled in your `php.ini` file:

```yaml
extension=curl
extension=gd
extension=mbstring
extension=mysqli
extension=pdo_mysql
```

Install NginX. Download [here](http://nginx.org/en/download.html)

Open the `nginx/conf/nginx.conf` file and point to the appropriate configuration file in the `/<site>/env/nginx` directory to use (e.g. localhost.conf for local development). For example, here we point to the localhost configuration for bestfightodds:

```yaml
http {
    ...
    include c:/dev/bfo/bfo/env/nginx/localhost.conf;
    ...
}
```

### Create a new site configuration file

Located in the `/bfo/<site>/config` directory is a config template `inc.config-template.php` that you can be use as a base configuration file. Make a copy of this file in the same directory and rename the new config file `inc.config.php`

Update the file with any necessary settings (see Application Specification document for more details). Be careful to not disable dev mode on specific features while in non production

## Development

PHP development will only require that Nginx and PHP-FM is running. If configured properly, any changes you make to the code should be reflected automatically in your browser

To start PHP-FM and Nginx (example here on Windows):

```
php-cgi.exe -b 127.0.0.1:9123
```
```
nginx.exe
```

For front-end Javascript and CSS changes you will need to have gulp running to monitor any changes to .js and .scss files as gulp will automatically compile and minify these for usage.

Within the site specific directory (e.g. `/bfo` or `/pbo`) run the following command before you start modifying and .js and .scss files

```
gulp watch
```

With PHP, Nginx and Gulp running you should be all set. Commit and push changes as with any Git repository. Note that pushing changes to the main branch will not trigger any automatic deployment to production. Code pulls in production are done manually on demand

## Testing 

The platform uses PHPUnit for general testing of shared components. The PHPUnit scripts are available in the `/tests` directory of the repository and can be run using the following command while in the repository root:

```
vendor\bin\phpunit tests
```

## Branches

The repository currently has two branches available: `master (origin)` and `feature` . The `master` branch is should be used for production fixes and the `feature` branch for longer running development activities that are later merged into the master branch

To check out either branch run the following command (in this example we are checking out the feature branch)

```
git checkout feature
```

You can switch between the branches as needed. If you are working in the feature branch you can run the following command to retrofit any changes from `master` branch into the ongoing feature branch. While in the `feature` branch, run:

```
git merge origin
```

Once you have completed development in the feature branch and want to merge this into the master to later deploy into production, run the following commands:

```
git checkout master
git pull origin master
git merge feature
git push origin master
```

If you need to completely reset an environment to go back to the latest commit in the `master` branch. Run the following command:

```
git fetch --all
git reset --hard origin/master
```
