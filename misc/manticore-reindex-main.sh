#!/bin/bash

/usr/bin/renice -n 19 $$ >/dev/null
mkdir /run/manticore 2>/dev/null

(
  /usr/bin/flock -x -n 174 || exit 0

  # remember timestamps of index last built
  (cd /cache/manticore; /usr/bin/touch idx_main)

  /usr/bin/unbuffer /usr/bin/indexer --rotate --nohup --verbose --print-queries idx_main | /usr/bin/awk '{ print strftime("[%F %H:%M:%S]"), $0; fflush(); }' >> /var/log/manticore/indexer-main.log
  
  # rename .tmp -> .new
  /usr/bin/find /cache/manticore/data/ -name 'idx_main.tmp.*' | /usr/bin/sed -E 's/^(.+)\.tmp\.([a-z0-9]+)/\0 \1\.new\.\2/' | /usr/bin/xargs -n2 /usr/bin/mv

) 174>/run/manticore/indexer-main.lock
