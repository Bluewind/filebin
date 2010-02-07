<?php
/*
 * Copyright 2009-2010 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under GPLv3
 * (see COPYING for full license text)
 *
 */

class File extends Controller {
  // TODO: Add comments

  function __construct()
  {
    parent::Controller();
    $this->load->helper('form');
    $this->load->model('file_mod');
  }

  function index()
  {
    $this->upload_form();
  }

  function upload_form()
  {
    $data = array();
    $data['title'] = 'Upload';

    $this->load->view('file/header', $data);
    $this->load->view('file/upload_form', $data);
    $this->load->view('file/footer', $data);
  }

  function delete()
  {
    $id = $this->uri->segment(3);
    $password = $this->input->post('password');
    if ($password !== false && $this->file_mod->id_exists($id)) {
      $this->file_mod->delete_id($id, $password);
      echo $id." deleted\n";
    } else {
      echo 'Couldn\'t delete '.$id."\n";
    }
    die();
  }

  function do_upload()
  {
    $data = array();
    if(isset($_FILES['userfile'])) {
      if ($_FILES['userfile']['error'] === 0) {
        $filesize = filesize($_FILES['userfile']['tmp_name']);
        if ($filesize >= $this->config->item('upload_max_size')) {
          $this->load->view('file/header', $data);
          $this->load->view('file/too_big');
        } else {
          $password = $this->input->post('password');
          $extension = $this->input->post('extension');
          if ($password !== false) {
            $password = $this->file_mod->hash_password($password);
          } else {
            $password = 'NULL';
          }

          $id = $this->file_mod->new_id();
          $file_hash = md5_file($_FILES['userfile']['tmp_name']);
          $file_name = $_FILES['userfile']['name'];
          $folder = $this->file_mod->folder($file_hash);
          file_exists($folder) || mkdir ($folder);
          $file = $this->file_mod->file($file_hash);
          
          $sql = '
            INSERT INTO `files` (`hash`, `id`, `filename`, `password`, `date`)
            VALUES (?, ?, ?, ?, ?)';
          $query = $this->db->query($sql, array($file_hash, $id, $file_name, $password, time()));
          
          move_uploaded_file($_FILES['userfile']['tmp_name'], $file);
          chmod($file, 0600);

          redirect($this->config->item('paste_show_url').$id.'/'.$extension);
        }
      } else {
        $this->index();
      }
    } else {
      $this->load->view('file/header', $data);
      $this->load->view('file/upload_error', $data);
      $this->load->view('file/footer', $data);
    }
  }

  function show_url()
  {
    $data = array();
    $id = $this->uri->segment(3);
    $mode = $this->uri->segment(4);

    if ($mode) {
      $data['url'] = site_url($this->config->item('paste_download_url').$id.'/'.$mode);
    } else {
      $data['url'] = site_url($this->config->item('paste_download_url').$id).'/';
    }

    if (strstr($_SERVER['HTTP_USER_AGENT'], 'libcurl')) {
      echo $data['url'];
    } else {
      $this->load->view('file/header', $data);
      $this->load->view('file/show_url', $data);
      $this->load->view('file/footer', $data);
    }
  }

  function download()
  {
    $data = array();
    $id = $this->uri->segment(3);
    $mode = $this->uri->segment(4);

    $filedata = $this->file_mod->get_filedata($id);
    $file = $this->file_mod->file($filedata['hash']);
    
    if ($this->file_mod->id_exists($id) && file_exists($file)) {
      // MODIFIED SINCE SUPPORT -- START
      // helps to keep traffic low when reloading an image
      // TODO: check for bugs, find source of code again
      $filedate = filectime($file);
      $etag = strtolower(md5_file($file));
      $modified = true;

      if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        $oldtag = trim(strtolower($_SERVER['HTTP_IF_NONE_MATCH']), '"');
        if($oldtag == $etag) {
          $modified = false;
        } else {
          $modified = true;
        }
      }
       
      if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        $olddate = date_parse(trim(strtolower($_SERVER['HTTP_IF_MODIFIED_SINCE'])));
        $olddate = gmmktime($olddate['hour'],
                            $olddate['minute'],
                            $olddate['second'],
                            $olddate['month'],
                            $olddate['day'],
                            $olddate['year']);
        if($olddate >= $filedate) {
          $modified = false;
        } else {
          $modified = true;
        }
      }
      // MODIFIED SINCE SUPPORT -- END

      $type = exec('/usr/bin/perlbin/vendor/mimetype -b '.escapeshellarg($file));

      if (!$mode && substr_count(ltrim($this->uri->uri_string(), "/"), '/') >= 3) {
        $mode = $this->file_mod->mime2extension($type);
      }

      if (!$modified) {
        header("HTTP/1.1 304 Not Modified");
        header('Etag: "'.$etag.'"');
      } else {
        if ($mode 
        && $this->file_mod->mime2extension($type)
        && filesize($file) <= $this->config->item('upload_max_text_size')
        ) {
          header("Content-Type: text/html\n");
          // TODO: move to own file
          echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
          .'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"'
          .'  <head><title>'.$filedata['filename'].'</title>'
          .'  <link rel="stylesheet" type="text/css" href="'.base_url().'data/paste.css" />'
          .'  </head>'
          .'<body>'
          .'  <div class="top_bar">'
          .'  <a class="raw_link no" href="'.site_url($this->config->item('paste_download_url').$id).'">Raw</a>'
          .'  </div'
          .'  <table class="content">'
          .'  <tr><td class="numbers"><pre>';
          // TODO: implement in PHP
          echo shell_exec('/usr/bin/seq 1 $(/usr/bin/wc -l '.escapeshellarg($file).' | /bin/cut -d\  -f1) | sed -r \'s/^(.*)$/<a href="#n\1" class="no" name="n\1" id="n\1">\1<\/a>/g\'');
          echo '  </pre></td><td class="code"><pre>'."\n";
          echo shell_exec(FCPATH.'scripts/syntax-highlighting.sh '.$filedata['filename'].'.'.$mode.' < '.escapeshellarg($file));
          echo '</pre></td></tr></table></body></html>';
        } else {
          header("Content-Type: ".$type."\n");
          header("Content-disposition: inline; filename=\"".$filedata['filename']."\"\n");
          header("Content-Length: ".filesize($file)."\n");
          header("Last-Modified: ".date('D, d M Y H:i:s', $filedate)." GMT");
          header('Etag: "'.$etag.'"');
          $fp = fopen($file,"r");
          while (!feof($fp)) {
            $buff = fread($fp,4096);
            echo $buff;
          }
          fclose($fp);
        }
      }
      exit();
    } else {
      $this->load->view('file/header', $data);
      $this->load->view('file/non_existant');
      $this->load->view('file/footer', $data);
    }
  }

  function cron()
  {
    $oldest_time = (time()-$this->config->item('upload_max_age'));
    $query = $this->db->query('SELECT hash, id FROM files WHERE date < ?',
      array($oldest_time));

    foreach($query->result_array() as $row) {
      $file = $this->file_mod->file($row['hash']);
      if(filemtime($file) < $oldest_time) {
        unlink($file);
        $this->db->query('DELETE FROM files WHERE hash = ?', array($row['hash']));
      } else {
        $this->db->query('DELETE FROM files WHERE id = ?', array($row['id']));
      }
    }
  }
}

/* End of file file.php */
/* Location: ./system/application/controllers/file.php */
