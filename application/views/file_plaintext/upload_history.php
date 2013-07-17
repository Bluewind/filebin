<?php
echo
	mb_str_pad($fields["id"], $lengths["id"])." | "
	.mb_str_pad($fields["filename"], $lengths["filename"])." | "
	.mb_str_pad($fields["mimetype"], $lengths["mimetype"])." | "
	.mb_str_pad($fields["date"], $lengths["date"])." | "
	.mb_str_pad($fields["hash"], $lengths["hash"])." | "
	.mb_str_pad($fields["filesize"], $lengths["filesize"])."\n";

foreach($query as $key => $item) {
	echo
		mb_str_pad($item["id"], $lengths["id"])." | "
		.mb_str_pad($item["filename"], $lengths["filename"])." | "
		.mb_str_pad($item["mimetype"], $lengths["mimetype"])." | "
		.date("r", $item["date"])." | "
		.$item["hash"]." | "
		.$item["filesize"]."\n";
}
?>

Total sum of your distinct uploads: <?php echo $total_size; ?>.
