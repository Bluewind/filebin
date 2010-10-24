<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>data/paste.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>data/paste-<?php echo $current_highlight; ?>.css" />
  </head>
  <body>
    <div class="top_bar">
      <a class="raw_link no" href="<?php echo $new_link; ?>">New</a> |
      <a class="raw_link no" href="<?php echo $raw_link; ?>">Raw</a> |
      <a class="raw_link no" href="<?php echo $plain_link; ?>">Plain</a> |
      Currently: <?php echo $current_highlight; ?>
      <div style="float:right;">
        <a class="raw_link no" href="<?php echo $auto_link; ?>">Code</a> |
        <a class="raw_link no" href="<?php echo $rmd_link; ?>">Render Markdown</a>
      </div>
    </div>
    <table class="content">
      <tr>
