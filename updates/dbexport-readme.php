<html>
<head>
<title>AnimeTosho Database Exports</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php $now = time(); ?>
<meta http-equiv="Expires" content="<?=gmdate('D, d M Y H:i:s', $now+23*3600)?>" />
<meta http-equiv="Last-Modified" content="<?=gmdate('D, d M Y H:i:s', $now)?>" />
<meta http-equiv="Cache-Control" content="private" />
</head>
<body>

<p>Exports of <a href="https://animetosho.org/">AnimeTosho</a>'s database are dumped here daily, and can be downloaded and used for whatever purpose without restriction.</p>

<p>Notes: <ul>
<li>these data sets are provided <b>without any support</b> beyond the brief explanation offerred below</li>
<li>data structures may change without notice</li>
<li>this document may not get updated to reflect possible changes</li>
<li>the AT script has evolved over time, so older entries may lack data, or have different data to newer entries</li>
<li>due to bugs etc, some errors are to be expected</li>
<li>if it looks like dumps are broken or aren't being updated, please <a href="https://animetosho.org/feedback">report the issue here</a></li>
</ul></p>

<h2>Download</h2>

<p>Files updated: <?=date('Y-m-d')?>
<ul>
<li><a href="torrents-latest.txt.xz" rel="nofollow">Torrents table</a> (<?=round(@filesize('torrents-latest.txt.xz')/1048576, 2)?> MB)</li>
<li><a href="files-latest.txt.xz" rel="nofollow">Files table</a> (<?=round(@filesize('files-latest.txt.xz')/1048576, 2)?> MB)</li>
<li><a href="attachments-latest.txt.xz" rel="nofollow">Attachments table</a> (<?=round(@filesize('attachments-latest.txt.xz')/1048576, 2)?> MB)</li>
<li><a href="attachmentfiles-latest.txt.xz" rel="nofollow">Attachment Files table</a> (<?=round(@filesize('attachmentfiles-latest.txt.xz')/1048576, 2)?> MB)</li>
</ul>
</p>

<p>Available File Links snapshots:
<ul>
<?php
foreach(glob('filelinks-????????.txt.xz') as $file) {
	$fn = basename($file);
	$date = preg_replace('~^.*-(\d{4})(\d{2})(\d{2})\..*$~', '$1-$2-$3', $fn);
	echo '<li><a href="'.$fn.'" rel="nofollow">'.$date.'</a> ('.round(@filesize($file)/1024, 2).' KB)</li>';
}
?>
</ul>
</p>

<h2>Format</h2>

<p>Data is in the form of text files, with fields separated by tabs and each line representing a record/row. Data is encoded in UTF-8, with Unix line endings, and tab, newline, null and backslash characters escaped using C-style escapes (<code>\t</code>, <code>\n</code>, <code>\0</code> and <code>\\</code> respectively).
<br />
The first row always contains the column headers - you can use these to sort data appropriately, to deal with structural changes.
<br />
Data is actually exported from a MySQL database, and can be imported into MySQL using a <a href="https://dev.mysql.com/doc/refman/5.7/en/load-data.html">LOAD DATA query</a>. You can also load it into Microsoft Excel, and although I'd like to joke about trying to load sizable data sets into Excel, it actually works...</p>

<p>The following describes the tables and fields.</p>

<h2>Torrents Table</h2>
<p>This table contains all torrent entries. For example, the display on the homepage simply pulls latest items from this table.</p>

