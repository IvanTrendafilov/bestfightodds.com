#!/bin/bash

backupdir="/var/www/vhosts/bestfightodds.com/httpdocs/db/scripts/backup/"
backupfilename="bfo2_backup_$(date '+%Y%m%d%H%M%S')"
hostname="213.89.180.107"
username="admin"
password="Bilbo123"
remotedir="/myshare/bfo/"

cd $backupdir/
rm $backupdir/bfo2_backup_*.sql
mysqldump -ubestfightodds -pmUCe5haj bets > $backupdir/$backupfilename.sql
zip -9 -D $backupdir/$backupfilename.zip $backupfilename.sql

ftp -in $hostname <<EOF
quote USER $username
quote PASS $password
passive
cd $remotedir
binary
put $backupdir/$backupfilename.zip $(basename "$backupdir/$backupfilename.zip")
quit
EOF

rm $backupdir/$backupfilename.zip
