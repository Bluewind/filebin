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
    if(isset($_FILES['file'])) {
      $this->do_upload();
    } elseif ($this->input->post('content')) {
      $this->do_paste();
    } elseif ($this->file_mod->id_exists($this->uri->segment(1))) {
      $this->file_mod->download();
    } else {
      $this->upload_form();
    }
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
    if ($this->file_mod->delete_id($id, $password)) {
      echo $id." deleted\n";
    } else {
      echo 'Couldn\'t delete '.$id."\n";
    }
    die();
  }

  function do_paste() 
  {
    $data = array();
    $content = $this->input->post('content')."\n";
    $extension = $this->input->post('extension');
    if($content === "\n") {
      $this->upload_form();
      return;
    }
    if(strlen($content) >= $this->config->item('upload_max_size')) {
      $this->load->view('file/header', $data);
      $this->load->view('file/too_big');
      $this->load->view('file/footer');
      return;
    }

    $id = $this->file_mod->new_id();
    $hash = md5($content);
    $folder = $this->file_mod->folder($hash);
    file_exists($folder) || mkdir ($folder);
    $file = $this->file_mod->file($hash);

    file_put_contents($file, $content);
    chmod($file, 0600);
    $this->file_mod->add_file($hash, $id, 'stdin');
    $this->file_mod->show_url($id, $extension);
  }

  function do_upload()
  {
    $data = array();
    $extension = $this->input->post('extension');
    if(!isset($_FILES['file'])) {
      $this->load->view('file/header', $data);
      $this->load->view('file/upload_error');
      $this->load->view('file/footer');
      return;
    }
    if ($_FILES['file']['error'] !== 0) {
      $this->upload_form();
      return;
    }
    $filesize = filesize($_FILES['file']['tmp_name']);
    if ($filesize >= $this->config->item('upload_max_size')) {
      $this->load->view('file/header', $data);
      $this->load->view('file/too_big');
      $this->load->view('file/footer');
      return;
    }

    $id = $this->file_mod->new_id();
    $hash = md5_file($_FILES['file']['tmp_name']);
    $filename = $_FILES['file']['name'];
    $folder = $this->file_mod->folder($hash);
    file_exists($folder) || mkdir ($folder);
    $file = $this->file_mod->file($hash);
    
    move_uploaded_file($_FILES['file']['tmp_name'], $file);
    chmod($file, 0600);
    $this->file_mod->add_file($hash, $id, $filename);
    $this->file_mod->show_url($id, $extension);
  }

  function cron()
  {
    $oldest_time = (time()-$this->config->item('upload_max_age'));
    $query = $this->db->query('SELECT hash, id FROM files WHERE date < ?',
      array($oldest_time));

    foreach($query->result_array() as $row) {
      $file = $this->file_mod->file($row['hash']);
      if(file_exists($file) && filemtime($file) < $oldest_time) {
        unlink($file);
        $this->db->query('DELETE FROM files WHERE hash = ?', array($row['hash']));
      } else {
        $this->db->query('DELETE FROM files WHERE id = ? LIMIT 1', array($row['id']));
      }
    }
  }
}

# vim: set ts=2 sw=2 et:
/* End of file file.php */
/* Location: ./system/application/controllers/file.php */
