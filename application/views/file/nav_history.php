<ul class="nav nav-pills nav-history">
<?php
$nav = array(
	"List" => "file/upload_history",
	"Thumbnails" => "file/upload_history_thumbnails",
);

$CI =& get_instance();

foreach ($nav as $key => $item) {
	?>
		<li <?php echo $CI->uri->uri_string() == $item ? 'class="active"' : ''; ?>>
			<a href="<?php echo site_url($item); ?>"><?php echo $key; ?></a>
		</li>
	<?php
}
?>
</ul>
