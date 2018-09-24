<?php

function expiration_duration($duration)
{
	$total = $duration;
	$days = floor($total / 86400);
	$total -= $days * 86400;
	$hours = floor($total / 3600);
	$total -= $hours * 3600;
	$minutes = floor($total / 60);
	$seconds = $total - $minutes * 60;
	$times = array($days, $hours, $minutes, $seconds);
	$suffixes = array(' day', ' hour', ' minute', ' second');
	$expiration = array();

	for ($i = 0; $i < count($suffixes); $i++) {
		if ($times[$i] != 0) {
			$duration = $times[$i].$suffixes[$i];
			if ($times[$i] > 1) {
				$duration .= "s";
			}
			array_push($expiration, $duration);
		}
	}

	return join(", ", $expiration);
}

function format_bytes($size)
{
	$suffixes = array('B', 'KiB', 'MiB', 'GiB', 'TiB' , 'PiB' , 'EiB', 'ZiB', 'YiB');
	$boundary = 2048.0;

	for ($suffix_pos = 0; $suffix_pos + 1 < count($suffixes); $suffix_pos++) {
		if ($size <= $boundary && $size >= -$boundary) {
			break;
		}
		$size /= 1024.0;
	}

	# don't print decimals for bytes
	if ($suffix_pos != 0) {
		return sprintf("%.2f%s", $size, $suffixes[$suffix_pos]);
	} else {
		return sprintf("%.0f%s", $size, $suffixes[$suffix_pos]);
	}
}

function is_api_client($override = null)
{
	static $is_api = null;

	if ($override !== null) {
		$is_api = $override;
	}

	if ($is_api === null) {
		$is_api = false;
	}
	return $is_api;
}

function random_alphanum($min_length, $max_length = null)
{
	$random = '';
	$char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$char_list .= "abcdefghijklmnopqrstuvwxyz";
	$char_list .= "1234567890";

	if ($max_length === null) {
		$max_length = $min_length;
	}
	$length = mt_rand($min_length, $max_length);

	for($i = 0; $i < $max_length; $i++) {
		if (strlen($random) == $length) break;
		$random .= substr($char_list, mt_rand(0, strlen($char_list) - 1), 1);
	}
	return $random;
}

function link_with_mtime($file)
{
	$link = base_url($file);

	if (file_exists(FCPATH.$file)) {
		$link .= "?".filemtime(FCPATH.$file);
	}

	return $link;
}

function js_cache_buster()
{
	$jsdir = FCPATH.'/data/js';
	$minified_main = $jsdir.'/main.min.js';
	if (file_exists($minified_main)) {
		return filemtime($minified_main);
	}

	$ret = 0;

	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($jsdir), RecursiveIteratorIterator::SELF_FIRST);

	foreach ($it as $file) {
		$mtime = $file->getMTime();
		if ($file->isFile()) {
			if ($mtime > $ret) {
				$ret = $mtime;
			}
		}
	}
	return $ret;
}

// Reference: http://php.net/manual/en/features.file-upload.multiple.php#109437
// This is a little different because we don't care about the fieldname
function getNormalizedFILES()
{
	$newfiles = array();
	$ret = array();

	foreach($_FILES as $fieldname => $fieldvalue)
		foreach($fieldvalue as $paramname => $paramvalue)
			foreach((array)$paramvalue as $index => $value)
				$newfiles[$fieldname][$index][$paramname] = $value;

	$i = 0;
	foreach ($newfiles as $fieldname => $field) {
		foreach ($field as $file) {
			// skip empty fields
			if ($file["error"] === 4) {
				continue;
			}
			$ret[$i] = $file;
			$ret[$i]["formfield"] = $fieldname;
			$i++;
		}
	}

	return $ret;
}

// Allow simple checking inside views
function auth_driver_function_implemented($function)
{
	static $result = array();
	if (isset($result[$function])) {
		return $result[$function];
	}

	$CI =& get_instance();
	$CI->load->driver("duser");
	$result[$function] = $CI->duser->is_implemented($function);;

	return $result[$function];
}

