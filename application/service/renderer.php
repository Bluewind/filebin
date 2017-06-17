<?php
/*
 * Copyright 2017 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace service;
class renderer {


	/**
	 * @param $output_cache output cache object
	 * @param $mfile mfile object
	 * @param $data data for the rendering of views
	 */
	public function __construct($output_cache, $mfile, $data)
	{
		$this->output_cache = $output_cache;
        $this->mfile = $mfile;
        $this->data = $data;
	}

	private function colorify($file, $lexer, $anchor_id = false)
	{
		$output = "";
		$lines_to_remove = 0;

		$output .= '<div class="code content table">'."\n";
		$output .= '<div class="highlight"><code class="code-container">'."\n";

		$content = file_get_contents($file);

		$linecount = count(explode("\n", $content));
		$content = $this->reformat_json($lexer, $linecount, $content);

		if ($lexer == "ascii") {
			// TODO: use exec safe and catch exception
			$ret = (new \libraries\ProcRunner(array('ansi2html', '-p', '-m')))
				->input($content)
				->forbid_stderr()
				->exec();
			// Last line is empty
			$lines_to_remove = 1;
		} else {
			// TODO: use exec safe and catch exception
			$ret = (new \libraries\ProcRunner(array('pygmentize', '-F', 'codetagify', '-O', 'encoding=guess,outencoding=utf8,stripnl=False', '-l', $lexer, '-f', 'html')))
				->input($content)
				->exec();
			// Last 2 items are "</pre></div>" and ""
			$lines_to_remove = 2;
		}


		$buf = explode("\n", $ret["stdout"]);
		$line_count = count($buf);

		for ($i = 1; $i <= $lines_to_remove; $i++) {
			unset($buf[$line_count - $i]);
		}

		foreach ($buf as $key => $line) {
			$line_number = $key + 1;
			if ($key == 0) {
				$line = str_replace("<div class=\"highlight\"><pre>", "", $line);
			}

			$anchor = "n$line_number";
			if ($anchor_id !== false) {
				$anchor = "n-$anchor_id-$line_number";
			}

			if ($line === "") {
				$line = "<br>";
			}

			// Be careful not to add superflous whitespace here (we are in a <code>)
			$output .= "<div class=\"table-row\">"
							."<a href=\"#$anchor\" class=\"linenumber table-cell\">"
								."<span class=\"anchor\" id=\"$anchor\"> </span>"
							."</a>"
							."<span class=\"line table-cell\">".$line."</span><!--\n";
			$output .= "--></div>";
		}

		$output .= "</code></div>";
		$output .= "</div>";

		return array(
			"return_value" => $ret["return_code"],
			"output" => $output
		);
	}

	public function highlight_file($filedata, $lexer, $is_multipaste)
	{
		// highlight the file and cache the result, fall back to plain text if $lexer fails
		foreach (array($lexer, "text") as $lexer) {
			$highlit = cache_function($filedata['data_id'].'_'.$lexer, 100,
									  function() use ($filedata, $lexer, $is_multipaste) {
				$file = $this->mfile->file($filedata['data_id']);
				if ($lexer == "rmd") {
					ob_start();

					echo '<div class="code content table markdownrender">'."\n";
					echo '<div class="table-row">'."\n";
					echo '<div class="table-cell">'."\n";

					require_once(APPPATH."/third_party/parsedown/Parsedown.php");
					$parsedown = new \Parsedown();
					echo $parsedown->text(file_get_contents($file));

					echo '</div></div></div>';

					return array(
						"output" => ob_get_clean(),
						"return_value" => 0,
					);
				} else {
					return $this->colorify($file, $lexer, $is_multipaste ? $filedata["id"] : false);
				}
			});

			if ($highlit["return_value"] == 0) {
				break;
			} else {
				$message = "Error trying to process the file. Either the lexer is unknown or something is broken.";
				if ($lexer != "text") {
					$message .= " Falling back to plain text.";
				}
				$this->output_cache->render_now(
					array("error_message" => "<p>$message</p>"),
					"file/fragments/alert-wide"
				);
			}
		}

		$data = array_merge($this->data, array(
			'title' => htmlspecialchars($filedata['filename']),
			'id' => $filedata["id"],
			'current_highlight' => htmlspecialchars($lexer),
			'timeout' => $this->mfile->get_timeout_string($filedata["id"]),
			'filedata' => $filedata,
		));

		$this->output_cache->render_now($data, 'file/html_paste_header');
		$this->output_cache->render_now($highlit["output"]);
		$this->output_cache->render_now($data, 'file/html_paste_footer');
	}

	/**
	 * @param $lexer
	 * @param $linecount
	 * @param $content
	 * @return string
	 */
	private function reformat_json($lexer, $linecount, $content)
	{
		if ($lexer === "json" && $linecount === 1) {
			$decoded_json = json_decode($content);
			if ($decoded_json !== null && $decoded_json !== false) {
				$pretty_json = json_encode($decoded_json, JSON_PRETTY_PRINT);
				if ($pretty_json !== false) {
					$content = $pretty_json;
					$this->output_cache->render_now(
						array(
							"error_type" => "alert-info",
							"error_message" => "<p>The file below has been reformated for readability. It may differ from the original.</p>"
						),
						"file/fragments/alert-wide"
					);
				}
			}
		}
		return $content;
	}


}
