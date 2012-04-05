Uploads/pastes are deleted after <?php echo $upload_max_age; ?> days<?php if($small_upload_size > 0): ?> unless they are smaller than <?php echo format_bytes($small_upload_size); ?><?php endif; ?>.
Maximum upload size is <?php echo format_bytes($max_upload_size); ?>.

How to link your uploads:
 - "/<ID>/" automatically highlight the uploads
 - "/<ID>" set the detected MIME type and let the browser do the rest
 - "/<ID>/plain" force the MIME type to be text/plain
 - "/<ID>/<file extension>" override auto detection and use the supplied
   file extension or language name for highlighting
 - "/<ID>/qr" display a qr code containing a link to /<ID>/
 - "/<ID>/rmd" convert markdown to HTML
 - "/<ID>/ascii" convert text with ANSI (shell) escape codes to HTML

If your upload is not detected as text, only "/<ID>/qr" and "/<ID>/plain"
will work as above and all others will simply return the file with the
detected MIME type.

