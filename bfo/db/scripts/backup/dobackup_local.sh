#!/bin/bash

backupdir="/var/www/bfo/bfo/db/scripts/backup/"
backupfilename="bfo2_backup_$(date '+%Y%m%d%H%M%S')"

echo 'Starting backup'
cd $backupdir/
mysqldump -h ls-9bf1268dd5beed1d539ee8d4c86139bf072394e4.cc2cczpln4tj.us-east-2.rds.amazonaws.com -ubfo -peVhT03rCIYT7u3c bets > $backupdir/$backupfilename.sql
echo 'Dump complete'
zip -j -9 -D $backupdir/$backupfilename.zip $backupdir/$backupfilename.sql
echo 'Compression complete'
rm $backupfilename.sql