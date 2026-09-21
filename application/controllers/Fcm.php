<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Fcm extends CI_Controller
{
    public function daftar()
    {
        header('Content-Type: application/json; charset=utf-8');

        $uid = isset($_SESSION['user']['id']) ? intval($_SESSION['user']['id']) : 0;
        if (!$uid) {
            echo json_encode(['status' => false, 'msg' => 'Belum login']); return;
        }

        $token = trim((string) $this->input->post('token'));
        if ($token === '') {
            echo json_encode(['status' => false, 'msg' => 'Token kosong']); return;
        }

        $data = [
            'user_id'      => $uid,
            'token'        => substr($token, 0, 255),
            'device_os'    => substr((string) $this->input->post('device_os'), 0, 30),
            'device_model' => substr((string) $this->input->post('device_model'), 0, 80),
            'is_active'    => 1,
            'last_seen_at' => date('Y-m-d H:i:s'),
        ];

        $ada = $this->db->where('token', $data['token'])->get('fcm_tokens')->row_array();
        if ($ada) {
            $this->db->where('id', $ada['id'])->update('fcm_tokens', $data);
        } else {
            $this->db->insert('fcm_tokens', $data);
        }

        echo json_encode(['status' => true, 'msg' => 'Token terdaftar']);
    }

    public function hapus()
    {
        header('Content-Type: application/json; charset=utf-8');
        $token = trim((string) $this->input->post('token'));
        if ($token !== '') {
            $this->db->where('token', $token)->update('fcm_tokens', ['is_active' => 0]);
        }
        echo json_encode(['status' => true]);
    }
}
