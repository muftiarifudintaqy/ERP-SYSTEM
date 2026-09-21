<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Calendar extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        // Set public methods (no permission required)
        $this->set_public_methods([]);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([
            'remove' => 'delete',
            'action' => 'edit'
        ]);
    }


    public function index()
    {
        $data['template'] = $this->template;
        $data['title'] = 'Kalender Endorse - ' . $this->template->title();

        // Check if reset filter is requested
        if ($this->input->get('reset_filter') === '1') {
            // Clear session filters
            $this->session->unset_userdata('calendar_filters');
            // Redirect to clean URL
            redirect('calendar');
            return;
        }

        // Initialize filters from GET or SESSION
        $calendar_filters = $this->input->get('group_by');
        $ids_campaign = $this->input->get('ids_campaign');
        $pic_filter = $this->input->get('pic');
        $campaign_filter = $this->input->get('campaign');
        $ids_product = $this->input->get('ids_product');

        // Load from session if not in GET parameters
        $session_filters = $this->session->userdata('calendar_filters');
        
        if ($calendar_filters === null && isset($session_filters['group_by'])) {
            $calendar_filters = $session_filters['group_by'];
        }
        if ($ids_campaign === null && isset($session_filters['ids_campaign'])) {
            $ids_campaign = $session_filters['ids_campaign'];
        }
        if ($pic_filter === null && isset($session_filters['pic'])) {
            $pic_filter = $session_filters['pic'];
        }
        if ($campaign_filter === null && isset($session_filters['campaign'])) {
            $campaign_filter = $session_filters['campaign'];
        }
        if ($ids_product === null && isset($session_filters['ids_product'])) {
            $ids_product = $session_filters['ids_product'];
        }

        // Set defaults
        if (empty($calendar_filters)) {
            $calendar_filters = ['rencana_at'];
        } else {
            if (!is_array($calendar_filters)) {
                $calendar_filters = [$calendar_filters];
            }
        }
        
        if ($ids_campaign === null) {
            $ids_campaign = [];
        }
        if ($ids_product === null) {
            $ids_product = [];
        }

        // Save to session
        $this->session->set_userdata('calendar_filters', [
            'group_by' => $calendar_filters,
            'ids_campaign' => $ids_campaign,
            'pic' => $pic_filter,
            'campaign' => $campaign_filter,
            'ids_product' => $ids_product
        ]);

        // Pass to view for maintaining state
        $data['current_filters'] = [
            'group_by' => $calendar_filters,
            'ids_campaign' => $ids_campaign,
            'pic' => $pic_filter,
            'campaign' => $campaign_filter,
            'ids_product' => $ids_product
        ];

        if (empty($campaign_filter)) {
            $qry = " AND is_internal = 0 ";
        } else if ($campaign_filter == 'Internal') {
            $qry = " AND is_internal = 1 ";
        } else if ($campaign_filter == 'Semua Campaign') {
            $qry = " AND is_internal = 0 OR is_internal = 1 ";
        }

        $sql = "
            SELECT 
                GROUP_CONCAT(CONCAT(endorse.nama_creator) SEPARATOR '; ') AS combined_info,
                endorse_campaign.title AS title,
                endorse.pic AS pic,
                endorse.rencana_at,
                endorse.posting_at,
                endorse.platform,
                endorse.status_endorse,
                endorse.id
            FROM endorse
            INNER JOIN endorse_campaign ON endorse.id_campaign = endorse_campaign.id
            WHERE status_endorse NOT IN ('Review', 'Hold', 'Reject') $qry
        ";

        if (!empty($ids_campaign)) {
            $ids_campaign_str = implode(",", $ids_campaign);
            $sql .= " AND endorse.id_campaign IN ($ids_campaign_str)";
        }

        if (!empty($ids_product)) {
            $ids_product_str = implode(",", $ids_product);
            $sql .= " AND endorse.product LIKE '%$ids_product_str%'";
        }

        if (!empty($pic_filter)) {
            $sql .= " AND endorse.pic LIKE '%$pic_filter%'";
        }

        $sql .= " GROUP BY endorse.id";

        $events = $this->mymodel->selectWithQuery($sql);

        $formatted_events = [];

        foreach ($events as $event) {
            if (!empty($event['combined_info'])) {
                $names = explode('; ', $event['combined_info']);

                // Check status_endorse instead of posting_at
                if (in_array('rencana_at', $calendar_filters) && !empty($event['rencana_at'])) {
                    if ($event['status_endorse'] != 'Posted Content') { // Check if status is not Posted Content
                        foreach ($names as $name) {
                            if (!empty($name)) {
                                $formatted_events[] = [
                                    'title' => $name,
                                    'start' => $event['rencana_at'],
                                    'end' => $event['rencana_at'],
                                    'extendedProps' => [
                                        'campaign' => $event['title'],
                                        'pic' => $event['pic'],
                                        'type' => 'RENCANA UPLOAD',
                                        'status' => $event['status_endorse'],
                                        'platform' => $event['platform'],
                                        'id' => $event['id'],
                                    ],
                                ];
                            }
                        }
                    }
                }

                if (in_array('posting_at', $calendar_filters) && !empty($event['posting_at'])) {
                    if ($event['status_endorse'] == 'Posted Content') { // Check if status is Posted Content
                        foreach ($names as $name) {
                            if (!empty($name)) {
                                $formatted_events[] = [
                                    'title' => $name,
                                    'start' => $event['posting_at'],
                                    'end' => $event['posting_at'],
                                    'extendedProps' => [
                                        'campaign' => $event['title'],
                                        'pic' => $event['pic'],
                                        'type' => 'SUDAH UPLOAD',
                                        'platform' => $event['platform'],
                                        'id' => $event['id'],
                                    ],
                                ];
                            }
                        }
                    }
                }
            }
        }

        $data['events'] = json_encode($formatted_events);

        $data['campaign'] = $this->mymodel->selectWithQuery("SELECT *
            FROM endorse_campaign
            ORDER BY title ASC");

        $data['product'] = $this->mymodel->selectWithQuery("
            SELECT id, name 
            FROM product 
            WHERE 
                is_operational = 0 
                AND status = 'Aktif' 
                AND (
                    is_varian = 1 
                    OR (is_varian = 0 AND (parent_id IS NULL OR parent_id = ''))
                )
            ORDER BY name ASC");

        $view_path = 'endorse/calendar';
        $data['content'] = $this->load->view($view_path, $data, true);

        $this->load->view('TemplateDashboard', $data);
    }
}