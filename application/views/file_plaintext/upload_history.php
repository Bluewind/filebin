<?php
echo
	mb_str_pad("ID", $lengths["id"])." | "
	.mb_str_pad("Filename", $lengths["filename"])." | "
	.mb_str_pad("Mimetype", $lengths["mimetype"])." | "
	.mb_str_pad("Date", $lengths["date"])." | "
	.mb_str_pad("Hash", $lengths["hash"])."\n";

foreach($query as $key => $item) {
	echo
		mb_str_pad($item["id"], $lengths["id"])." | "
		.mb_str_pad($item["filename"], $lengths["filename"])." | "
		.mb_str_pad($item["mimetype"], $lengths["mimetype"])." | "
		.$item["date"]." | "
		.$item["hash"]."\n";
}

