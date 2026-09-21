<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';
require_once FCPATH . 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class Payroll extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');
        $this->load->library('session');

        $this->set_public_methods([]);
        $this->set_method_permissions([
            'save_salary_structure' => 'edit',
            'update_salary_structure_inline' => 'edit',
            'bulk_update_salary_structure' => 'edit',
            'bulk_delete_salary_structure' => 'delete',
            'update_component_inline' => 'edit',
            'update_slip_status_inline' => 'edit',
            'update_period_status_inline' => 'edit'
        ]);
    }

    public function index()
    {
        if ($this->is_payroll_admin()) {
            redirect(base_url('payroll/dashboard'));
            return;
        }

        redirect(base_url('payroll/my_payroll'));
    }

    public function dashboard()
    {
        $this->require_payroll_admin();

        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Dasbor Payroll - ' . $this->template->title();

        $data['period_summary'] = $this->mymodel->selectWithQuery("SELECT
            COUNT(id) AS total_period,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_period,
            SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) AS reviewed_period,
            SUM(CASE WHEN status = 'finalized' THEN 1 ELSE 0 END) AS finalized_period,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_period
            FROM payroll_periods");

        $data['latest_period'] = $this->mymodel->selectWithQuery("SELECT * FROM payroll_periods ORDER BY id DESC LIMIT 1");
        $data['latest_period'] = !empty($data['latest_period']) ? $data['latest_period'][0] : null;

        $latest_period_id = !empty($data['latest_period']['id']) ? (int) $data['latest_period']['id'] : 0;

        $data['slip_summary'] = [];
        if ($latest_period_id > 0) {
            $data['slip_summary'] = $this->mymodel->selectWithQuery("SELECT
                COUNT(id) AS total_slip,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_slip,
                SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) AS reviewed_slip,
                SUM(CASE WHEN status = 'finalized' THEN 1 ELSE 0 END) AS finalized_slip,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_slip,
                COALESCE(SUM(net_amount), 0) AS total_net
                FROM payroll_slips
                WHERE payroll_period_id = '{$latest_period_id}'");
        }

        $data['recent_periods'] = $this->mymodel->selectWithQuery("SELECT * FROM payroll_periods ORDER BY id DESC LIMIT 6");

        $data['content'] = $this->load->view('payroll/dashboard', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function payroll_period()
    {
        $this->require_payroll_admin();

        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Periode Payroll - ' . $this->template->title();
        $data['periods'] = $this->mymodel->selectWithQuery("SELECT * FROM payroll_periods ORDER BY id DESC");

        $data['content'] = $this->load->view('payroll/payroll_period', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function create_period()
    {
        $this->require_payroll_admin();

        $month = (int) $this->input->post('month');
        $year = (int) $this->input->post('year');
        $pay_date = trim((string) $this->input->post('pay_date'));

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            $this->session->set_flashdata('error', 'Periode tidak valid.');
            redirect(base_url('payroll/payroll_period'));
            return;
        }

        $period_start = sprintf('%04d-%02d-01', $year, $month);
        $period_end = date('Y-m-t', strtotime($period_start));
        $period_key = sprintf('%04d-%02d', $year, $month);

        $exists = $this->mymodel->selectWithQuery("SELECT id FROM payroll_periods WHERE period_key = '{$period_key}' LIMIT 1");
        if (!empty($exists)) {
            $this->session->set_flashdata('error', 'Periode payroll sudah ada.');
            redirect(base_url('payroll/payroll_period'));
            return;
        }

        $insert = [
            'period_key' => $period_key,
            'period_label' => date('F Y', strtotime($period_start)),
            'period_start' => $period_start,
            'period_end' => $period_end,
            'pay_date' => $pay_date !== '' ? $pay_date : null,
            'status' => 'draft',
            'created_by' => $_SESSION['user']['id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('payroll_periods', $insert);
        $this->session->set_flashdata('success', 'Periode payroll berhasil dibuat.');
        redirect(base_url('payroll/payroll_period'));
    }

    public function set_period_status()
    {
        $this->require_payroll_admin();

        $id = (int) $this->input->post('id');
        $status = trim((string) $this->input->post('status'));
        $allowed = ['draft', 'reviewed', 'finalized', 'paid'];

        if ($id <= 0 || !in_array($status, $allowed, true)) {
            $this->session->set_flashdata('error', 'Status periode tidak valid.');
            redirect(base_url('payroll/payroll_period'));
            return;
        }

        $this->db->update('payroll_periods', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);

        $this->session->set_flashdata('success', 'Status periode berhasil diubah.');
        redirect(base_url('payroll/payroll_period'));
    }

    public function update_period_status_inline()
    {
        $this->require_payroll_admin();

        $id = (int) $this->input->post('id');
        $status = trim((string) $this->input->post('status'));
        $allowed = ['draft', 'reviewed', 'finalized', 'paid'];

        $response = [
            'success' => false,
            'message' => 'Status periode tidak valid.'
        ];

        if ($id <= 0 || !in_array($status, $allowed, true)) {
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }

        $exists = $this->mymodel->selectWithQuery("SELECT id FROM payroll_periods WHERE id = '{$id}' LIMIT 1");
        if (empty($exists)) {
            $response['message'] = 'Periode payroll tidak ditemukan.';
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }

        $this->db->update('payroll_periods', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);

        $response['success'] = true;
        $response['message'] = 'Status periode berhasil diubah.';
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }

    public function run_payroll()
    {
        $this->require_payroll_admin();

        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Proses Payroll - ' . $this->template->title();
        $data['periods'] = $this->mymodel->selectWithQuery("SELECT * FROM payroll_periods ORDER BY id DESC");

        $period_id = (int) ($this->input->get('period_id') ?? 0);
        if ($period_id <= 0 && !empty($data['periods'])) {
            $period_id = (int) $data['periods'][0]['id'];
        }

        $data['selected_period_id'] = $period_id;
        $data['selected_period'] = null;
        $data['slips'] = [];
        $data['preview_employees'] = [];
        $data['preview_items_map'] = [];
        $data['preview_totals_map'] = [];

        if ($period_id > 0) {
            $period = $this->mymodel->selectWithQuery("SELECT * FROM payroll_periods WHERE id = '{$period_id}' LIMIT 1");
            $data['selected_period'] = !empty($period) ? $period[0] : null;

            $data['slips'] = $this->mymodel->selectWithQuery("SELECT ps.*, u.full_name, up.bank_account_number, up.bank_name,
                COALESCE(p.name, '-') AS position_name
                FROM payroll_slips ps
                LEFT JOIN user u ON u.id = ps.user_id
                LEFT JOIN user_profile up ON up.user_id = u.id
                LEFT JOIN positions p ON p.id = up.position_id
                WHERE ps.payroll_period_id = '{$period_id}'
                ORDER BY u.full_name ASC");

            if (!empty($data['selected_period'])) {
                $period_end = $data['selected_period']['period_end'];
                $data['preview_employees'] = $this->mymodel->selectWithQuery("SELECT
                    up.user_id,
                    COALESCE(u.full_name, CONCAT('User #', up.user_id)) AS full_name,
                    up.position_id,
                    COALESCE(p.name, '-') AS position_name,
                    up.join_date,
                    up.jenis_kontrak,
                    up.bank_name,
                    up.bank_account_number
                    FROM user_profile up
                    LEFT JOIN user u ON u.id = up.user_id
                    LEFT JOIN positions p ON p.id = up.position_id
                    WHERE up.user_id IS NOT NULL
                    AND (up.join_date IS NULL OR up.join_date <= '{$period_end}')
                    ORDER BY COALESCE(u.full_name, CONCAT('User #', up.user_id)) ASC");

                foreach ($data['preview_employees'] as $employee) {
                    $preview_user_id = (int) $employee['user_id'];
                    $preview_position_id = (int) ($employee['position_id'] ?? 0);
                    $items = $this->build_component_rows($period_id, $data['selected_period'], $preview_user_id, $preview_position_id);

                    $gross = 0;
                    $deduction = 0;
                    foreach ($items as $item) {
                        if ($item['component_type_snapshot'] === 'earning') {
                            $gross += (float) $item['amount'];
                        } else {
                            $deduction += (float) $item['amount'];
                        }
                    }

                    $data['preview_items_map'][$preview_user_id] = $items;
                    $data['preview_totals_map'][$preview_user_id] = [
                        'gross' => $gross,
                        'deduction' => $deduction,
                        'net' => $gross - $deduction
                    ];
                }
            }
        }

        $data['content'] = $this->load->view('payroll/run_payroll', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function generate_payroll()
    {
        $this->require_payroll_admin();

        $period_id = (int) $this->input->post('period_id');
        $override_payload_json = (string) $this->input->post('override_payload_json');
        $override_payload = json_decode($override_payload_json, true);
        if (!is_array($override_payload)) {
            $override_payload = [];
        }
        $selected_user_ids_raw = $this->input->post('selected_user_ids');
        $selected_user_ids = $this->parse_selected_ids(
            is_array($selected_user_ids_raw) ? implode(',', $selected_user_ids_raw) : $selected_user_ids_raw
        );

        if ($period_id <= 0) {
            $this->session->set_flashdata('error', 'Periode payroll wajib dipilih.');
            redirect(base_url('payroll/run_payroll'));
            return;
        }

        if (empty($selected_user_ids)) {
            $this->session->set_flashdata('error', 'Pilih minimal 1 karyawan untuk generate payroll.');
            redirect(base_url('payroll/run_payroll?period_id=' . $period_id));
            return;
        }

        $period = $this->mymodel->selectWithQuery("SELECT * FROM payroll_periods WHERE id = '{$period_id}' LIMIT 1");
        if (empty($period)) {
            $this->session->set_flashdata('error', 'Periode payroll tidak ditemukan.');
            redirect(base_url('payroll/run_payroll'));
            return;
        }

        $period = $period[0];
        if (in_array($period['status'], ['finalized', 'paid'], true)) {
            $this->session->set_flashdata('error', 'Periode sudah finalized/paid, tidak bisa generate ulang.');
            redirect(base_url('payroll/run_payroll?period_id=' . $period_id));
            return;
        }

        $id_list = implode(',', $selected_user_ids);
        $employees = $this->mymodel->selectWithQuery("SELECT
            up.user_id,
            COALESCE(u.full_name, CONCAT('User #', up.user_id)) AS full_name,
            up.position_id,
            up.join_date,
            up.jenis_kontrak
            FROM user_profile up
            LEFT JOIN user u ON u.id = up.user_id
            WHERE up.user_id IN ({$id_list})
            ORDER BY COALESCE(u.full_name, CONCAT('User #', up.user_id)) ASC");

        $generated = 0;

        foreach ($employees as $employee) {
            $join_date = !empty($employee['join_date']) ? $employee['join_date'] : null;
            if (!empty($join_date) && strtotime($join_date) > strtotime($period['period_end'])) {
                continue;
            }

            $user_id = (int) $employee['user_id'];
            $position_id = (int) ($employee['position_id'] ?? 0);

            $existing_slip = $this->mymodel->selectWithQuery("SELECT * FROM payroll_slips
                WHERE payroll_period_id = '{$period_id}' AND user_id = '{$user_id}' LIMIT 1");

            if (!empty($existing_slip) && in_array($existing_slip[0]['status'], ['finalized', 'paid'], true)) {
                continue;
            }

            if (!empty($existing_slip)) {
                $slip_id = (int) $existing_slip[0]['id'];
                $this->db->update('payroll_slips', [
                    'status' => 'draft',
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $slip_id]);

                $this->db->delete('payroll_slip_items', ['payroll_slip_id' => $slip_id]);
            } else {
                $this->db->insert('payroll_slips', [
                    'payroll_period_id' => $period_id,
                    'user_id' => $user_id,
                    'status' => 'draft',
                    'gross_amount' => 0,
                    'deduction_amount' => 0,
                    'net_amount' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $slip_id = (int) $this->db->insert_id();
            }

            $items = $this->build_component_rows($period_id, $period, $user_id, $position_id);
            $gross = 0;
            $deduction = 0;

            $user_override_items = $override_payload['items'][(string) $user_id] ?? [];
            $user_custom_items = $override_payload['custom_items'][(string) $user_id] ?? [];
            $items = $this->apply_preview_item_overrides($items, $user_override_items);

            foreach ($items as $item) {
                $this->db->insert('payroll_slip_items', [
                    'payroll_slip_id' => $slip_id,
                    'payroll_component_id' => $item['payroll_component_id'],
                    'component_name_snapshot' => $item['component_name_snapshot'],
                    'component_type_snapshot' => $item['component_type_snapshot'],
                    'calculation_type_snapshot' => $item['calculation_type_snapshot'],
                    'qty' => $item['qty'],
                    'amount' => $item['amount'],
                    'is_manual_override' => !empty($item['is_preview_override']) ? 1 : 0,
                    'manual_note' => $item['manual_note'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                if ($item['component_type_snapshot'] === 'earning') {
                    $gross += (float) $item['amount'];
                } else {
                    $deduction += (float) $item['amount'];
                }
            }

            if (is_array($user_custom_items) && !empty($user_custom_items)) {
                foreach ($user_custom_items as $custom_item) {
                    if (!is_array($custom_item)) {
                        continue;
                    }

                    $custom_name = trim((string) ($custom_item['component_name'] ?? ''));
                    if ($custom_name === '') {
                        continue;
                    }

                    $custom_type = trim((string) ($custom_item['component_type'] ?? 'earning'));
                    if (!in_array($custom_type, ['earning', 'deduction'], true)) {
                        $custom_type = 'earning';
                    }

                    $custom_qty = isset($custom_item['qty']) ? (float) $custom_item['qty'] : 1;
                    if ($custom_qty < 0) {
                        $custom_qty = 0;
                    }

                    $custom_unit_amount = isset($custom_item['unit_amount']) ? (float) $custom_item['unit_amount'] : 0;
                    if ($custom_unit_amount < 0) {
                        $custom_unit_amount = 0;
                    }

                    $custom_unit = trim((string) ($custom_item['unit'] ?? 'periode'));
                    if ($custom_unit === '') {
                        $custom_unit = 'periode';
                    }

                    if (isset($custom_item['total']) && $custom_item['total'] !== '') {
                        $custom_amount = (float) $custom_item['total'];
                    } else {
                        $custom_amount = $custom_qty * $custom_unit_amount;
                    }
                    $custom_amount = max(0, round($custom_amount));

                    $this->db->insert('payroll_slip_items', [
                        'payroll_slip_id' => $slip_id,
                        'payroll_component_id' => 0,
                        'component_name_snapshot' => $custom_name,
                        'component_type_snapshot' => $custom_type,
                        'calculation_type_snapshot' => 'manual',
                        'qty' => $custom_qty,
                        'amount' => $custom_amount,
                        'is_manual_override' => 1,
                        'manual_note' => sprintf(
                            'Komponen tambahan preview (periode ini): nominal=%s qty=%s satuan=%s total=%s',
                            $custom_unit_amount,
                            $custom_qty,
                            $custom_unit,
                            $custom_amount
                        ),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    if ($custom_type === 'earning') {
                        $gross += $custom_amount;
                    } else {
                        $deduction += $custom_amount;
                    }
                }
            }

            $this->db->update('payroll_slips', [
                'gross_amount' => $gross,
                'deduction_amount' => $deduction,
                'net_amount' => $gross - $deduction,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $slip_id]);

            $generated++;
        }

        $this->session->set_flashdata('success', 'Generate payroll selesai untuk ' . $generated . ' karyawan.');
        redirect(base_url('payroll/run_payroll?period_id=' . $period_id));
    }

    public function slip_detail()
    {
        $this->require_payroll_admin();

        $slip_id = (int) $this->input->get('id');
        if ($slip_id <= 0) {
            redirect(base_url('payroll/run_payroll'));
            return;
        }

        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Detail Slip Payroll - ' . $this->template->title();
        $data['slip'] = $this->get_slip_detail($slip_id);

        if (empty($data['slip'])) {
            $this->session->set_flashdata('error', 'Slip payroll tidak ditemukan.');
            redirect(base_url('payroll/run_payroll'));
            return;
        }

        $data['items'] = $this->mymodel->selectWithQuery("SELECT psi.*, pc.can_be_edited_in_payroll
            FROM payroll_slip_items psi
            LEFT JOIN payroll_components pc ON pc.id = psi.payroll_component_id
            WHERE psi.payroll_slip_id = '{$slip_id}'
            ORDER BY psi.id ASC");

        $data['content'] = $this->load->view('payroll/slip_detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function slip_detail_modal()
    {
        $this->require_payroll_admin();

        $slip_id = (int) $this->input->get('id');
        if ($slip_id <= 0) {
            show_error('Slip payroll tidak ditemukan.', 404);
            return;
        }

        $data['slip'] = $this->get_slip_detail($slip_id);
        if (empty($data['slip'])) {
            show_error('Slip payroll tidak ditemukan.', 404);
            return;
        }

        $data['items'] = $this->mymodel->selectWithQuery("SELECT psi.*
            FROM payroll_slip_items psi
            WHERE psi.payroll_slip_id = '{$slip_id}'
            ORDER BY psi.id ASC");

        $this->output
            ->set_content_type('text/html')
            ->set_output($this->load->view('payroll/slip_detail_modal', $data, true));
    }

    public function slip_pdf()
    {
        $this->require_payroll_admin();

        $slip_id = (int) $this->input->get('id');
        if ($slip_id <= 0) {
            show_error('Slip payroll tidak ditemukan.', 404);
            return;
        }

        $slip = $this->get_slip_detail($slip_id);
        if (empty($slip)) {
            show_error('Slip payroll tidak ditemukan.', 404);
            return;
        }

        $items = $this->mymodel->selectWithQuery("SELECT *
            FROM payroll_slip_items
            WHERE payroll_slip_id = '{$slip_id}'
            ORDER BY id ASC");

        $this->render_slip_pdf($slip, $items);
    }

    public function update_slip_item()
    {
        $this->require_payroll_admin();

        $item_id = (int) $this->input->post('item_id');
        $amount = (float) $this->input->post('amount');
        $note = trim((string) $this->input->post('manual_note'));

        $item = $this->mymodel->selectWithQuery("SELECT psi.*, pc.can_be_edited_in_payroll
            FROM payroll_slip_items psi
            LEFT JOIN payroll_components pc ON pc.id = psi.payroll_component_id
            WHERE psi.id = '{$item_id}' LIMIT 1");

        if (empty($item)) {
            $this->session->set_flashdata('error', 'Item payroll tidak ditemukan.');
            redirect(base_url('payroll/run_payroll'));
            return;
        }

        $item = $item[0];
        if ((int) $item['can_be_edited_in_payroll'] !== 1) {
            $this->session->set_flashdata('error', 'Komponen ini tidak bisa diubah manual.');
            redirect(base_url('payroll/slip_detail?id=' . $item['payroll_slip_id']));
            return;
        }

        $this->db->update('payroll_slip_items', [
            'amount' => $amount,
            'is_manual_override' => 1,
            'manual_note' => $note !== '' ? $note : null,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $item_id]);

        $this->recalculate_slip((int) $item['payroll_slip_id']);

        $this->session->set_flashdata('success', 'Item payroll berhasil diperbarui.');
        redirect(base_url('payroll/slip_detail?id=' . $item['payroll_slip_id']));
    }

    public function set_slip_status()
    {
        $this->require_payroll_admin();

        $slip_id = (int) $this->input->post('slip_id');
        $status = trim((string) $this->input->post('status'));
        $allowed = ['draft', 'reviewed', 'finalized', 'paid'];

        if ($slip_id <= 0 || !in_array($status, $allowed, true)) {
            $this->session->set_flashdata('error', 'Status slip tidak valid.');
            redirect(base_url('payroll/run_payroll'));
            return;
        }

        $this->db->update('payroll_slips', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $slip_id]);

        $row = $this->mymodel->selectWithQuery("SELECT payroll_period_id FROM payroll_slips WHERE id = '{$slip_id}' LIMIT 1");
        $period_id = !empty($row) ? (int) $row[0]['payroll_period_id'] : 0;

        $this->session->set_flashdata('success', 'Status slip berhasil diubah.');
        redirect(base_url('payroll/run_payroll?period_id=' . $period_id));
    }

    public function update_slip_status_inline()
    {
        $this->require_payroll_admin();

        $slip_id = (int) $this->input->post('slip_id');
        $status = trim((string) $this->input->post('status'));
        $allowed = ['draft', 'reviewed', 'finalized', 'paid'];

        $response = [
            'success' => false,
            'message' => 'Status slip tidak valid.'
        ];

        if ($slip_id <= 0 || !in_array($status, $allowed, true)) {
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }

        $exists = $this->mymodel->selectWithQuery("SELECT id FROM payroll_slips WHERE id = '{$slip_id}' LIMIT 1");
        if (empty($exists)) {
            $response['message'] = 'Slip payroll tidak ditemukan.';
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }

        $this->db->update('payroll_slips', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $slip_id]);

        $response['success'] = true;
        $response['message'] = 'Status slip berhasil diubah.';
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }

    public function salary_structure()
    {
        $this->require_payroll_admin();

        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Struktur Gaji - ' . $this->template->title();
        $data['positions'] = $this->mymodel->selectWithQuery("SELECT p.id, p.name, p.level_id, ql.name as level_name
            FROM positions p
            LEFT JOIN quest_levels ql ON p.level_id = ql.id
            ORDER BY p.level_id ASC, p.name ASC");
        $data['components'] = $this->mymodel->selectWithQuery("SELECT * FROM payroll_components WHERE is_active = 1 ORDER BY name ASC");
        $data['rows'] = $this->mymodel->selectWithQuery("SELECT pssp.*
            FROM payroll_salary_structure_position pssp
            INNER JOIN payroll_components pc ON pc.id = pssp.payroll_component_id
            WHERE pc.is_active = 1
            ORDER BY pssp.position_id ASC, pssp.payroll_component_id ASC");

        $data['content'] = $this->load->view('payroll/salary_structure', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function save_salary_structure()
    {
        $this->require_payroll_admin();

        $position_id = (int) $this->input->post('position_id');
        $component_id = (int) $this->input->post('payroll_component_id');
        $amount = (float) $this->input->post('amount');
        $is_enabled = (int) $this->input->post('is_enabled');

        if ($position_id <= 0 || $component_id <= 0) {
            $this->session->set_flashdata('error', 'Data salary structure tidak valid.');
            redirect(base_url('payroll/salary_structure'));
            return;
        }

        $exists = $this->mymodel->selectWithQuery("SELECT id FROM payroll_salary_structure_position
            WHERE position_id = '{$position_id}' AND payroll_component_id = '{$component_id}' LIMIT 1");

        $payload = [
            'position_id' => $position_id,
            'payroll_component_id' => $component_id,
            'amount' => $amount,
            'is_enabled' => $is_enabled,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($exists)) {
            $this->db->update('payroll_salary_structure_position', $payload, ['id' => (int) $exists[0]['id']]);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('payroll_salary_structure_position', $payload);
        }

        $this->session->set_flashdata('success', 'Salary structure berhasil disimpan.');
        redirect(base_url('payroll/salary_structure'));
    }

    public function update_salary_structure_inline()
    {
        $this->require_payroll_admin();

        $id = (int) $this->input->post('id');
        $amount_input = $this->input->post('amount');
        $enabled_input = $this->input->post('is_enabled');

        $response = [
            'success' => false,
            'message' => 'Permintaan tidak valid.'
        ];

        if ($id <= 0) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
            return;
        }

        $exists = $this->mymodel->selectWithQuery("SELECT id FROM payroll_salary_structure_position WHERE id = '{$id}' LIMIT 1");
        if (empty($exists)) {
            $response['message'] = 'Data struktur gaji tidak ditemukan.';
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
            return;
        }

        $payload = [
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($amount_input !== null && $amount_input !== '') {
            $payload['amount'] = max(0, (float) $amount_input);
        }

        if ($enabled_input !== null && $enabled_input !== '') {
            $payload['is_enabled'] = ((int) $enabled_input === 1) ? 1 : 0;
        }

        if (count($payload) === 1) {
            $response['message'] = 'Tidak ada perubahan yang dikirim.';
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));
            return;
        }

        $this->db->update('payroll_salary_structure_position', $payload, ['id' => $id]);

        $response['success'] = true;
        $response['message'] = 'Perubahan berhasil disimpan.';
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function bulk_update_salary_structure()
    {
        $this->require_payroll_admin();

        $selected_ids = $this->parse_selected_ids($this->input->post('selected_ids'));
        if (empty($selected_ids)) {
            $this->session->set_flashdata('error', 'Pilih minimal 1 data struktur gaji.');
            redirect(base_url('payroll/salary_structure'));
            return;
        }

        $amount_input = trim((string) $this->input->post('amount'));
        $enabled_input = $this->input->post('is_enabled');

        $payload = [
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($amount_input !== '') {
            $payload['amount'] = (float) $amount_input;
        }

        if ($enabled_input !== '' && $enabled_input !== null) {
            $payload['is_enabled'] = ((int) $enabled_input === 1) ? 1 : 0;
        }

        if (count($payload) === 1) {
            $this->session->set_flashdata('error', 'Isi minimal satu field untuk bulk edit.');
            redirect(base_url('payroll/salary_structure'));
            return;
        }

        $this->db->where_in('id', $selected_ids);
        $this->db->update('payroll_salary_structure_position', $payload);

        $this->session->set_flashdata('success', 'Bulk edit berhasil untuk ' . count($selected_ids) . ' data.');
        redirect(base_url('payroll/salary_structure'));
    }

    public function bulk_delete_salary_structure()
    {
        $this->require_payroll_admin();

        $selected_ids = $this->parse_selected_ids($this->input->post('selected_ids'));
        if (empty($selected_ids)) {
            $this->session->set_flashdata('error', 'Pilih minimal 1 data struktur gaji.');
            redirect(base_url('payroll/salary_structure'));
            return;
        }

        $this->db->where_in('id', $selected_ids);
        $this->db->delete('payroll_salary_structure_position');

        $this->session->set_flashdata('success', 'Berhasil menghapus ' . count($selected_ids) . ' data struktur gaji.');
        redirect(base_url('payroll/salary_structure'));
    }

    public function employee_salary_setup()
    {
        $this->require_payroll_admin();
        $this->session->set_flashdata('error', 'Halaman Override Gaji Karyawan sudah tidak digunakan.');
        redirect(base_url('payroll/salary_structure'));
    }

    public function save_employee_override()
    {
        $this->require_payroll_admin();
        $this->session->set_flashdata('error', 'Fitur Override Gaji Karyawan sudah tidak digunakan.');
        redirect(base_url('payroll/salary_structure'));
    }

    public function master_components()
    {
        $this->require_payroll_admin();

        $data['user'] = $_SESSION['user'];
        $data['title'] = 'Master Komponen - ' . $this->template->title();
        $data['components'] = $this->mymodel->selectWithQuery("SELECT * FROM payroll_components ORDER BY id DESC");

        $data['content'] = $this->load->view('payroll/master_components', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function save_component()
    {
        $this->require_payroll_admin();

        $id = (int) $this->input->post('id');
        $payload = [
            'name' => trim((string) $this->input->post('name')),
            'component_type' => trim((string) $this->input->post('component_type')),
            'calculation_type' => trim((string) $this->input->post('calculation_type')),
            'day_basis' => trim((string) $this->input->post('day_basis')),
            'default_amount' => (float) $this->input->post('default_amount'),
            'is_active' => (int) $this->input->post('is_active'),
            'can_be_edited_in_payroll' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($payload['name'] === '' || !in_array($payload['component_type'], ['earning', 'deduction'], true)) {
            $this->session->set_flashdata('error', 'Data komponen payroll tidak valid.');
            redirect(base_url('payroll/master_components'));
            return;
        }

        $allowed_calc = ['fixed', 'per_day', 'manual', 'future_kpi_based'];
        if (!in_array($payload['calculation_type'], $allowed_calc, true)) {
            $payload['calculation_type'] = 'fixed';
        }

        $allowed_day_basis = ['working_day', 'wfo_day', 'attendance_day', 'none'];
        if (!in_array($payload['day_basis'], $allowed_day_basis, true)) {
            $payload['day_basis'] = 'none';
        }

        if ($id > 0) {
            $this->db->update('payroll_components', $payload, ['id' => $id]);
            $this->session->set_flashdata('success', 'Komponen payroll berhasil diperbarui.');
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('payroll_components', $payload);
            $this->session->set_flashdata('success', 'Komponen payroll berhasil ditambahkan.');
        }

        redirect(base_url('payroll/master_components'));
    }

    public function update_component_inline()
    {
        $this->require_payroll_admin();

        $id = (int) $this->input->post('id');
        $payload = [
            'name' => trim((string) $this->input->post('name')),
            'component_type' => trim((string) $this->input->post('component_type')),
            'calculation_type' => trim((string) $this->input->post('calculation_type')),
            'day_basis' => trim((string) $this->input->post('day_basis')),
            'default_amount' => (float) $this->input->post('default_amount'),
            'is_active' => (int) $this->input->post('is_active'),
            'can_be_edited_in_payroll' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $response = [
            'success' => false,
            'message' => 'Data komponen tidak valid.'
        ];

        if ($payload['name'] === '' || !in_array($payload['component_type'], ['earning', 'deduction'], true)) {
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }

        $allowed_calc = ['fixed', 'per_day', 'manual', 'future_kpi_based'];
        if (!in_array($payload['calculation_type'], $allowed_calc, true)) {
            $payload['calculation_type'] = 'fixed';
        }

        $allowed_day_basis = ['working_day', 'wfo_day', 'attendance_day', 'none'];
        if (!in_array($payload['day_basis'], $allowed_day_basis, true)) {
            $payload['day_basis'] = 'none';
        }

        $payload['default_amount'] = max(0, $payload['default_amount']);
        $payload['is_active'] = $payload['is_active'] === 1 ? 1 : 0;

        if ($id > 0) {
            $exists = $this->mymodel->selectWithQuery("SELECT id FROM payroll_components WHERE id = '{$id}' LIMIT 1");
            if (empty($exists)) {
                $response['message'] = 'Komponen tidak ditemukan.';
                $this->output->set_content_type('application/json')->set_output(json_encode($response));
                return;
            }
            $this->db->update('payroll_components', $payload, ['id' => $id]);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('payroll_components', $payload);
            $id = (int) $this->db->insert_id();
        }

        $response['success'] = true;
        $response['message'] = 'Komponen berhasil disimpan.';
        $response['id'] = $id;
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }

    public function my_payroll()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = (int) $data['user']['id'];

        $data['title'] = 'Payroll Saya - ' . $this->template->title();
        $data['slips'] = $this->mymodel->selectWithQuery("SELECT ps.*, pp.period_label, pp.pay_date
            FROM payroll_slips ps
            LEFT JOIN payroll_periods pp ON pp.id = ps.payroll_period_id
            WHERE ps.user_id = '{$user_id}'
            ORDER BY pp.period_start DESC");

        $data['content'] = $this->load->view('payroll/my_payroll', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function my_payroll_detail()
    {
        $data['user'] = $_SESSION['user'];
        $user_id = (int) $data['user']['id'];
        $slip_id = (int) $this->input->get('id');

        $data['title'] = 'Detail Payroll Saya - ' . $this->template->title();

        $slip = $this->mymodel->selectWithQuery("SELECT ps.*, pp.period_label, pp.pay_date
            FROM payroll_slips ps
            LEFT JOIN payroll_periods pp ON pp.id = ps.payroll_period_id
            WHERE ps.id = '{$slip_id}' AND ps.user_id = '{$user_id}'
            LIMIT 1");

        if (empty($slip)) {
            $this->session->set_flashdata('error', 'Data payroll tidak ditemukan.');
            redirect(base_url('payroll/my_payroll'));
            return;
        }

        $data['slip'] = $slip[0];
        $data['items'] = $this->mymodel->selectWithQuery("SELECT * FROM payroll_slip_items
            WHERE payroll_slip_id = '{$slip_id}' ORDER BY id ASC");

        $data['content'] = $this->load->view('payroll/my_payroll_detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function my_payroll_pdf()
    {
        $user_id = (int) ($_SESSION['user']['id'] ?? 0);
        $slip_id = (int) $this->input->get('id');

        if ($slip_id <= 0 || $user_id <= 0) {
            show_error('Slip payroll tidak ditemukan.', 404);
            return;
        }

        $slip = $this->get_slip_detail($slip_id);
        if (empty($slip) || (int) $slip['user_id'] !== $user_id) {
            show_error('Slip payroll tidak ditemukan.', 404);
            return;
        }

        $items = $this->mymodel->selectWithQuery("SELECT *
            FROM payroll_slip_items
            WHERE payroll_slip_id = '{$slip_id}'
            ORDER BY id ASC");

        $this->render_slip_pdf($slip, $items);
    }

    private function build_component_rows($period_id, $period, $user_id, $position_id)
    {
        $components = $this->mymodel->selectWithQuery("SELECT * FROM payroll_components WHERE is_active = 1 ORDER BY id ASC");
        $structure_rows = $this->mymodel->selectWithQuery("SELECT * FROM payroll_salary_structure_position
            WHERE position_id = '{$position_id}'");
        $override_rows = $this->mymodel->selectWithQuery("SELECT * FROM payroll_employee_component_overrides
            WHERE user_id = '{$user_id}'");
        $attendance_rows = $this->mymodel->selectWithQuery("SELECT * FROM payroll_attendance_summary
            WHERE payroll_period_id = '{$period_id}' AND user_id = '{$user_id}' LIMIT 1");

        $structure_map = [];
        foreach ($structure_rows as $row) {
            $structure_map[(int) $row['payroll_component_id']] = $row;
        }

        $override_map = [];
        foreach ($override_rows as $row) {
            $override_map[(int) $row['payroll_component_id']] = $row;
        }

        $attendance = !empty($attendance_rows) ? $attendance_rows[0] : [
            'working_days' => 0,
            'attendance_days' => 0,
            'wfo_days' => 0
        ];

        $items = [];
        foreach ($components as $component) {
            $component_id = (int) $component['id'];
            $enabled = false;
            $amount_base = (float) $component['default_amount'];

            if (isset($structure_map[$component_id])) {
                $enabled = (int) $structure_map[$component_id]['is_enabled'] === 1;
                $amount_base = (float) $structure_map[$component_id]['amount'];
            }

            if (isset($override_map[$component_id])) {
                $enabled = (int) $override_map[$component_id]['is_enabled'] === 1;
                $amount_base = (float) $override_map[$component_id]['amount'];
            }

            if (!$enabled) {
                continue;
            }

            $qty = 1;
            $amount = $amount_base;
            $calc = $component['calculation_type'];
            $unit_label = 'bulan';
            $formula_badge = 'Bulanan';
            $unit_amount = $amount_base;

            if ($calc === 'per_day') {
                $basis = $component['day_basis'];
                if ($basis === 'wfo_day') {
                    $qty = (int) $attendance['wfo_days'];
                    $unit_label = 'hari WFO';
                    $formula_badge = 'Per Hari WFO';
                } elseif ($basis === 'attendance_day') {
                    $qty = (int) $attendance['attendance_days'];
                    $unit_label = 'hari hadir';
                    $formula_badge = 'Per Hari Hadir';
                } else {
                    $qty = (int) $attendance['working_days'];
                    $unit_label = 'hari kerja';
                    $formula_badge = 'Per Hari Kerja';
                }
                $unit_amount = $amount_base;
                $amount = $unit_amount * $qty;
            } elseif ($calc === 'manual' || $calc === 'future_kpi_based') {
                $qty = 1;
                $unit_amount = 0;
                $amount = 0;
                $unit_label = 'periode';
                $formula_badge = $calc === 'manual' ? 'Input Manual' : 'Basis KPI (Nanti)';
            }

            $items[] = [
                'payroll_component_id' => $component_id,
                'component_name_snapshot' => $component['name'],
                'component_type_snapshot' => $component['component_type'],
                'calculation_type_snapshot' => $calc,
                'qty' => $qty,
                'unit_label' => $unit_label,
                'unit_amount' => $unit_amount,
                'formula_badge' => $formula_badge,
                'amount' => $amount,
                'is_preview_override' => 0,
                'manual_note' => null
            ];
        }

        return $items;
    }

    private function apply_preview_item_overrides($items, $user_override_items)
    {
        if (!is_array($user_override_items) || empty($user_override_items)) {
            return $items;
        }

        foreach ($items as &$item) {
            $component_id = (string) $item['payroll_component_id'];
            if (!isset($user_override_items[$component_id]) || !is_array($user_override_items[$component_id])) {
                continue;
            }

            $override = $user_override_items[$component_id];
            $old_qty = (float) $item['qty'];
            $old_unit_amount = (float) $item['unit_amount'];
            $old_amount = (float) $item['amount'];
            $old_unit_label = (string) $item['unit_label'];

            $new_qty = isset($override['qty']) && $override['qty'] !== '' ? (float) $override['qty'] : $old_qty;
            $new_unit_amount = isset($override['unit_amount']) && $override['unit_amount'] !== '' ? (float) $override['unit_amount'] : $old_unit_amount;
            $new_unit_label = isset($override['unit']) && trim((string) $override['unit']) !== '' ? trim((string) $override['unit']) : $old_unit_label;

            if (isset($override['total']) && $override['total'] !== '') {
                $new_amount = (float) $override['total'];
            } else {
                $new_amount = $new_qty * $new_unit_amount;
            }

            $changed = (
                abs($new_qty - $old_qty) > 0.0001 ||
                abs($new_unit_amount - $old_unit_amount) > 0.0001 ||
                abs($new_amount - $old_amount) > 0.0001 ||
                $new_unit_label !== $old_unit_label
            );

            $item['qty'] = $new_qty;
            $item['unit_amount'] = $new_unit_amount;
            $item['unit_label'] = $new_unit_label;
            $item['amount'] = $new_amount;

            if ($changed) {
                $item['is_preview_override'] = 1;
                $item['manual_note'] = sprintf(
                    'Override preview: nominal=%s qty=%s satuan=%s total=%s',
                    $new_unit_amount,
                    $new_qty,
                    $new_unit_label,
                    $new_amount
                );
            }
        }
        unset($item);

        return $items;
    }

    private function recalculate_slip($slip_id)
    {
        $items = $this->mymodel->selectWithQuery("SELECT component_type_snapshot, amount
            FROM payroll_slip_items WHERE payroll_slip_id = '{$slip_id}'");

        $gross = 0;
        $deduction = 0;
        foreach ($items as $item) {
            if ($item['component_type_snapshot'] === 'earning') {
                $gross += (float) $item['amount'];
            } else {
                $deduction += (float) $item['amount'];
            }
        }

        $this->db->update('payroll_slips', [
            'gross_amount' => $gross,
            'deduction_amount' => $deduction,
            'net_amount' => $gross - $deduction,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $slip_id]);
    }

    private function get_slip_detail($slip_id)
    {
        $row = $this->mymodel->selectWithQuery("SELECT ps.*, pp.period_label, pp.period_start, pp.period_end,
            pp.pay_date, pp.status AS period_status, u.full_name, up.bank_account_number, up.bank_name,
            COALESCE(p.name, '-') AS position_name
            FROM payroll_slips ps
            LEFT JOIN payroll_periods pp ON pp.id = ps.payroll_period_id
            LEFT JOIN user u ON u.id = ps.user_id
            LEFT JOIN user_profile up ON up.user_id = u.id
            LEFT JOIN positions p ON p.id = up.position_id
            WHERE ps.id = '{$slip_id}' LIMIT 1");

        return !empty($row) ? $row[0] : null;
    }

    private function render_slip_pdf($slip, $items)
    {
        $earning_items = [];
        $deduction_items = [];

        foreach ($items as $item) {
            if (($item['component_type_snapshot'] ?? '') === 'deduction') {
                $deduction_items[] = $item;
                continue;
            }

            $earning_items[] = $item;
        }

        $view_data = [
            'slip' => $slip,
            'earning_items' => $earning_items,
            'deduction_items' => $deduction_items,
            'generated_at' => date('d M Y H:i'),
        ];

        $html = $this->load->view('payroll/slip_pdf', $view_data, true);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $employee_name = trim((string) ($slip['full_name'] ?? 'pegawai'));
        $period_label = trim((string) ($slip['period_label'] ?? date('F Y')));
        $safe_employee_name = preg_replace('/[^A-Za-z0-9_-]+/', '-', $employee_name);
        $safe_period_label = preg_replace('/[^A-Za-z0-9_-]+/', '-', $period_label);
        $filename = 'slip-gaji-' . trim($safe_employee_name, '-') . '-' . trim($safe_period_label, '-') . '.pdf';

        $dompdf->stream($filename, ['Attachment' => true]);
    }

    private function is_payroll_admin()
    {
        $role = (string) ($_SESSION['user']['role'] ?? '');
        return in_array($role, ['1', '2'], true);
    }

    private function parse_selected_ids($selected_ids_raw)
    {
        $raw = trim((string) $selected_ids_raw);
        if ($raw === '') {
            return [];
        }

        $parts = explode(',', $raw);
        $ids = [];
        foreach ($parts as $part) {
            $id = (int) trim($part);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function require_payroll_admin()
    {
        if (!$this->is_payroll_admin()) {
            redirect(base_url('payroll/my_payroll'));
            exit;
        }
    }
}
