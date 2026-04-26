#!/bin/sh
cd `dirname "$0"`
export PATH=/bin:/usr/bin

OLDDATE=$(date -d "-5 days" "+%Y%m%d")
CURDATE=$(date +%Y%m%d)
TMPDIR=/atdata/dbbak


/usr/bin/mariadb-hotcopy -u backup -p {{ pwd.updates_mysql_backup }} --noindices --quiet toto $TMPDIR
/usr/bin/sleep 10
rm -f "toto-$OLDDATE.tar.zst"
ZSTD_CLEVEL=15 nice -n19 tar --zstd -cf "toto-$CURDATE.tar.zst" --directory=$TMPDIR toto
rm -rf $TMPDIR/toto/

/usr/bin/sleep 10
/usr/bin/mariadb-hotcopy -u backup -p {{ pwd.updates_mysql_backup }} --noindices --quiet toto_repl $TMPDIR
/usr/bin/sleep 10
rm -f "toto_repl-$OLDDATE.tar.zst"
ZSTD_CLEVEL=15 nice -n19 tar --zstd -cf "toto_repl-$CURDATE.tar.zst" --directory=$TMPDIR toto_repl
rm -rf $TMPDIR/toto_repl/

/usr/bin/sleep 10
/usr/bin/mariadb-hotcopy -u backup -p {{ pwd.updates_mysql_backup }} --noindices --quiet anidb $TMPDIR
/usr/bin/sleep 10
rm -f "anidb-$OLDDATE.tar.zst"
ZSTD_CLEVEL=15 nice -n19 tar --zstd -cf "anidb-$CURDATE.tar.zst" --directory=$TMPDIR anidb
rm -rf $TMPDIR/anidb/

/usr/bin/sleep 10
/usr/bin/mariadb-hotcopy -u backup -p {{ pwd.updates_mysql_backup }} --noindices --quiet arcscrape $TMPDIR
/usr/bin/sleep 10
rm -f "arcscrape-$OLDDATE.tar.zst"
ZSTD_CLEVEL=15 nice -n19 tar --zstd -cf "arcscrape-$CURDATE.tar.zst" --directory=$TMPDIR arcscrape
rm -rf $TMPDIR/arcscrape/



# upload backups
sftp -oPreferredAuthentications=publickey -oIdentityFile=bak.key totobak@{{ backup_host }} >/dev/null 2>&1 <<ENDSFTP
cd /db-backup
rm *-$OLDDATE.*
put *-$CURDATE.*
exit
ENDSFTP



####### For DB Exports #######

# export file links table
FROM_TIME=$(cat filelinks-timestamp.txt 2>/dev/null || date -d '-25 hour' +%s)
TO_TIME=$(date -d '-1 hour' +%s)

/usr/bin/sleep 10
/usr/bin/mysql --quick -B --net-buffer-length=4M -ubackup -p{{ pwd.updates_mysql_backup }} --init-command='SET SESSION slow_query_log=OFF, read_buffer_size=4194304' -e "SELECT id,fid AS file_id,site,part,url,added AS date_added,lastchecked AS date_updated FROM toto.toto_filelinks_active WHERE status=0 AND encrypted=0 AND lastchecked > $FROM_TIME AND lastchecked <= $TO_TIME ORDER BY lastchecked ASC" | /usr/bin/pv -L 256k -B 4m -q | nice -n15 /usr/bin/xz -6 > "$TMPDIR/filelinks-$CURDATE.txt.xz"

echo $TO_TIME > filelinks-timestamp.txt


