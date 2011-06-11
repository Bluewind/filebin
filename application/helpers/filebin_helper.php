<?php

// Source: http://ibnuyahya.com/format-bytes-to-kb-mb-gb/
function format_bytes($size, $precision = 2){
		$base = log($size) / log(1024);
		$suffixes = array('B', 'KiB', 'MiB', 'GiB', 'TiB' , 'PiB' , 'EiB');
		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
}

// Original source: http://www.phpfreaks.com/forums/index.php?topic=198274.msg895468#msg895468
function rangeDownload($file, $filename, $type)
{
	$fp = @fopen($file, 'r');

	$size	= filesize($file); // File size
	$length = $size;	   // Content length
	$start	= 0;		   // Start byte
	$end	= $size - 1;	   // End byte
	// Now that we've gotten so far without errors we send the accept range header
	/* At the moment we only support single ranges.
	 * Multiple ranges requires some more work to ensure it works correctly
	 * and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
	 *
	 * Multirange support annouces itself with:
	 * header('Accept-Ranges: bytes');
	 *
	 * Multirange content must be sent with multipart/byteranges mediatype,
	 * (mediatype = mimetype)
	 * as well as a boundry header to indicate the various chunks of data.
	 */
	header("Accept-Ranges: 0-$length");
	// header('Accept-Ranges: bytes');
	// multipart/byteranges
	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
	if (isset($_SERVER['HTTP_RANGE']))
	{
		$c_start = $start;
		$c_end	 = $end;
		// Extract the range string
		list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
		// Make sure the client hasn't sent us a multibyte range
		if (strpos($range, ',') !== false)
		{
			// (?) Shoud this be issued here, or should the first
			// range be used? Or should the header be ignored and
			// we output the whole content?
			header('HTTP/1.1 416 Requested Range Not Satisfiable');
			header("Content-Range: bytes $start-$end/$size");
			// (?) Echo some info to the client?
			exit;
		}
		// If the range starts with an '-' we start from the beginning
		// If not, we forward the file pointer
		// And make sure to get the end byte if spesified
		if ($range{0} == '-')
		{
			// The n-number of the last bytes is requested
			$c_start = $size - substr($range, 1);
		}
		else
		{
			$range	= explode('-', $range);
			$c_start = $range[0];
			$c_end	 = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
		}
		/* Check the range and make sure it's treated according to the specs.
		 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
		 */
		// End bytes can not be larger than $end.
		$c_end = ($c_end > $end) ? $end : $c_end;
		// Validate the requested range and return an error if it's not correct.
		if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size)
		{
			header('HTTP/1.1 416 Requested Range Not Satisfiable');
			header("Content-Range: bytes $start-$end/$size");
			// (?) Echo some info to the client?
			exit;
		}
		$start	= $c_start;
		$end	= $c_end;
		$length = $end - $start + 1; // Calculate new content length
		fseek($fp, $start);
		header('HTTP/1.1 206 Partial Content');
	}
	// Notify the client the byte range we'll be outputting
	header("Content-Range: bytes $start-$end/$size");
	header("Content-Length: $length");
	header("Content-disposition: inline; filename=\"".$filename."\"\n");
	header("Content-Type: ".$type."\n");

	// Start buffered download
	$buffer = 1024 * 8;
	while(!feof($fp) && ($p = ftell($fp)) <= $end)
	{
		if ($p + $buffer > $end)
		{
			// In case we're only outputtin a chunk, make sure we don't
			// read past the length
			$buffer = $end - $p + 1;
		}
		set_time_limit(0); // Reset time limit for big files
		echo fread($fp, $buffer);
		flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
	}

	fclose($fp);
}

# vim: set noet:
