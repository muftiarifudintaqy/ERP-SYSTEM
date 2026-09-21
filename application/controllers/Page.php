<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Page extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('template');
        $this->load->helper('url');
    }

    public function privacy_policy()
    {
        $data['title'] = 'Privacy Policy - ' . $this->template->title();
        $this->load->view('page/privacy_policy', $data);
    }

    public function error()
    {
        show_404();
    }
}