<h3>Fields:</h3>
<ul>
<li><b>id</b>: unique identifier</li>
<li><b>tosho_id</b>: ID from TokyoTosho; 0 if none available</li>
<li><b>nyaa_id</b>: ID from Nyaa; 0 if none available</li>
<li><b>anidex_id</b>: ID from AniDex; 0 if none available</li>
<li><b>nekobt_id</b>: ID from nekoBT; 0 if none available</li>
<li><b>name</b>: name/title</li>
<li><b>link</b>: original HTTP torrent download link</li>
<li><b>magnet</b>: magnet link of torrent, either obtained from source or generated from torrent file</li>
<li><b>cat</b>: TokyoTosho category</li>
<li><b>website</b>: URL for website</li>
<li><b>totalsize</b>: total size of all files in torrent, in bytes</li>
<li><b>date_posted</b>: Unix timestamp of when torrent was uploaded</li>
<li><b>comment</b>: </li>
<li><b>date_added</b>: Unix timestamp of when torrent was grabbed by AT scripts</li>
<li><b>date_completed</b>: Unix timestamp of when torrent download was completed</li>
<li><b>torrentname</b>: Name extracted from torrent file</li>
<li><b>torrentfiles</b>: Number of files found in the torrent</li>
<li><b>stored_nzb</b>: Whether AT has an NZB stored with this entry. If available, the NZB can be downloaded from <i>https://storage.animetosho.org/nzbs/<font color="red">xxxxxxxx</font>/file.nzb</i> replacing <font color="red">xxxxxxxx</font> with the 8 character hex encoded ID (see <i>id</i> column, convert the number to hexadecimal representation and left pad with zeroes)</li>
<li><b>stored_torrent</b>: Whether AT has a .torrent stored with this entry. If available, the torrent can be downloaded from <i>https://storage.animetosho.org/torrent/<font color="red">hex-btih</font>/torrent.torrent</i> replacing <font color="red">hex-btih</font> with the 40 character lower-case hex encoded info hash (see <i>btih</i> column)</li>
<li><b>nyaa_class</b>: Nyaa's classification: 0=unknown, 1=remake, 2=none, 3=trusted, 4=a+, -1=hidden</li>
<li><b>nyaa_cat</b>: Nyaa's category</li>
<li><b>anidex_cat</b>: AniDex's category</li></li>
<li><b>anidex_labels</b>: labels from AniDex as bit flags: 1=batch, 2=raw, 4=hentai, 8=reencode</li>
<li><b>nekobt_hide</b>: 1 if hidden/deleted on nekoBT, 0 otherwise</li>
<li><b>btih</b>: hex encoded torrent info hash</li>
<li><b>btih_sha256</b>: hex encoded BitTorrent v2 info hash, if file is a BTv2 or hybrid torrent file</li>
<li><b>isdupe</b>: whether this entry is considered a duplicate, based on BTIH</li>
<li><b>deleted</b>: whether this entry is marked as deleted</li>
<li><b>date_updated</b>: Unix timestamp of when this row was last updated</li>
<li><b>aid</b>: related AniDB anime ID</li>
<li><b>eid</b>: related AniDB episode ID</li>
<li><b>fid</b>: related AniDB file ID</li>
<li><b>gids</b>: related AniDB group IDs, comma separated list</li>
<li><b>resolveapproved</b>: whether exclamation mark shows up next to the anime title in the view page</li>
<li><b>main_fileid</b>: if the torrent contains one file of significance, will be the AT file ID, otherwise 0. If this is set, links from this file are displayed on the home page</li>
<li><b>srcurl</b>: source article URL</li>
<li><b>srcurltype</b>: source article type</li>
<li><b>srctitle</b>: source article title</li>
<li><b>status</b>: torrent status: 0=downloading, 1=downloaded, -1=skipped, -2=broken, -3=other error</li>
</ul>

<h2>Files Table</h2>
<p>This table contains all file entries. Torrents contain one or more files.</p>

<h3>Fields:</h3>
<ul>
<li><b>id</b>: unique identifier</li>
<li><b>torrent_id</b>: ID of associated torrent entry</li>
<li><b>is_archive</b>: 1 if this file is an 7z archive created by the Anime Tosho script, 0 otherwise</li>
<li><b>filename</b>: file's name; includes path if supplied</li>
<li><b>filesize</b>: file's size in bytes</li>
<li><b>vidframes</b>: non-empty if video I-frames are being stored for screenshot purposes. Is a comma separated list of integers which are timestamps at which the frame occurs in the video (miliseconds elapsed since start of video). Stored I-frames can be downloaded from <i>https://storage.animetosho.org/sframes/<font color="red">xxxxxxxx</font>_<font color="green">time</font>.mkv</i> where <font color="red">xxxxxxxx</font> is the file's ID, hex encoded and left padded with zeroes to 8 characters, and <font color="green">time</font> is the timestamp of the frame. If soft subtitles have been rendered for the frame, they can be downloaded from <i>https://storage.animetosho.org/sframes/<font color="red">xxxxxxxx</font>_<font color="blue">track</font>_<font color="green">time</font>.webp</i> where <font color="blue">track</font> is the track number of the subtitle (from the original video, usually is track 3)</li>
<li><b>crc32</b>: CRC32 hash of file, hex encoded, if available</li>
<li><b>md5</b>: MD5 hash of file, hex encoded, if available</li>
<li><b>sha1</b>: SHA1 hash of file, hex encoded, if available</li>
<li><b>sha256</b>: SHA256 hash of file, hex encoded, if available</li>
<li><b>tth</b>: TTH hash of file, hex encoded, if available</li>
<li><b>ed2k</b>: ED2K hash of file, hex encoded, if available</li>
<li><b>bt2</b>: <a href="http://bittorrent.org/beps/bep_0052.html">BitTorrent v2 (2017-08-31) root hash</a> of file, hex encoded, if available</li>
<li><b>crc32k</b>: CRC32 hash of first 1KB of file, hex encoded, if available</li>
<li><b>torpc_sha1_*</b>: hex encoded SHA1 hash of concatenated SHA1 hashes (binary encoded) of the respective block size. For example, the <i>torpc_sha1_16k</i> hash is obtained by breaking the file into 16KB blocks (if the last block is less than 16KB, it is discarded), calculating a 20 byte SHA1 hash for each block, concatenating these hashes, which is then fed through SHA1 to obtain the final hash. The selected block sizes correspond with the most common piece sizes used for torrents, and hence this hash can be useful in trying to detect duplicate torrents which have different info hash values.</li>
</ul>

