<?php register_js_include("/data/js/jquery.colorbox.js"); ?>
<?php register_js_include("/data/js/jquery.lazyload.js"); ?>
<!-- Comment markers background: http://stackoverflow.com/a/14776780/953022 -->
<div class="container container-wide">
<div class="upload_thumbnails"><!--
	<?php foreach($items as $key => $item): ?>
		--><a <?php if (strpos($item["mimetype"], "image/") === 0) {?>rel="gallery" class="colorbox"<?php } ?> data-orientation="<?php echo $item["orientation"]; ?>" href="<?php echo site_url("/".$item["id"])."/"; ?>" title="<?php echo htmlentities($item["filename"]); ?>" data-content="<?php echo htmlentities($item["tooltip"]); ?>" data-id="<?php echo $item["id"]; ?>"><img class="thumb lazyload" data-original="<?php echo site_url("file/thumbnail/".$item["id"]); ?>"><noscript><img class="thumb" src="<?php echo site_url("file/thumbnail/".$item["id"]); ?>"></noscript></a><!--
	<?php endforeach; ?>
	-->
</div>
</div>
