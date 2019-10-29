#!/bin/bash

backupdir="/var/www/vhosts/bestfightodds.com/httpdocs/db/scripts/backup/"
gdrivedir="/var/www/vhosts/bestfightodds.com/gdrive/BFO_Backups/"
gdrivepath="/var/www/vhosts/bestfightodds.com/gopath/bin/"
backupfilename="bfo2_backup_$(date '+%Y%m%d%H%M%S')"

echo 'Starting backup'
cd $backupdir/
rm $backupdir/bfo2_backup_*.sql
echo 'Cleared old backups'
mysqldump -ubestfightodds -pmUCe5haj bets > $backupdir/$backupfilename.sql
echo 'Dump complete'
zip -j -9 -D $gdrivedir/$backupfilename.zip $backupdir/$backupfilename.sql
echo 'Compression complete'
cd $gdrivedir/
$gdrivepath/drive push -no-prompt $backupfilename.zip
rm $backupfilename.zip