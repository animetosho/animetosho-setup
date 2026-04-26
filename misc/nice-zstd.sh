#!/bin/sh
exec /usr/bin/nice -n 10 /usr/bin/zstd "$@"