/usr/bin/sleep 10
/usr/bin/mysql --quick -B --net-buffer-length=4M -ubackup -p{{ pwd.updates_mysql_backup }} --init-command='SET SESSION slow_query_log=OFF, read_buffer_size=4194304' -e 'SELECT id, tosho_id, nyaa_id, anidex_id, nekobt_id, name, link, magnet, cat, website, totalsize, dateline AS date_posted, comment, added_date AS date_added, completed_date AS date_completed, torrentname, torrentfiles, stored_nzb, stored_torrent, nyaa_class, nyaa_cat, IFNULL(anidex_cat, 0) AS anidex_cat, IFNULL(anidex_labels, 0) AS anidex_labels, nekobt_hide, IF(btih IS NULL, "", HEX(btih)) AS btih, IF(btih_sha256 IS NULL, "", HEX(btih_sha256)) AS btih_sha256, isdupe, deleted, lastchecked AS date_updated, aid, eid, fid, gids, resolveapproved, sigfid AS main_fileid, srcurl, srcurltype, srctitle, ulcomplete AS status FROM toto_repl.toto_toto' | /usr/bin/zstd -3 > $TMPDIR/torrents-new.txt.zst
/usr/bin/unzstd --rm -c $TMPDIR/torrents-new.txt.zst | nice -n15 /usr/bin/xz -6 > $TMPDIR/torrents-latest.txt.xz
/usr/bin/sleep 10
/usr/bin/php /root/dbbak/dbquery.php attachments | /usr/bin/zstd -3 > $TMPDIR/attachments-new.txt.zst
/usr/bin/unzstd --rm -c $TMPDIR/attachments-new.txt.zst | nice -n15 /usr/bin/xz -6 > $TMPDIR/attachments-latest.txt.xz
/usr/bin/sleep 10
/usr/bin/mysql --quick -B --net-buffer-length=4M -ubackup -p{{ pwd.updates_mysql_backup }} --init-command='SET SESSION slow_query_log=OFF, read_buffer_size=4194304' -e 'SELECT id, HEX(hash) AS sha1, filesize, packedsize FROM toto_repl.toto_attachment_files' | /usr/bin/zstd -3 > $TMPDIR/attachmentfiles-new.txt.zst
/usr/bin/unzstd --rm -c $TMPDIR/attachmentfiles-new.txt.zst | nice -n15 /usr/bin/xz -6 > $TMPDIR/attachmentfiles-latest.txt.xz
/usr/bin/sleep 10
/usr/bin/mysql --quick -B --net-buffer-length=4M -ubackup -p{{ pwd.updates_mysql_backup }} --init-command='SET SESSION slow_query_log=OFF, read_buffer_size=4194304' -e 'SELECT id, toto_id AS torrent_id, type AS is_archive, filename, filesize, vidframes, IF(crc32 IS NULL,"",HEX(crc32)) AS crc32, IF(md5 IS NULL,"",HEX(md5)) AS md5, IF(sha1 IS NULL,"",HEX(sha1)) AS sha1, IF(sha256 IS NULL,"",HEX(sha256)) AS sha256, IF(tth IS NULL,"",HEX(tth)) AS tth, IF(ed2k IS NULL,"",HEX(ed2k)) AS ed2k, IF(bt2 IS NULL,"",HEX(bt2)) AS bt2, IF(crc32k IS NULL,"",HEX(crc32k)) AS crc32k, IF(fe.torpc_sha1_16k IS NULL,"",HEX(fe.torpc_sha1_16k)) AS torpc_sha1_16k, IF(fe.torpc_sha1_32k IS NULL,"",HEX(fe.torpc_sha1_32k)) AS torpc_sha1_32k, IF(fe.torpc_sha1_64k IS NULL,"",HEX(fe.torpc_sha1_64k)) AS torpc_sha1_64k, IF(fe.torpc_sha1_128k IS NULL,"",HEX(fe.torpc_sha1_128k)) AS torpc_sha1_128k, IF(fe.torpc_sha1_256k IS NULL,"",HEX(fe.torpc_sha1_256k)) AS torpc_sha1_256k, IF(fe.torpc_sha1_512k IS NULL,"",HEX(fe.torpc_sha1_512k)) AS torpc_sha1_512k, IF(fe.torpc_sha1_1024k IS NULL,"",HEX(fe.torpc_sha1_1024k)) AS torpc_sha1_1024k, IF(fe.torpc_sha1_2048k IS NULL,"",HEX(fe.torpc_sha1_2048k)) AS torpc_sha1_2048k, IF(fe.torpc_sha1_4096k IS NULL,"",HEX(fe.torpc_sha1_4096k)) AS torpc_sha1_4096k, IF(fe.torpc_sha1_8192k IS NULL,"",HEX(fe.torpc_sha1_8192k)) AS torpc_sha1_8192k, IF(fe.torpc_sha1_16384k IS NULL,"",HEX(fe.torpc_sha1_16384k)) AS torpc_sha1_16384k FROM toto_repl.toto_files LEFT JOIN toto.toto_files_extra fe ON toto_files.id=fe.fid' | /usr/bin/zstd -3 > $TMPDIR/files-new.txt.zst
/usr/bin/unzstd --rm -c $TMPDIR/files-new.txt.zst | nice -n15 /usr/bin/xz -6 > $TMPDIR/files-latest.txt.xz

/bin/mv -f $TMPDIR/*-latest.txt.xz /storage/storage/dbexport/

/usr/bin/find /storage/storage/dbexport/ -type f -name "filelinks-*.txt.xz" -mtime +7 | /usr/bin/xargs -r /bin/rm -f
mv "$TMPDIR/filelinks-$CURDATE.txt.xz" /storage/storage/dbexport/


dir=`/usr/bin/dirname "$(readlink -f "$0")"`
cd /storage/storage/dbexport/
/usr/bin/php "$dir/dbexport.php" > index.html
