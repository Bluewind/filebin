<?php

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

function even_odd($reset = false)
{
	static $counter = 1;

	if ($reset) {
		$counter = 1;
	}

	if ($counter++%2 == 0) {
		return 'even';
	} else {
		return 'odd';
	}
}

// Source: http://hu.php.net/manual/en/function.str-pad.php#71558
// This is a multibyte enabled str_pad
function mb_str_pad($ps_input, $pn_pad_length, $ps_pad_string = " ", $pn_pad_type = STR_PAD_RIGHT, $ps_encoding = NULL)
{
	$ret = "";

	if (is_null($ps_encoding))
		$ps_encoding = mb_internal_encoding();

	$hn_length_of_padding = $pn_pad_length - mb_strlen($ps_input, $ps_encoding);
	$hn_psLength = mb_strlen($ps_pad_string, $ps_encoding); // pad string length

	if ($hn_psLength <= 0 || $hn_length_of_padding <= 0) {
		// Padding string equal to 0:
		//
		$ret = $ps_input;
		}
	else {
		$hn_repeatCount = floor($hn_length_of_padding / $hn_psLength); // how many times repeat

		if ($pn_pad_type == STR_PAD_BOTH) {
			$hs_lastStrLeft = "";
			$hs_lastStrRight = "";
			$hn_repeatCountLeft = $hn_repeatCountRight = ($hn_repeatCount - $hn_repeatCount % 2) / 2;

			$hs_lastStrLength = $hn_length_of_padding - 2 * $hn_repeatCountLeft * $hn_psLength; // the rest length to pad
			$hs_lastStrLeftLength = $hs_lastStrRightLength = floor($hs_lastStrLength / 2);			// the rest length divide to 2 parts
			$hs_lastStrRightLength += $hs_lastStrLength % 2; // the last char add to right side

			$hs_lastStrLeft = mb_substr($ps_pad_string, 0, $hs_lastStrLeftLength, $ps_encoding);
			$hs_lastStrRight = mb_substr($ps_pad_string, 0, $hs_lastStrRightLength, $ps_encoding);

			$ret = str_repeat($ps_pad_string, $hn_repeatCountLeft) . $hs_lastStrLeft;
			$ret .= $ps_input;
			$ret .= str_repeat($ps_pad_string, $hn_repeatCountRight) . $hs_lastStrRight;
			}
		else {
			$hs_lastStr = mb_substr($ps_pad_string, 0, $hn_length_of_padding % $hn_psLength, $ps_encoding); // last part of pad string

			if ($pn_pad_type == STR_PAD_LEFT)
				$ret = str_repeat($ps_pad_string, $hn_repeatCount) . $hs_lastStr . $ps_input;
			else
				$ret = $ps_input . str_repeat($ps_pad_string, $hn_repeatCount) . $hs_lastStr;
			}
		}

	return $ret;
}

function is_cli_client($override = null)
{
	static $is_cli = null;

	if ($override !== null) {
		$is_cli = $override;
	}

	if ($is_cli === null) {
		$is_cli = false;
		// official client uses "fb-client/$version" as useragent
		$clients = array("fb-client", "libcurl", "pyfb", "curl/");
		foreach ($clients as $client) {
			if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], $client) !== false) {
				$is_cli =  true;
			}
		}
	}
	return $is_cli;
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

function handle_etag($etag)
{
	$etag = strtolower($etag);
	$modified = true;

	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
		$oldtag = trim(strtolower($_SERVER['HTTP_IF_NONE_MATCH']), '"');
		if($oldtag == $etag) {
			$modified = false;
		} else {
			$modified = true;
		}
	}

	header('Etag: "'.$etag.'"');

	if (!$modified) {
		header("HTTP/1.1 304 Not Modified");
		exit();
	}
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

function send_json_reply($array, $status = "success")
{
	$reply = array();
	$reply["status"] = $status;
	$reply["data"] = $array;

	$CI =& get_instance();
	$CI->output->set_content_type('application/json');
	$CI->output->set_output(json_encode($reply));
}

function send_json_error_reply($error_id, $message, $array = null, $status_code = 400)
{
	$reply = array();
	$reply["status"] = "error";
	$reply["error_id"] = $error_id;
	$reply["message"] = $message;

	if ($array !== null) {
		$reply["data"] = $array;
	}

	$CI =& get_instance();
	$CI->output->set_status_header($status_code);
	$CI->output->set_content_type('application/json');
	$CI->output->set_output(json_encode($reply));
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

	if (is_cli_client()) {
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
	$mimetype = $fileinfo->file($file);

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

# vim: set noet:
