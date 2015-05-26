<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace libraries;

class ProcRunner {
	private $cmd;
	private $input = NULL;
	private $forbid_nonzero = false;
	private $forbid_stderr = false;

	/**
	 * This function automatically escapes all parameters before executing the command.
	 *
	 * @param cmd array with the command and it's arguments
	 */
	function __construct($cmd)
	{
		assert(is_array($cmd));
		$this->cmd = implode(" ", array_map('escapeshellarg', $cmd));
	}

	/**
	 * Set stdin. You will have to set this to NULL if you call exec() a second
	 * time and don't want stdin to be sent again
	 *
	 * @param input string to send via stdin
	 */
	function input($input)
	{
		$this->input = $input;
		return $this;
	}

	function forbid_nonzero()
	{
		$this->forbid_nonzero = true;
		return $this;
	}


	function forbid_stderr()
	{
		$this->forbid_stderr = true;
		return $this;
	}

	/**
	 * Run the command.
	 *
	 * @return array with keys return_code, stdout, stderr
	 */
	function exec()
	{
		$descriptorspec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);

		if ($this->input !== NULL) {
			$descriptorspec[0] = array('pipe', 'r');
		}

		$proc = proc_open($this->cmd, $descriptorspec, $pipes);

		if ($proc === false) {
			throw new \exceptions\ApiException('procrunner/proc_open-failed',
				'Failed to run process',
				array($this->cmd, $this->input)
			);
		}

		if ($this->input !== NULL) {
			fwrite($pipes[0], $this->input);
			fclose($pipes[0]);
		}

		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		assert($stdout !== false);

		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		assert($stderr !== false);

		$return_code = proc_close($proc);

		$ret = array(
			"return_code" => $return_code,
			"stdout" => $stdout,
			"stderr" => $stderr,
		);

		if ($this->forbid_nonzero && $return_code !== 0) {
			throw new \exceptions\ApiException('procrunner/non-zero-exit',
				'Process exited with a non-zero status',
				array($this->cmd, $this->input, $ret)
			);
		}

		if ($this->forbid_stderr && $stderr !== "") {
			throw new \exceptions\ApiException('procrunner/stderr',
				'Output on stderr not allowed but received',
				array($this->cmd, $this->input, $ret)
			);
		}

		return $ret;
	}

	/**
	 * Run the command and enable some sanity checks such as empty stderr and
	 * zero exit status. Might enable more in the future.
	 *
	 * @See exec
	 */
	function execSafe()
	{
		$this->forbid_stderr();
		$this->forbid_nonzero();
		return $this->exec();
	}
}
