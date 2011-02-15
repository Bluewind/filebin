Uploads/pastes are deleted after 5 days<?php if($small_upload_size > 0): ?> unless they are smaller than <?php echo format_bytes($small_upload_size); ?><?php endif; ?>.
Maximum upload size is <?php echo format_bytes($max_upload_size); ?>.

<?php include "client.php"; ?>
