<?php

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 *
 */
	define('ENVIRONMENT', 'development');
/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */

if (false && defined('ENVIRONMENT'))
{
	switch (ENVIRONMENT)
	{
		case 'development':
			error_reporting(E_ALL);
		break;
	
		case 'testing':
		case 'production':
			error_reporting(0);
		break;

		default:
			exit('The application environment is not set correctly.');
	}
}

/*
 *---------------------------------------------------------------
 * SYSTEM FOLDER NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" folder.
 * Include the path if the folder is not in the same  directory
 * as this file.
 *
 */
	$system_path = 'system';

/*
 *---------------------------------------------------------------
 * APPLICATION FOLDER NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * folder then the default one you can set its name here. The folder
 * can also be renamed or relocated anywhere on your server.  If
 * you do, use a full server path. For more info please see the user guide:
 * http://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 *
 */
	$application_folder = 'application';

/*
 * --------------------------------------------------------------------
 * DEFAULT CONTROLLER
 * --------------------------------------------------------------------
 *
 * Normally you will set your default controller in the routes.php file.
 * You can, however, force a custom routing by hard-coding a
 * specific controller class/function here.  For most applications, you
 * WILL NOT set your routing here, but it's an option for those
 * special instances where you might want to override the standard
 * routing in a specific front controller that shares a common CI installation.
 *
 * IMPORTANT:  If you set the routing here, NO OTHER controller will be
 * callable. In essence, this preference limits your application to ONE
 * specific controller.  Leave the function name blank if you need
 * to call functions dynamically via the URI.
 *
 * Un-comment the $routing array below to use this feature
 *
 */
	// The directory name, relative to the "controllers" folder.  Leave blank
	// if your controller is not in a sub-folder within the "controllers" folder
	// $routing['directory'] = '';

	// The controller class file name.  Example:  Mycontroller
	// $routing['controller'] = '';

	// The controller function you wish to be called.
	// $routing['function']	= '';


/*
 * -------------------------------------------------------------------
 *  CUSTOM CONFIG VALUES
 * -------------------------------------------------------------------
 *
 * The $assign_to_config array below will be passed dynamically to the
 * config class when initialized. This allows you to set custom config
 * items or override any default config values found in the config.php file.
 * This can be handy as it permits you to share one application between
 * multiple front controller files, with each file containing different
 * config values.
 *
 * Un-comment the $assign_to_config array below to use this feature
 *
 */
	// $assign_to_config['name_of_config_item'] = 'value of config item';



// --------------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
// --------------------------------------------------------------------

/*
 * ---------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * ---------------------------------------------------------------
 */

	// Set the current directory correctly for CLI requests
	if (defined('STDIN'))
	{
		chdir(dirname(__FILE__));
	}

	if (realpath($system_path) !== FALSE)
	{
		$system_path = realpath($system_path).'/';
	}

	// ensure there's a trailing slash
	$system_path = rtrim($system_path, '/').'/';

	// Is the system path correct?
	if ( ! is_dir($system_path))
	{
		exit("Your system folder path does not appear to be set correctly. Please open the following file and correct this: ".pathinfo(__FILE__, PATHINFO_BASENAME));
	}

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
	// The name of THIS file
	define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

	// The PHP file extension
	// this global constant is deprecated.
	define('EXT', '.php');

	// Path to the system folder
	define('BASEPATH', str_replace("\\", "/", $system_path));

	// Path to the front controller (this file)
	define('FCPATH', str_replace(SELF, '', __FILE__));

	// Name of the "system folder"
	define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));


	// The path to the "application" folder
	if (is_dir($application_folder))
	{
		define('APPPATH', $application_folder.'/');
	}
	else
	{
		if ( ! is_dir(BASEPATH.$application_folder.'/'))
		{
			exit("Your application folder path does not appear to be set correctly. Please open the following file and correct this: ".SELF);
		}

		define('APPPATH', BASEPATH.$application_folder.'/');
	}

	if (getenv("HOME") == "") {
		putenv('HOME='.FCPATH);
	}

/*
 * Custom error handling
 */
/* CI uses that name for it's error handling function. It misleading, but
 *  whatever. If I don't use it the framework will override my handler later.
 */
function _exception_handler($errno, $errstr, $errfile, $errline)
{
	if (!(error_reporting() & $errno)) {
		// This error code is not included in error_reporting
		return;
	}
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("_exception_handler");

// Source: https://gist.github.com/abtris/1437966
function getExceptionTraceAsString($exception) {
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

function _log_exception($e)
{
	$backtrace = getExceptionTraceAsString($e);
	$log_heading = sprintf("Exception '%s' with message '%s' in %s:%d", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
	error_log($log_heading."\n".$backtrace);
}

// The actual exception handler
function _actual_exception_handler($e)
{
	_log_exception($e);

	$display_errors = in_array(strtolower(ini_get('display_errors')), array('1', 'on', 'true', 'stdout'));

	$GLOBALS["is_error_page"] = true;
	$heading = "Internal Server Error";
	$message = "<p>An unhandled error occured.</p>\n";

	if ($display_errors) {
		$backtrace = getExceptionTraceAsString($e);
		$message .= '<div>';
		$message .= '<b>Fatal error</b>:  Uncaught exception '.get_class($e).'<br>';
		$message .= '<b>Message</b>: '.$e->getMessage().'<br>';
		$message .= '<pre>'.(str_replace(FCPATH, "./", $backtrace)).'</pre>';
		$message .= 'thrown in <b>'.$e->getFile().'</b> on line <b>'.$e->getLine().'</b><br>';
		$message .= '</div>';
	} else {
		$message .="<p>More information can be found in syslog or by enabling display_errors.</p>";
	}

	$message = "$message";
	include APPPATH."/errors/error_general.php";
}
set_exception_handler('_actual_exception_handler');

/**
 * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
 */
function check_for_fatal()
{
	$error = error_get_last();
	if ($error["type"] == E_ERROR) {
		_actual_exception_handler(new ErrorException(
			$error["message"], 0, $error["type"], $error["file"], $error["line"]));
	}
}
register_shutdown_function("check_for_fatal");

function _assert_failure($file, $line, $expr, $message = "")
{
	_actual_exception_handler(new Exception("assert($expr): Assertion failed in $file at line $line".($message != "" ? " with message: '$message'" : "")));
	exit(1);
}

assert_options(ASSERT_ACTIVE,   true);
assert_options(ASSERT_CALLBACK, '_assert_failure');
/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 *
 * And away we go...
 *
 */
try {
	require_once BASEPATH.'core/CodeIgniter.php';
} catch (\exceptions\NotAuthenticatedException $e) {
	redirect("user/login");
} catch (\exceptions\PublicApiException $e) {
	show_error(nl2br(htmlspecialchars($e->__toString())), $e->get_http_error_code());
}

/* End of file index.php */
/* Location: ./index.php */
