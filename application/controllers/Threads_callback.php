<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Threads_callback extends CI_Controller
{
    public function index()
    {
        $code = $this->input->get('code', true);
        $state = $this->input->get('state', true);
        $error = $this->input->get('error', true);
        $error_description = $this->input->get('error_description', true);

        $data = [
            'code' => $code,
            'state' => $state,
            'error' => $error,
            'error_description' => $error_description,
            'all_params' => $_GET,
        ];

        $this->load->view('threads_callback', $data);
    }
}
