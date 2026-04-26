#!/bin/sh

mkdir keys

# spiped private keys
head -c32 /dev/random >keys/spipe-mysql.key
head -c32 /dev/random >keys/spipe-nginx.key
head -c32 /dev/random >keys/spipe-rsyncd.key

# dhparams for HTTPS
openssl dhparam -out keys/dhparams 2048

# backup server SSH key
#openssl genrsa -out misc/backup.pem 2048
ssh-keygen -t rsa-sha2-256 -b 2048 -N '' -f keys/backup.pem
cat keys/backup.pem | ssh-keygen -f /dev/stdin -y >keys/backup-ssh-pub

# passwords
echo "pwd:" >keys/passwords.yml
echo "  updates_mysql_repl: $(openssl rand -base64 24)" >>keys/passwords.yml
echo "  updates_mysql_toto: $(openssl rand -base64 24)" >>keys/passwords.yml
echo "  updates_mysql_adbtcp: $(openssl rand -base64 24)" >>keys/passwords.yml
echo "  updates_mysql_backup: $(openssl rand -base64 24)" >>keys/passwords.yml
echo "  updates_transmission: $(openssl rand -base64 24)" >>keys/passwords.yml
echo "  web_mysql_sphinx: $(openssl rand -base64 24)" >>keys/passwords.yml
echo "  web_mysql_web: $(openssl rand -base64 24)" >>keys/passwords.yml
echo "  web_mysql_xzserv: $(openssl rand -base64 24)" >>keys/passwords.yml
echo "  web_mysql_cachesrv: $(openssl rand -base64 24)" >>keys/passwords.yml
echo "  web_mysql_backup: $(openssl rand -base64 24)" >>keys/passwords.yml
echo "  storage_rsync: $(openssl rand -base64 24)" >>keys/passwords.yml
