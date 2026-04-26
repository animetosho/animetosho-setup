#!/bin/sh
cd `dirname "$0"`
export PATH=/bin:/usr/bin

OLDDATE=$(date -d "-7 days" "+%Y%m%d")
CURDATE=$(date +%Y%m%d)

mysqldump -ubackup -p{{ pwd.web_mysql_backup }} --hex-blob -Q --skip-log-queries --ignore-table=anito.toto_captcha --ignore-table=anito.toto_http_posts --ignore-table=anito.toto_parsecache --ignore-table=anito.toto_sessions --ignore-table=anito.toto_anime_latest --ignore-table=anito.toto_aniep_latest --ignore-table=anito.toto_ep_latest --ignore-table=anito.toto_comments_all anito | zstd -12 -T1 >"anito-$CURDATE.sql.zst"

mysqldump -ubackup -p{{ pwd.web_mysql_backup }} -Q --skip-log-queries --no-data anito | zstd -12 -T1 >"anitoSchema-$CURDATE.sql.zst"

# clear old files
rm -f *-$OLDDATE.sql.*

# upload backups
sftp -oPreferredAuthentications=publickey -oIdentityFile=bak.key totobak@{{ backup_host }} >/dev/null 2>&1 <<ENDSFTP
cd /db-backup
rm *-$OLDDATE.sql.*
put *-$CURDATE.sql.*
exit
ENDSFTP
