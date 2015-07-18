<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace libraries;

class ExceptionHandler {
	static function setup()
	{
		set_error_handler(array("\libraries\ExceptionHandler", "error_handler"));
		set_exception_handler(array("\libraries\ExceptionHandler", 'exception_handler'));
		register_shutdown_function(array("\libraries\ExceptionHandler", "check_for_fatal"));
		assert_options(ASSERT_ACTIVE, true);
		assert_options(ASSERT_CALLBACK, array("\libraries\ExceptionHandler", '_assert_failure'));
	}

	static function error_handler($errno, $errstr, $errfile, $errline)
	{
		if (!(error_reporting() & $errno)) {
			// This error code is not included in error_reporting
			return;
		}
		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	}

	// Source: https://gist.github.com/abtris/1437966
	static private function getExceptionTraceAsString($exception) {
		$rtn = "";
		$count = 0;
		foreach ($exception->getTrace() as $frame) {
			$args = "";
			if (isset($frame['args'])) {
				$args = array();
				foreach ($frame['args'] as $arg) {
					if (is_string($arg)) {
						$args[] = "'" . $arg . "'";
					} elseif (is_array($arg)) {
						$args[] = "Array";
					} elseif (is_null($arg)) {
						$args[] = 'NULL';
					} elseif (is_bool($arg)) {
						$args[] = ($arg) ? "true" : "false";
					} elseif (is_object($arg)) {
						$args[] = get_class($arg);
					} elseif (is_resource($arg)) {
						$args[] = get_resource_type($arg);
					} else {
						$args[] = $arg;
					}
				}
				$args = join(", ", $args);
			}
			$rtn .= sprintf( "#%s %s(%s): %s(%s)\n",
				$count,
				isset($frame['file']) ? $frame['file'] : 'unknown file',
				isset($frame['line']) ? $frame['line'] : 'unknown line',
				(isset($frame['class']))  ? $frame['class'].$frame['type'].$frame['function'] : $frame['function'],
				$args );
			$count++;
		}
		return $rtn;
	}

	static public function log_exception($ex)
	{
		$exceptions = array($ex);
		while ($ex->getPrevious() !== null) {
			$ex = $ex->getPrevious();
			$exceptions[] = $ex;
		}

		foreach ($exceptions as $key => $e) {
			$message = sprintf("Exception %d/%d '%s' with message '%s' in %s:%d\n", $key+1, count($exceptions), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
			if (method_exists($e, "get_error_id")) {
				$message .= 'Error ID: '.$e->get_error_id()."\n";
			}
			if (method_exists($e, "get_data") && $e->get_data() !== NULL) {
				$message .= 'Data: '.var_export($e->get_data(), true)."\n";
			}
			$message .= "Backtrace:\n".self::getExceptionTraceAsString($e)."\n";
			error_log($message);
		}
	}

	// The actual exception handler
	static public function exception_handler($ex)
	{
		self::log_exception($ex);

		$display_errors = in_array(strtolower(ini_get('display_errors')), array('1', 'on', 'true', 'stdout'));
		if (php_sapi_name() === 'cli' OR defined('STDIN')) {
			$display_errors = true;
		}

		$GLOBALS["is_error_page"] = true;
		$heading = "Internal Server Error";
		$message = "<p>An unhandled error occured.</p>\n";

		if ($display_errors) {
			$exceptions = array($ex);
			while ($ex->getPrevious() !== null) {
				$ex = $ex->getPrevious();
				$exceptions[] = $ex;
			}

			foreach ($exceptions as $key => $e) {
				$backtrace = self::getExceptionTraceAsString($e);
				$message .= '<div>';
				$message .= '<b>Exception '.($key+1).' of '.count($exceptions).'</b><br>';
				$message .= '<b>Fatal error</b>:  Uncaught exception '.htmlspecialchars(get_class($e)).'<br>';
				$message .= '<b>Message</b>: '.htmlspecialchars($e->getMessage()).'<br>';
				if (method_exists($e, "get_error_id")) {
					$message .= '<b>Error ID</b>: '.htmlspecialchars($e->get_error_id()).'<br>';
				}
				if (method_exists($e, "get_data") && $e->get_data() !== NULL) {
					$message .= '<b>Data</b>: <pre>'.htmlspecialchars(var_export($e->get_data(), true)).'</pre><br>';
				}
				$message .= '<b>Backtrace:</b><br>';
				$message .= '<pre>'.htmlspecialchars(str_replace(FCPATH, "./", $backtrace)).'</pre>';
				$message .= 'thrown in <b>'.htmlspecialchars($e->getFile()).'</b> on line <b>'.htmlspecialchars($e->getLine()).'</b><br>';
				$message .= '</div>';
			}
		} else {
			$message .="<p>More information can be found in the php error log or by enabling display_errors.</p>";
		}

		$message = "$message";
		include APPPATH."/errors/error_general.php";
	}

	/**
	 * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
	 */
	static public function check_for_fatal()
	{
		$error = error_get_last();
		if ($error["type"] == E_ERROR) {
			self::exception_handler(new \ErrorException(
				$error["message"], 0, $error["type"], $error["file"], $error["line"]));
		}
	}

	static public function assert_failure($file, $line, $expr, $message = "")
	{
		self::exception_handler(new Exception("assert($expr): Assertion failed in $file at line $line".($message != "" ? " with message: '$message'" : "")));
		exit(1);
	}

}
