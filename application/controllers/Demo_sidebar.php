<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Demo_sidebar extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
    }

    public function index()
    {
        // Demo untuk dashboard
        $this->load->view('dashboard_example');
    }

    public function dashboard()
    {
        // Menggunakan layout dengan content view kustom
        $data['page_title'] = 'Dashboard';
        $data['content_view'] = 'demo/dashboard_content';
        $this->load->view('layout', $data);
    }

    public function report()
    {
        $data['page_title'] = 'Report';
        $data['content_view'] = 'demo/report_content';
        $this->load->view('layout', $data);
    }

    public function pengeluaran()
    {
        $data['page_title'] = 'Pengeluaran';
        $data['content_view'] = 'demo/pengeluaran_content';
        $this->load->view('layout', $data);
    }

    public function marketing_overview()
    {
        $data['page_title'] = 'Marketing Overview';
        $data['content_view'] = 'demo/marketing_overview_content';
        $this->load->view('layout', $data);
    }

    public function marketing_advertiser()
    {
        $data['page_title'] = 'Advertiser List';
        $data['content_view'] = 'demo/advertiser_content';
        $this->load->view('layout', $data);
    }

    public function marketing_influencer()
    {
        $data['page_title'] = 'Influencer';
        $data['content_view'] = 'demo/influencer_content';
        $this->load->view('layout', $data);
    }

    public function marketing_influencer_listing()
    {
        $data['page_title'] = 'Influencer Listing';
        $data['content_view'] = 'demo/influencer_listing_content';
        $this->load->view('layout', $data);
    }

    public function marketing_endorse_campaign()
    {
        $data['page_title'] = 'Endorse Campaign';
        $data['content_view'] = 'demo/endorse_campaign_content';
        $this->load->view('layout', $data);
    }

    public function marketing_endorse_calendar()
    {
        $data['page_title'] = 'Endorse Calendar';
        $data['content_view'] = 'demo/endorse_calendar_content';
        $this->load->view('layout', $data);
    }
}