<p>Mediainfo and related data are currently not included mainly due to size and time it takes to dump the data. The data is also compressed using a custom LZMA based scheme, which users would need to implement a decompressor for. I may consider including this data if many are interested in such.</p>

<h2>Attachments Table</h2>
<p>This table contains all attachment (subtitles, fonts etc) entries. File entries are mapped 1:1 to attachment entries (if attachments exist). Note that attachments are de-duplicated, and hence, there's a separate Attachment Files table (below) which describes unique attachment files.</p>

<h3>Fields:</h3>
<ul>
<li><b>file_id</b>: ID of associated file entry</li>
<li><b>attachments</b>: JSON encoded array describing available attachments</li>
</ul>

<h3>JSON Array Structure</h3>
<p>The array houses up to four entries, in the order listed below. The first two are an array of objects, describing each file, whilst the last two are integers. A <code>null</code> is used to indicate that a particular entry is missing (e.g. if there's only subtitles, the first entry will be <code>null</code> whilst the second will be an array).</p>
<ul>
<li>Array of file attachments (e.g. fonts)
	<ul>
	<li><b>_afid</b>: attachment file ID</li>
	<li><b>name</b>: the file name of the attachment</li>
	<li><b>mime</b>: the MIME type of the attachment</li>
	</ul>
</li>
<li>Array of subtitles
	<ul>
	<li><b>_afid</b>: attachment file ID</li>
	<li><b>lang</b>: subtitle language</li>
	<li><b>codec</b>: subtitle format (e.g. SRT, ASS)</li>
	<li><b>tracknum</b>: track number the subtitle occupied in the source file</li>
	</ul>
</li>
<li>Chapters XML file (attachment file ID)</li>
<li>Tags XML file (attachment file ID)</li>
</ul>

<h2>Attachment Files Table</h2>
<p>This table contains information about attachment files stored on disk. A file on disk can be linked to multiple attachments (due to de-duplication).</p>

<h3>Fields:</h3>
<ul>
<li><b>id</b>: unique identifier; files can be downloaded by converting this ID to a hex representation, left-padding with 0's to make it 8 hex characters long, and visiting the URL <i>https://storage.animetosho.org/attach/<font color="red">xxxxxxxx</font>/file.xz</i>, replacing <font color="red">xxxxxxxx</font> with the 8 character hex representation of the ID, left padded with zeroes</li>
<li><b>sha1</b>: hex encoded SHA1 hash of the file</li>
<li><b>filesize</b>: size of file</li>
<li><b>packedsize</b>: size of file, after XZ compression</li>
</ul>

<h2>File Links Exports</h2>
<p>These contain the download links generated for files.
<br/>The data is an export of links generated/updated since the last export, performed daily. Only a few days' worth of snapshots are retained, and the full table is not available due to size.</p>

<h3>Fields:</h3>
<ul>
<li><b>id</b>: unique identifier</li>
<li><b>file_id</b>: ID of associated file entry</li>
<li><b>site</b>: displayed site name for the link. Sub-links are indicated as <i>Parent|Child</i></li>
<li><b>part</b>: 1-based part number; if file wasn't split, will be 1</li>
<li><b>url</b>: the link URL</li>
<li><b>date_added</b>: Unix timestamp of when this entry was added</li>
<li><b>date_updated</b>: Unix timestamp of when this entry was last updated</li>
</ul>

<h2>Other Tables</h2>

<p>The other tables used by Anime Tosho probably won't be supplied for the following reasons:</p>

<ul>
<li><b>Tracker Scrape Tables</b>: contains seeder/leecher stats scraped from torrent trackers; this information can usually be scraped easily</li>
<li><b>AniDB Info Tables</b>: contains all data retrieved from AniDB, such as series information; please see <a href="http://wiki.anidb.net/w/API">AniDB API</a> for obtaining data</li>
<li><b>AniDB - TVDB Mapping Table</b>: used to map AniDB references to TVDB/IMDB. Original mapping data <a href="https://github.com/fuzeman/anime-lists">'anime-lists' can be found here</a></li>
<li><b>Source Scrape Tables</b>: contains all data scraped from upstream sources (TokyoTosho, Nyaa, AniDex). Please see sources for data dumps if desired</li>
</ul>

<h2>Code</h2>

<p>All open sourced code can be found <a href="https://github.com/animetosho?tab=repositories">on this GitHub page</a>.</p>

</body>
</html>
