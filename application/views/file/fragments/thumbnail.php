<!-- Comment markers background: http://stackoverflow.com/a/14776780/953022 -->
<div class="container container-wide">
<?php
$base_url = site_url();
if (substr($base_url, -1) !== "/") {
	$base_url .= "/";
}
?>
<div class="upload_thumbnails"><!--
	<?php foreach($items as $key => $item): ?>
		--><a
			<?php if (strpos($item["mimetype"], "image/") === 0) {?>rel="gallery" class="colorbox"<?php } ?>
			data-orientation="<?php echo $item["orientation"]; ?>"
			href="<?php echo $base_url.$item["id"]."/"; ?>"
			title="<?php echo htmlentities($item["filename"]); ?>"
			data-content="<?php echo htmlentities($item["tooltip"]); ?>"
			data-id="<?php echo $item["id"]; ?>"><!--
			--><img	class="thumb lazyload"
				data-original="<?php echo $base_url."file/thumbnail/".$item["id"]; ?>"
				><!--
			--><noscript><img class="thumb" src="<?php echo $base_url."file/thumbnail/".$item["id"]; ?>"></noscript></a><!--
	<?php endforeach; ?>
	-->
</div>
</div>
