#!/bin/bash

backupdir="/var/www/bfo/bfo/db/scripts/backup/"
backupfilename="bfo2_backup_$(date '+%Y%m%d%H%M%S')"

echo 'Starting backup'
cd $backupdir/
mysqldump -ubestfightodds -pmUCe5haj --skip-tz-utc bets > $backupdir/$backupfilename.sql
echo 'Dump complete'
zip -j -9 -D $backupdir/$backupfilename.zip $backupdir/$backupfilename.sql
echo 'Compression complete'
rm $backupfilename.sql