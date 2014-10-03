<?php register_js_include("/data/js/jquery.colorbox.js"); ?>
<!-- Comment markers background: http://stackoverflow.com/a/14776780/953022 -->
<div class="container container-wide">
<div class="upload_thumbnails"><!--
	<?php foreach($items as $key => $item): ?>
		--><a rel="gallery" class="colorbox" data-orientation="<?php echo $item["orientation"]; ?>" href="<?php echo site_url("/".$item["id"])."/"; ?>" title="<?php echo htmlentities($item["filename"]); ?>" data-content="<?php echo htmlentities($item["tooltip"]); ?>" data-id="<?php echo $item["id"]; ?>"><img class="thumb" src="<?php echo site_url("file/thumbnail/".$item["id"]); ?>"></a><!--
	<?php endforeach; ?>
	-->
</div>
</div>
