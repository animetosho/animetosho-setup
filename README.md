Anime Tosho Setup Scripts
====================

This repo contains Ansible playbooks, with supporting files, to set up Anime Tosho servers, plus [a guide](guide.md) to walk through the process and required manual steps.

**Note**: whilst these setup scripts+guide can allow you to run your own version of AT, AT ultimately wasn't designed to be straightforward to setup/run/manage. Expect there to be hiccups along the way, and a lot of things to be rather unpolished.
AT was only designed to be run by myself - someone who understands how it all works. This means that you'll likely need to dig through unfamiliar, sometimes hacky code to apply changes/fixes, without any documentation or assistance. It is recommended that you *at least* be reasonably familiar with Debian+MariaDB server administration and some familiarity with programming in PHP, and ideally comfortable dealing with large, old, undocumented codebases unassisted.

## High level setup

Anime Tosho is designed to run across four servers:

* updates server: handles torrent processing, including uploads; no public web interface
* primary web server: serves the animetosho.org web front-end, plus cache.animetosho.org application
* storage server: serves storage.animetosho.org, which holds attachments, screenshots etc
* feed web server: serves feed.animetosho.org, covering RSS feeds and API

Currently AT runs on three servers, where a ‘secondary webserver’ hosts both storage and feed functions. 

The updates server performs all processing, and updates the other servers (aka webservers) via MariaDB database replication. The updates server also syncs files to the storage server via lsync. The primary web server can connect to the updates server to serve a small admin UI.

All servers run Debian Linux (stable) amd64.

## Setup Guide

See [here](guide.md)

## List of Repositories

The following is a list of all custom Anime Tosho projects relevant to running the operation:

**Updates Server**

* [Core Processing Scripts](https://github.com/animetosho/animetosho-updater): coordinates and performs most updates tasks
* [Nyuu](https://github.com/animetosho/nyuu): Usenet uploader
  * [ParPar](https://github.com/animetosho/parpar): generates PAR2 files for Usenet
* [DDL Uploader PHP Extension](https://github.com/animetosho/uploader-php-ext): augments uploading to DDL hosts
* [AniDB TCP Client](https://github.com/animetosho/anidb-tcp-client): synchronises a local copy of the AniDB database
  * [AniDB HTTP Client](https://github.com/animetosho/anidb-http-client): synchronises anime resource links from AniDB (not covered by TCP Client)
* [Is Image Transparent?](https://github.com/animetosho/is_image_transparent): used when rendering subtitles to images to delete rendered subtitles that result in completely transparent images

**Web/Feed Server**

* [Website](https://github.com/animetosho/animetosho-website): script which runs *animetosho.org* and *feed.animetosho.org*
* [Cache Website](https://github.com/animetosho/cache.animetosho.org): application running *cache.animetosho.org*

**Storage Server**

* [Frame Server](https://github.com/animetosho/frame-server): renders a video I-Frame to an image - used for displaying screenshots
  * [bestsource fork](https://github.com/animetosho/bestsource): a fork of the [VapourSynth bestsource plugin](https://github.com/vapoursynth/bestsource) which better suits our screenshot rendering needs
  * [EncodeFrame](https://github.com/animetosho/vs-encodeframe): VapourSynth plugin which encodes a video frame to an image
* [xz Package Server](https://github.com/animetosho/xz-server): generates 7z attachment packages from multiple xz-compressed attachment files

