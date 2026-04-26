#!/bin/bash

/usr/bin/renice -n 19 $$ >/dev/null
mkdir /run/manticore 2>/dev/null

(
  /usr/bin/flock -x -n 173 || exit 0

  # since this script is run on similar intervals to update crons, add a delay to allow changes to replicate and be included in the index
  sleep 30

  # remember timestamps of index last built
  (cd /cache/manticore; /usr/bin/touch idx_main_delta)

  /usr/bin/unbuffer /usr/bin/indexer --rotate --verbose --print-queries idx_main_delta | /usr/bin/awk '{ print strftime("[%F %H:%M:%S]"), $0; fflush(); }' >> /var/log/manticore/indexer-delta.log

) 173>/run/manticore/indexer-delta.lock
