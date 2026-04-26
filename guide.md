# Anime Tosho Server Setup Guide

This page provides instructions on how to set up servers to run your own Anime Tosho instance. It is assumed that you have familiarity with Linux server administration.

Notes:

* This setup has only been designed for Debian 13 amd64
* None of AT’s servers currently run Debian 13, meaning that it’s possible unexpected issues arise due to running newer software
* This setup assumes you’re running on a fresh system install, and the Ansible playbooks will make system level changes

#### Missing AniDB Data and Functionality

Note that AniDB data will not be provided (beyond [the *cat* table](https://github.com/animetosho/animetosho-updater/blob/master/schema/anidb_data.sql)), and the update component ([TCP API Client](https://github.com/animetosho/anidb-tcp-client)) is left in a [non-functioning state](https://github.com/animetosho/anidb-tcp-client#-important-note---this-application-doesnt-work). If you want AniDB integration similar to Anime Tosho, you will need to develop your own way of pulling data from AniDB.

## Server Provisioning

Although AT was designed to run on four servers, one could consolidate these into fewer servers if desired, but some aspects should be considered:

- the updates server uses a lot of bandwidth and torrent activity can be I/O intensive. Having it as a separate server ensures website accessibility isn’t affected by back-end processing load
  - note that the updates server also locally stores a copy of the 'storage data' that the storage server serves
- the main website, being the face of Anime Tosho, receives most of the complaints, so having it hosted on a provider who isn’t so strict about such is beneficial. This is less of a concern with other servers
- the storage web server needs sufficient storage space, but doesn’t use much CPU/RAM/IO. The feed server doesn’t need much storage, but is more CPU bound. Having them on the same server means they can use resources not consumed by the other, but if you want them on separate servers, you can provision them appropriately for their respective needs

The playbooks here are designed to operate with a minimum of two servers (updates and web/storage/feed server), up to four overall.

On the other hand, if you wish to run on *more* servers (i.e. scale out), note that AT wasn’t designed for such. Scaling out the storage and feed servers is trivial however, since they’re effectively read-only replicas - simply run the appropriate server setup on multiple servers and add them all to the DNS.
(note that all storage server nodes would need the full dataset - sharding data across storage nodes is not implemented)

### Updates Server

Upload bandwidth is likely the key bottleneck for the updates server, as it needs to upload every processed torrent several times. I recommend a server with no upload quota.
There also needs to be sufficient storage space to temporarily hold torrents, and you’ll want plenty of leeway to cater for issues arising (such as DDL upload failures, slow torrents etc). Also, this server holds a full copy of the storage data (what the storage server serves), so you’ll need disk space for that.
Disk I/O can also be a concern if you choose a server with hard disks due to concurrently processing several tasks and torrents’ random disk I/O.

Updates processing doesn’t need much CPU (a desktop/server class processor from 2009 is more than sufficient) or RAM, though the latter can be useful for reducing disk I/O (and scripts tend to use large buffers to mitigate random I/O).

I’d recommend a dedicated/bare metal server, though a VDS setup might work. The script may push the server quite hard, and doesn’t consider ‘fair share’ resource policies that many VPS providers impose.

Overall recommendations:

- CPU: four core Silvermont Atom (e.g. Atom C2550), Core 2 Quad era or better
- RAM: 8GB, ideally 16GB or more
- Disk: 3TB space. If using hard disks, I recommend a minimum of two disks for RAID0
- Bandwidth: unlimited upload, 300Mbps minimum, ideally more

Anime Tosho has been using the following configuration for the last five or so years:

- CPU: Xeon L3426
- RAM: 16GB
- Disk: 2x 2TB, mixed RAID modes (see partitioning below)
- Bandwidth: 300Mbps

### Primary Web Server

The main concern of primary web server would be a host that is more amenable to dealing with complaints. Otherwise the requirements are fairly modest.

The server runs a typical PHP+nginx+MariaDB stack, with Manticoresearch for search.

Recommendations:

- CPU: 1 vCore, ideally not some 2013-era Atom core
- RAM: 1GB if disk is SSD, 4+GB if HDD
- Disk: 20GB
- Bandwidth: depends on traffic you get. 1TB/month is sufficient for Anime Tosho’s primary webserver

### Storage Server

The main concerns here would be sufficient space to hold files, and bandwidth to serve them. CPU/RAM requirements are fairly modest, though note that screenshots are dynamically rendered, which can cause CPU spikes if you don't throttle scraper bots.

As AT doesn’t delete files, storage data does grow over time, so you’ll want headroom to handle that.

Note that the PNG encoder for rendering screenshots [requires SSE4.1 support](https://github.com/animetosho/vs-encodeframe#requirements) (Intel 45nm Core2, AMD Bulldozer or later CPU, noting that if running on a VPS, the hypervisor may mask CPU features).

Recommendations:

- CPU: 2 vCores, ideally AVX2 supported (Intel Haswell, AMD Zen 1 or better), though you can probably get by with a first gen Intel Core
- RAM: 1GB
- Disk: 1.5TB, hard disks okay
- Bandwidth: 5TB/month is sufficient for AT’s needs, assuming you‘re vigilant with blocking abusive bots

Note that database backups from the updates and primary web server are sent to this server.

### Feed Server

The feed server runs essentially the same configuration as the primary web server, with the benefit that it’s much less of a target for complaints. As such, you can use the provisioning suggestion for the primary web server.

Anime Tosho gets a decent amount of API hits, many of which go to the search server, resulting in high-ish CPU usage.
Having enough RAM to hold most of the DB's core structures (e.g. indexes) may be beneficial.

Recommendations for Anime Tosho’s level of workload:

- CPU: 4 vCores (ideally Xeon E3v3 or AMD Zen or better)
- RAM: 4GB if disk is SSD, 6GB if HDD
- Disk: 20GB
- Bandwidth: 8TB/month is sufficient for Anime Tosho’s needs

For less traffic, it should easily scale down to 1 vCore and 1GB RAM.

## Disk Partitioning & OS Installation

I often install Debian stable manually via debootstrap (often through a recovery console). This allows for custom RAID/partitioning schemes, filesystem customisation and full disk encryption (on root/swap partitions). Not all hosts provide this capability though, or as none of this is required, you may decide to not do any of this. Nevertheless, the following are the configurations I tend to use.

### Updates Server

I typically partition the disk(s) as follows:

- /atdata: 1.5TB (total) or more recommended, RAID0 if available
- /: 70GB, RAID1 if available, LUKS encryption
- /storage: 1.5TB-2TB (total), RAID0 (match this to the storage server’s disk size)
- swap: 1-2GB (total), RAID0, LUKS
- /boot: 500MB, RAID1

Note that if you're running on harddisks on a bare metal server, I recommend placing the /atdata partition first.

After partitioning, I often use the following commands for a two disk setup (you'll likely need to tweak these):

```bash
# /atdata
mdadm --create --verbose /dev/md0 --level=stripe --chunk=2048 --raid-devices=2 /dev/sda1 /dev/sdb1
mkfs.ext4 -T largefile4 -m 1 -O ^has_journal,^resize_inode,^huge_file -v /dev/md0
tune2fs -o ^user_xattr,^acl /dev/md0

# (root)
mdadm --create --verbose /dev/md1 --level=mirror --raid-devices=2 /dev/sda2 /dev/sdb2
cryptsetup --cipher aes-xts-plain64 --hash sha512 -s 256 --iter-time 2000 luksFormat /dev/md1
cryptsetup luksOpen /dev/md1 root
mkfs.ext4 -v /dev/mapper/root
tune2fs -i 6m -e remount-ro -c 50 /dev/mapper/root

# (swap)
mdadm --create --verbose /dev/md2 --level=stripe --raid-devices=2 /dev/sda3 /dev/sdb3
cryptsetup --cipher aes-xts-plain64 --hash sha512 -s 256 --iter-time 2000 luksFormat /dev/md2
cryptsetup luksOpen /dev/md2 swap
mkswap /dev/mapper/swap

# /storage
mdadm --create --verbose /dev/md3 --level=stripe --raid-devices=2 /dev/sda4 /dev/sdb4
mkfs.ext4 -T big -m 1 -v /dev/md3
tune2fs -o ^user_xattr,^acl /dev/md3

# /boot
mdadm --create --verbose /dev/md4 --level=mirror --metadata=0.90 --raid-devices=2 /dev/sda5 /dev/sdb5
mkfs.ext2 -b 4096 -m 1 -v /dev/md4
```

And set up the fstab to be something like:

```
UUID=030dd70e-5cd6-4881-a1ce-228a981f6901	/atdata			ext4	rw,nosuid,nodev,noatime,nodiratime,nobarrier,noblock_validity		0	2
UUID=e530d9b7-7d2f-4c34-a622-5104695d009b	/				ext4	defaults	0	1
UUID=ae8760d3-701a-469e-8894-d6b5cc620c21	none			swap	sw			0	2
UUID=345b56e2-3dd4-4e7f-a2e6-fe9f47c6ce42	/storage		ext4	rw,nosuid,nodev,noatime,nodiratime		0	2
UUID=a551da72-b168-4698-8118-00e65c428b94	/boot			ext2	defaults	0	1
```

I set up a Dropbear SSH server in initramfs to allow encrypted disks to be unlocked during boot.

### Web/Feed/Storage Servers

If running these on harddisks, I often like to create a small /cache partition, with journaling disabled. This is mostly to try and eek out some I/O performance (haven’t measured impact so it might be practically non-existent). I typically allocate 15GB on the storage server, whilst the web/feed servers can likely get by with ~2GB for the /cache partition.

Like the updates server, I encrypt the root/swap partitions to reduce the likelihood of data exposure, should the server unexpectedly die.

## Pre Setup

You must generate your own keys and passwords that’ll be used before running the Ansible playbooks. A script *make-keys.sh* is provided to generate these for you. You’ll likely want to save a copy of all these passwords/keys, which can be found in the created *keys* folder.

You should also check *vars.yml* and update values appropriately.

Ansible requires Python to be installed on target nodes, so you may need to manually do that if it wasn’t included in the base Debian install.

The playbooks log in as root, so you’ll either need to allow root logins over SSH or modify the playbooks to use elevation.

## Main Setup

After disk partitioning and OS installation, Ansible playbooks perform most of the setup.

Note that these playbooks don’t use Ansible’s inventory management - you’ll need to explicitly run them on each target server.

You’ll first want to run **main.yml** on all the servers, which sets up a shared base configuration. This can be done from a system with Ansible installed via the `ansible-playbook -i <target_host>, -k main.yml` command.

After that, run the following playbooks in listed order for the specified servers.

**Updates Server**

- updates.yml

**Primary Web Server**

- main-web.yml
- web.yml

**Storage Server**

- main-web.yml
- storage.yml
- storage-noweb.yml
  - this disables some functionality if you’re not running web/feed functionality on this server; if you are, don’t run this playbook
- backup.yml
  - or run this on another server you want database backups to be sent

**Feed Server**

- main-web.yml
- web.yml
- web-feed.yml
  - this only applies some configuration tweaks, and thus is optional

Note that the web and feed servers are set up almost the same, so you can just run them as one server (avoid running the *web-feed* playbook). You can add storage server functionality to a web/feed server by also running the *storage* playbook.

## Post Setup

The Ansible playbooks set up necessary applications, configuration and code, but there’s several things you’ll need to do afterwards:

- configuration tweaks (optional)
  - e.g. adjusting MariaDB’s `aria_pagecache_buffer_size` based on RAM on the web servers
- set up Tor hidden service on web servers
- install or set up TLS certificates for web servers via LetsEncrypt
  (the playbooks set up a self-signed cert, which should be replaced)
- [add Usenet upload host details](https://github.com/animetosho/animetosho-updater#config) to updater script
  - [AniDB TCP API client](https://github.com/animetosho/anidb-tcp-client) also needs an AniDB account, but the client won’t work unless you have an API key
- add backup server SSH fingerprints for SFTP automation
- data import (database and storage)
- database replication and storage sync
- rebuild search index after database import
- enable cron jobs
- reboot to apply kernel tweaks and check that services start up correctly

The first four above are left as issues for the reader to figure out.

### Adding Backup Server SSH Fingerprint

The database backup scripts send database dumps to the backup server via SFTP. As SSH needs confirmation when first encountering a new key, you’ll need to add the SSH fingerprint for automation to work.

You can do this by running the following on updates and primary web server (replacing the host with the correct destination).

```
ssh-keyscan {{ backup_host }} >> /root/.ssh/known_hosts
```

(this is not done in the playbooks to allow servers to be set up in arbitrary order)

### Data Importation

The playbooks set up a ‘blank’ configuration. At this point, you may wish to import existing data if you have a database dump. Dumps I provide will be labelled with the database they’re meant to be loaded into. Note that since there’s database replication, you may need to import them onto multiple servers. Also note that not all slaves (web servers) have all databases, so you can skip loading dump files if the respective database is missing.
Note that the *anidb* database data will not be provided; the *anidb.cat* table is prepopulated by the playbooks, so if you do somehow get an *anidb* database dump, you’ll need to truncate that table first.

You should be able to import a compressed dump file to a database with a command like: `xzcat toto_repl.sql.xz | mariadb toto_repl`

Once you’ve imported databases onto the web/feed server, don’t forget to [run *schema/anito-rebuild_scripts.sql*](https://github.com/animetosho/animetosho-website/blob/master/schema/anito-rebuild_scripts.sql) on the *anito* database to rebuild index tables.

For storage data, load it into the `/storage/storage` folder on both the updates and storage servers.

#### Without a data import

If you wish to skip data importation and start new, you’ll need to set a [starting point for the updater script](https://github.com/animetosho/animetosho-updater#initial-state) so that it knows where to start scraping upstream sites from.

Note that you may want to do this after database replication is set up (next step), otherwise you have to ensure database consistency before enabling sync.

### Data Sync Setup

The updates server will need to sync data to the web servers. The playbooks configure the syncing but leave it in a disabled state.

#### Database Replication

If you’ve been following this guide, the databases should have the same data across all servers.
(note that AT doesn’t do full replication - some servers skip replicating databases they don’t need, and some may have tables set as BLACKHOLE (drop all data), so data only needs to match enough to ensure replication consistency)

If the data matches, set the master log file/position on slaves, and start replication.

```sql
-- on updates server - get value in File/Position columns
SHOW MASTER STATUS;

-- on web/feed/storage servers
STOP SLAVE;
CHANGE MASTER TO MASTER_LOG_FILE = "<File>", MASTER_LOG_POS = <Position>;
START SLAVE;
```

Once that’s done, turn on replication by running `systemctl enable spiped-mysql && systemctl start spiped-mysql` on all web servers. Check that it’s all working with a `SHOW SLAVE STATUS` query on the web servers.

#### Storage Data Sync

Make sure that the updates server’s */storage* folder matches to that on the storage server - if you’re importing data, you can just import them onto both servers, otherwise if you’re starting fresh, you can use the following command on the updates server to sync its contents to the storage server:

```
rsync -rlHtv --delete --password-file=/etc/lsyncd/lsync.pwd --progress /storage/storage/ rsync://lsync@127.0.0.1:1873/storage/
```

Note that lsync is configured to skip rsync on startup (because it can take a long time), which means it’s on you to ensure the directories are synced before starting lsync (including if the service crashes or updates server reboots).
Once you’re happy, enable and start the lsync service on the updates server to turn on storage data syncing (`systemctl enable lsyncd && systemctl start lsyncd` command).

### Search Index Rebuild

Manticoresearch is used on the web/feed servers to handle search queries. If data has been imported, you’ll want to build the index so that searches work. If data hasn’t been imported, you may still need to build an initial index.
Note that index rebuilds are configured to be throttled to reduce load on the server, so for the initial build, you may wish to disable throttling (see `sql_ranged_throttle` in */etc/manticoresearch/manticore.conf*).
To do a rebuild, use a `runuser -u manticore -- indexer --all` command.

You may need to restart searchd after the initial index build.

### Cron Jobs

Cron jobs are installed, but commented out. It is recommended that you ensure functionality works as expected first - I usually manually run the commands (as the specified account to avoid permission issues) and observe output/logs to see if there’s any issues. Once you're happy with it, you can enable the cron tasks by uncommenting out entries.

Uncomment entries in the following files in */etc/cron.d/* to enable cron jobs:

**Updates Server**

* **totocron**: fires off core processing functions
* **dbbak**: daily database backup

**Web/Feed Server**

* **manticore**: rebuild the search index
* **dbbak**: daily database backup. Note: don't enable this on the feed server as it's a read-only replica which doesn't need backing up

## Maintenance

The updates script/setup in particular [needs ongoing maintenance](https://github.com/animetosho/animetosho-updater#maintenance).
