<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Home extends BaseController
{
    // Declare public methods before BaseController runs permission checks.
    protected $public_methods = ['index'];

    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');
    }

    public function index()
    {
        return redirect(base_url() . 'auth/login');
    }
}
