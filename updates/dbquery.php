<?php

if($argc < 2) die("No query specified\n");

define('ROOT_DIR', '/var/atscript/');

if(!($db = mysqli_init())) die("mysqli_init failed\n");
foreach([
	MYSQLI_INIT_COMMAND => 'SET SESSION slow_query_log=OFF, read_buffer_size=4194304',
	MYSQLI_OPT_NET_READ_BUFFER_SIZE => 4*1048576
] as $opt => $val) {
	if(!$db->options($opt, $val)) die("mysqli options failed\n");
}

if(!$db->real_connect('localhost', 'backup', '{{ pwd.updates_mysql_backup }}', 'toto_repl', null, '/var/run/mysqld/mysqld.sock'))
	die("Couldn't connect to DB\n");

$db->set_charset('utf8mb4');


function get_echo_fields(&$query) {
	$fields = $query->fetch_fields();
	echo implode("\t", array_map(function($field) {
		return $field->name;
	}, $fields)), "\n";
	return $fields;
}
function hex_binary_fields(&$row, $fields) {
	foreach($fields as $fk => $fv) {
		if($fv->flags & (MYSQLI_BLOB_FLAG | MYSQLI_BINARY_FLAG))
			$row[$fk] = bin2hex($row[$fk]);
	}
}

if($argv[1] == 'attachments') {
	require_once ROOT_DIR.'includes/finfo-compress.php';
	require_once ROOT_DIR.'includes/attach-info.php';
	
	if(!($query = $db->query('SELECT fid AS file_id, attachments FROM toto_repl.toto_attachments', MYSQLI_USE_RESULT)))
		die("Query failed\n");
	$fields = get_echo_fields($query);
	
	while($row = $query->fetch_array(MYSQLI_NUM)) {
		
		$attachments = FileInfoCompressor::decompress_unpack('attach', $row[1]);
		// unpack MIME
		foreach([ATTACHMENT_OTHER, ATTACHMENT_SUBTITLE] as $atype) {
			if(empty($attachments[$atype])) continue;
			foreach($attachments[$atype] as &$attach)
				$attach = unpack_attachment_info($atype, $attach);
		}
		
		$row[1] = json_encode($attachments, JSON_UNESCAPED_SLASHES);
		
		echo implode("\t", $row), "\n";
	}
	
	$query->free_result();
}


$db->close();
