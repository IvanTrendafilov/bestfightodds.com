#!/bin/bash

backupdir="/var/www/vhosts/proboxingodds.com/httpdocs/db/scripts/backup/"
gdrivedir="/var/www/vhosts/proboxingodds.com/gdrive/PBO_Backups/"
gdrivepath="/var/www/vhosts/proboxingodds.com/gopath/bin/"
backupfilename="pbo_code_backup_$(date '+%Y%m%d%H%M%S')"

echo 'Starting backup'
cd $gdrivedir/
rm $gdrivedir/pbo_code_backup_*.zip
echo 'Cleared old backups'
zip -r $gdrivedir/$backupfilename.zip /var/www/vhosts/proboxingodds.com/httpdocs -x '/var/www/vhosts/proboxingodds.com/httpdocs/logs/*' '/var/www/vhosts/proboxingodds.com/httpdocs/app/front/pages/cache/*' '/var/www/vhosts/proboxingodds.com/httpdocs/app/front/img/cache/*' '/var/www/vhosts/proboxingodds.com/httpdocs/db/scripts/backup/*'
echo 'Dump complete/compression complete'
cd $gdrivedir/
$gdrivepath/drive push -no-prompt $backupfilename.zip
rm $backupfilename.zip