function static_storage($key, $value = null)
{
	static $storage = array();

	if ($value !== null) {
		$storage[$key] = $value;
	}

	if (!isset($storage[$key])) {
		$storage[$key] = null;
	}

	return $storage[$key];
}

function stateful_client()
{
	$CI =& get_instance();

	if ($CI->input->post("apikey")) {
		return false;
	}

	if (is_api_client()) {
		return false;
	}

	return true;
}

function init_cache()
{
	static $done = false;
	if ($done) {return;}

	$CI =& get_instance();
	$CI->load->driver('cache', array('adapter' => $CI->config->item("cache_backend")));
	$done = true;
}

function delete_cache($key)
{
	init_cache();
	$CI =& get_instance();
	$CI->cache->delete($key);
}

/**
 * Cache the result of the function call in the cache backend.
 * @param key cache key to use
 * @param ttl time to live for the cache entry
 * @param function function to call
 * @return return value of function (will be cached)
 */
function cache_function($key, $ttl, $function)
{
	init_cache();
	$CI =& get_instance();
	if (! $content = $CI->cache->get($key)) {
		$content = $function();
		$CI->cache->save($key, $content, $ttl);
	}
	return $content;
}

/**
 * Cache the result of a function call in the cache backend and in the memory of this process.
 * @param key cache key to use
 * @param ttl time to live for the cache entry
 * @param function function to call
 * @return return value of function (will be cached)
 */
function cache_function_full($key, $ttl, $function) {
	$local_key = 'cache_function-'.$key;
	if (static_storage($local_key) !== null) {
		return static_storage($local_key);
	}
	$ret = cache_function($key, $ttl, $function);
	static_storage($local_key, $ret);
	return $ret;
}

// Return mimetype of file
function mimetype($file) {
	$fileinfo = new finfo(FILEINFO_MIME_TYPE);

	// XXX: Workaround for PHP#71434 https://bugs.php.net/bug.php?id=71434
	$old = error_reporting();
	error_reporting($old &~ E_NOTICE);
	$mimetype = $fileinfo->file($file);
	error_reporting($old);

	return $mimetype;
}

function files_are_equal($a, $b)
{
	$chunk_size = 8*1024;

	// Check if filesize is different
	if (filesize($a) !== filesize($b)) {
		return false;
	}

	// Check if content is different
	$ah = fopen($a, 'rb');
	$bh = fopen($b, 'rb');

	$result = true;
	while (!feof($ah) && !feof($bh)) {
		if (fread($ah, $chunk_size) !== fread($bh, $chunk_size)) {
			$result = false;
			break;
		}
	}

	fclose($ah);
	fclose($bh);

	return $result;
}

# Source: http://php.net/manual/en/function.ini-get.php#96996
function return_bytes($size_str)
{
	switch (substr ($size_str, -1))
	{
		case 'K': case 'k': return (int)$size_str * 1024;
		case 'M': case 'm': return (int)$size_str * 1048576;
		case 'G': case 'g': return (int)$size_str * 1073741824;
		default:
		if (strlen($size_str) === strlen(intval($size_str))) {
			return (int)$size_str;
		}
		throw new \exceptions\ApiException('filebin-helper/invalid-input-unit', "Input has invalid unit");
	}
}

function ensure_json_keys_contain_objects($data, $keys) {
	foreach ($keys as $key) {
		if (empty($data[$key])) {
			$data[$key] = (object) array();
		}
	}
	return $data;
}

function output_cli_usage() {
	echo "php index.php <controller> <function> [arguments]\n";
	echo "\n";
	echo "Functions:\n";
	echo "  file cron               Cronjob\n";
	echo "  file nuke_id <ID>       Nukes all IDs sharing the same hash\n";
	echo "  user cron               Cronjob\n";
	echo "  user add_user           Add a user\n";
	echo "  user delete_user        Delete a user including all their data\n";
	echo "  tools update_database   Update/Initialise the database\n";
	echo "\n";
	echo "Functions that shouldn't have to be run:\n";
	echo "  file clean_stale_files     Remove files without database entries,\n";
	echo "                             database entries without files and multipaste\n";
	echo "                             tarballs that are no longer needed\n";
	echo "  file update_file_metadata  Update filesize and mimetype in database\n";
}

# vim: set noet:
