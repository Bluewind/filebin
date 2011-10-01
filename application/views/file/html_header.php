<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>data/paste.css?<?php echo filemtime(FCPATH."/data/paste.css"); ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>data/paste-<?php echo $current_highlight; ?>.css<?php if (file_exists(FCPATH."/data/paste-".$current_highlight.".css")) { echo "?".filemtime(FCPATH."/data/paste-".$current_highlight.".css");} ?>" />
  </head>
  <body>
    <div class="top_bar">
      <a class="raw_link no" href="<?php echo $new_link; ?>">New</a> |
      <a class="raw_link no" href="<?php echo $raw_link; ?>">Raw</a> |
      <a class="raw_link no" href="<?php echo $plain_link; ?>">Plain</a> |
      Currently: <?php echo $current_highlight; ?> |
      Timeout: <a class="raw_link no" href="<?php echo $delete_link; ?>" title="delete"><?php echo $timeout; ?></a>
      <div style="float:right;">
        <a class="raw_link no" href="<?php echo $auto_link; ?>">Code</a> |
        <a class="raw_link no" href="<?php echo $rmd_link; ?>">Render Markdown</a>
      </div>
    </div>
  <script type="text/javascript">
		/* <![CDATA[ */
function update_anchor_highlight() {
	var anchor = window.location.hash.substr(1);
	var element = document.getElementById("highlight_line");
	if (element) {
		element.parentNode.removeChild(element);
	}

	anchor = document.getElementById(anchor);
	if (!anchor) {
		return;
	}
	var newElement = document.createElement("div");
	newElement.setAttribute("id", "highlight_line");
	newElement.textContent=" ";
	anchor.parentNode.insertBefore(newElement, anchor.nextSibling);
}

if ("onhashchange" in window) {
	window.onload = function () {
		update_anchor_highlight();
	}
	window.onhashchange = function () {
		update_anchor_highlight();
	}
}
    /* ]]> */
  </script>
    <table class="content">
      <tr>
