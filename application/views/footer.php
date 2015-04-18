<?php
if (is_cli_client() && !isset($force_full_html)) {
	return;
}
?>
	</div><!-- .container -->
<div id="push"></div>
</div> <!-- #wrap -->
<footer class="footer" id="footer">
	<div class="container muted credits">
			<p>Site code licensed under <a href="http://www.gnu.org/licenses/agpl-3.0.html" target="_blank">AGPL v3</a>.</p>
			<p><a href="http://glyphicons.com">Glyphicons Free</a> licensed under <a href="http://creativecommons.org/licenses/by/3.0/">CC BY 3.0</a>.</p>
			<ul class="footer-links">
				<li><a href="http://git.server-speed.net/users/flo/filebin/">Source</a></li>
				<li class="muted">&middot;</li>
				<li><a href="<?php echo site_url("file/contact"); ?>">Contact</a></li>
			</ul>
	</div>
</footer>

<?php
$CI = &get_instance();
if ($CI->config->item("environment") == "development" && property_exists($CI, "email")) {
	echo $CI->email->print_debugger();
}
?>
</body>
</html>
