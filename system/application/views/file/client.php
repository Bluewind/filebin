<p><b>Shell:</b></p>
<pre>
<?php if(false): ?>
>curl -n -F "content=&lt;-" <?php echo site_url(); ?> &lt; file      (not binary safe)
cat file | curl -n -F "content=&lt;-" <?php echo site_url(); ?>  (not binary safe)
<?php endif; ?>
curl -n -F "file=@/home/user/foo" <?php echo site_url(); ?>   (binary safe)
cat file | curl -n -F "file=@-;filename=stdin" <?php echo site_url(); ?>   (binary safe)
</pre>
<p><b>Client:</b><br />
<p>Development (git): <a href="http://git.server-speed.net/users/flo/fb/">http://git.server-speed.net/users/flo/fb/</a><br />
Latest release: <a href="<?php echo $client_link; ?>"><?php echo $client_link; ?></a>.<br />
GPG sigs, older versions: <a href="<?php echo $client_link_dir; ?>"><?php echo $client_link_dir; ?></a>
</p>
<p>If you want to use authentication (needed for deleting) add the following to your ~/.netrc:</p>
<pre>
machine paste.xinu.at password my_secret_password
</pre>
<p><b>Packages:</b><br />
Arch Linux: pacman -S fb-client<br />
Debian: <a href="<?php echo $client_link_deb; ?>"><?php echo $client_link_deb; ?></a><br />
Slackware: <a href="<?php echo $client_link_slackware; ?>"><?php echo $client_link_slackware; ?></a></p>

