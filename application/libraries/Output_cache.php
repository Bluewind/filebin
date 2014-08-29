<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Output_cache {
	private $output_cache = array();

	/**
	 * Combine multiple objects for the same view into one
	 * @param data data to pass to the view
	 * @param view view path
	 */
	public function add_merge($data, $view)
	{
		assert($view !== NULL);

		// combine multiple objects for the same view into one
		$count = count($this->output_cache);
		if ($count > 0 && $this->output_cache[$count - 1]["view"] === $view) {
			$this->output_cache[$count - 1]["data"] = array_merge_recursive($this->output_cache[$count - 1]["data"], $data);
		} else {
			$this->add($data, $view);
		}
	}

	/**
	 * Add some data that will be output directly if view is NULL or passed
	 * to the view otherweise.
	 *
	 * @param data data to pass to view or output
	 * @param view view path or NULL
	 */
	public function add($data, $view = NULL)
	{
		$this->output_cache[] = array(
			"view" => $view,
			"data" => $data,
		);
	}

	/**
	 * Add a function that will be excuted when render() is called.
	 * This function is supposed to use render_now() to output data.
	 *
	 * @param data_function
	 */
	public function add_function($data_function)
	{
		$this->output_cache[] = array(
			"view" => NULL,
			"data_function" => $data_function,
		);
	}

	public function render_now($data, $view = NULL)
	{
		if ($view !== NULL) {
			echo get_instance()->load->view($view, $data, true);
		} else {
			echo $data;
		}
	}

	public function render()
	{
		while ($output = array_shift($this->output_cache)) {
			if (isset($output["data_function"])) {
				$output["data_function"]();
			} else {
				$data = $output["data"];
				$this->render_now($data, $output["view"]);
			}
		}
	}
}
