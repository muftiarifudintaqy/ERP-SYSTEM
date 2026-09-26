<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'core/BaseController.php';

class Kinerja extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('permission');
        $this->load->library('template');
        $this->load->library('PushSender');
    }

    private function _uid() { return isset($_SESSION['user']['id']) ? intval($_SESSION['user']['id']) : 0; }
    private function _kelola() { return $this->_super_role() || $this->permission->check_permission($this->_uid(), 'kinerja', 'edit'); }

    private function _super_role($uid = 0)
    {
        $uid = $uid ?: $this->_uid();
        if (!$uid) return false;
        $r = $this->mymodel->selectWithQuery("SELECT ur.id FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=".intval($uid)." AND r.name IN ('developer','super_admin','head_admin','owner') LIMIT 1");
        return !empty($r);
    }

    private function _is_team_admin($tid, $uid = 0)
    {
        $uid = $uid ?: $this->_uid();
        if (!$uid) return false;
        $r = $this->mymodel->selectWithQuery("SELECT id FROM team_members WHERE team_id=".intval($tid)." AND user_id=".intval($uid)." AND role_in_team='admin' LIMIT 1");
        return !empty($r);
    }

    private function _tim_saya()
    {
        $uid = $this->_uid();
        if ($this->_kelola()) {
            return $this->mymodel->selectWithQuery("SELECT * FROM teams WHERE is_active=1 ORDER BY name");
        }
        return $this->mymodel->selectWithQuery("SELECT t.* FROM teams t JOIN team_members m ON m.team_id=t.id WHERE m.user_id=$uid AND t.is_active=1 ORDER BY t.name");
    }

    private function _boleh_tim($team_id)
    {
        if ($this->_kelola()) return true;
        $uid = $this->_uid(); $tid = intval($team_id);
        $c = $this->mymodel->selectWithQuery("SELECT id FROM team_members WHERE team_id=$tid AND user_id=$uid LIMIT 1");
        return !empty($c);
    }

    /* HTTPS_URL_TETAP: domain aman untuk link notifikasi, tidak ikut base_url() dinamis */
    private function _url_aman($path)
    {
        return 'https://72-61-215-30.sslip.io/' . ltrim($path, '/');
    }

    private function _notify_team($team_id, $type, $title, $body, $ref_url, $exclude_uid = null, $extra_id = null)
    {
        $tid = intval($team_id);
        $anggota = $this->mymodel->selectWithQuery("SELECT user_id FROM team_members WHERE team_id=$tid");
        if (empty($anggota)) return;
        foreach ($anggota as $a) {
            $uid = intval($a['user_id']);
            if ($exclude_uid !== null && $uid === intval($exclude_uid)) continue;
            $this->db->insert('notifications', array(
                'team_id'     => $tid,
                'user_id'     => $uid,
                'type'        => 'info',
                'category'    => 'team',
                'subcategory' => $type,
                'title'       => substr($title, 0, 190),
                'message'     => $body,
                'ref_url'     => $ref_url,
                'extra_id'    => $extra_id,
                'is_read'     => 0,
                'created_at'  => date('Y-m-d H:i:s')
            ));
            try {
                $this->pushsender->kirim_ke_user($uid, $title, $body, $ref_url);
            } catch (\Throwable $e) {
                log_message('error', 'push gagal: ' . $e->getMessage());
            }
            try {
                $this->load->library('FcmSender');
                $this->fcmsender->kirim($uid, $title, $body, $ref_url, $type);
            } catch (\Throwable $e) {
                log_message('error', 'fcm gagal: ' . $e->getMessage());
            }
        }
    }

    public function push_subscribe()
    {
        $uid = $this->_uid();
        // Hanya membaca sesi: kunci dilepas supaya permintaan lain dari user
        // yang sama tidak ikut antre menunggu (sesi CI memakai berkas + kunci).
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
        @file_put_contents('/var/log/apache2/push-debug.log', date('H:i:s')
            . ' uid=' . var_export($uid, true)
            . ' endpoint=' . substr((string)$this->input->post('endpoint'), 0, 45)
            . ' p256dh=' . (strlen((string)$this->input->post('p256dh')))
            . ' auth=' . (strlen((string)$this->input->post('auth')))
            . PHP_EOL, FILE_APPEND);
        if (!$uid) { echo json_encode(array('status'=>false,'msg'=>'Belum login')); return; }
        $endpoint = $this->input->post('endpoint');
        $p256dh   = $this->input->post('p256dh');
        $auth     = $this->input->post('auth');
        if (!$endpoint || !$p256dh || !$auth) {
            echo json_encode(array('status'=>false,'msg'=>'Data subscription tidak lengkap'));
            return;
        }
        $ada = $this->mymodel->selectWithQuery("SELECT id FROM push_subscriptions WHERE endpoint='".$this->db->escape_str($endpoint)."' LIMIT 1");
        if (!empty($ada)) {
            $this->db->where('endpoint', $endpoint)->update('push_subscriptions', array(
                'user_id'    => $uid,
                'p256dh'     => $p256dh,
                'auth'       => $auth,
                'user_agent' => substr($this->input->user_agent(), 0, 250),
            ));
        } else {
            // hapus duplikat subscription lama dari perangkat yang sama
            $ua = substr($this->input->user_agent(), 0, 250);
            $this->db->where('user_id', $uid)->where('user_agent', $ua)->delete('push_subscriptions');
            $this->db->insert('push_subscriptions', array(
                'user_id'    => $uid,
                'endpoint'   => $endpoint,
                'p256dh'     => $p256dh,
                'auth'       => $auth,
                'user_agent' => substr($this->input->user_agent(), 0, 250),
                'created_at' => date('Y-m-d H:i:s'),
            ));
        }
        echo json_encode(array('status'=>true));
    }

    public function push_unsubscribe()
    {
        $endpoint = $this->input->post('endpoint');
        if ($endpoint) {
            $this->db->where('endpoint', $endpoint)->delete('push_subscriptions');
        }
        echo json_encode(array('status'=>true));
    }

    public function push_vapid_key()
    {
        if (!defined('VAPID_PUBLIC_KEY')) {
            require_once APPPATH . 'config/vapid.php';
        }
        echo json_encode(array('status'=>true, 'key'=>VAPID_PUBLIC_KEY));
    }

    public function index()
    {
        $tim = $this->_tim_saya();
        $aktif = intval($this->input->get('team'));
        if (!$aktif && !empty($tim)) $aktif = intval($tim[0]['id']);

        $lists = array(); $tasks = array(); $anggota = array();
        if ($aktif && $this->_boleh_tim($aktif)) {
            $lists = $this->mymodel->selectWithQuery("SELECT * FROM task_lists WHERE team_id=$aktif ORDER BY sort_order, id");
            $tasks = $this->mymodel->selectWithQuery("
                SELECT t.*, u.full_name AS assignee_name,
                  (SELECT a.file_path FROM task_attachments a
                    WHERE a.task_id = t.id
                      AND LOWER(a.file_type) IN ('jpg','jpeg','png','gif','webp')
                    ORDER BY a.id ASC LIMIT 1) AS cover_path,
                  (SELECT COUNT(*) FROM task_attachments a2 WHERE a2.task_id = t.id) AS jml_bukti,
                  (SELECT COUNT(*) FROM task_checklist c WHERE c.task_id = t.id) AS ck_total,
                  (SELECT COUNT(*) FROM task_checklist c2 WHERE c2.task_id = t.id AND c2.is_done = 1) AS ck_selesai
                FROM tasks t
                LEFT JOIN user u ON u.id = t.assignee_id
                WHERE t.team_id = $aktif
                ORDER BY t.sort_order, t.id");
            $anggota = $this->mymodel->selectWithQuery("SELECT u.id, u.full_name FROM team_members m JOIN user u ON u.id=m.user_id WHERE m.team_id=$aktif ORDER BY u.full_name");
        } else { $aktif = 0; }

        $data['title']      = 'Value of Kinerja';
        $data['teams']      = $tim;
        $data['team_id']    = $aktif;
        $data['lists']      = $lists;
        $data['tasks']      = $tasks;
        $data['anggota']    = $anggota;
        $data['boleh']      = $this->_kelola();
        $data['semua_user'] = $this->mymodel->selectWithQuery("SELECT id, full_name FROM user WHERE status='Aktif' ORDER BY full_name");
        $data['content']    = $this->load->view('kinerja/board', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function team_members_list()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->get('team_id'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $rows = $this->mymodel->selectWithQuery("SELECT user_id FROM team_members WHERE team_id=$tid");
        echo json_encode(array('status'=>true,'data'=>$rows));
    }

    public function update_team()
    {
        header('Content-Type: application/json');
        if (!$this->_kelola()) { echo json_encode(array('status'=>false,'msg'=>'Tidak punya izin.')); return; }
        $tid = intval($this->input->post('id'));
        if (!$tid) { echo json_encode(array('status'=>false,'msg'=>'Tim tidak valid.')); return; }
        $nama = trim($this->input->post('name'));
        if ($nama === '') { echo json_encode(array('status'=>false,'msg'=>'Nama tim wajib diisi.')); return; }

        $this->db->where('id', $tid)->update('teams', array(
            'name' => $nama,
            'description' => $this->input->post('description'),
        ));

        $baru = $this->input->post('members');
        $baru = is_array($baru) ? array_map('intval', $baru) : array();
        $lama = $this->mymodel->selectWithQuery("SELECT user_id FROM team_members WHERE team_id=$tid");
        $lamaIds = array_map(function($r){ return intval($r['user_id']); }, $lama);

        foreach ($baru as $uid) {
            if (!in_array($uid, $lamaIds)) {
                $this->db->query("INSERT IGNORE INTO team_members (team_id,user_id) VALUES ($tid, $uid)");
            }
        }
        foreach ($lamaIds as $uid) {
            if (!in_array($uid, $baru)) {
                $this->db->query("DELETE FROM team_members WHERE team_id=$tid AND user_id=$uid");
            }
        }

        echo json_encode(array('status'=>true,'msg'=>'Tim diperbarui.','id'=>$tid));
    }

    public function delete_team()
    {
        header('Content-Type: application/json');
        if (!$this->_kelola()) { echo json_encode(array('status'=>false,'msg'=>'Tidak punya izin.')); return; }
        $tid = intval($this->input->post('id'));
        if (!$tid) { echo json_encode(array('status'=>false,'msg'=>'Tim tidak valid.')); return; }
        $cek = $this->mymodel->selectWithQuery("SELECT id FROM teams WHERE id=$tid LIMIT 1");
        if (empty($cek)) { echo json_encode(array('status'=>false,'msg'=>'Tim tidak ditemukan.')); return; }

        $lampiranTugas = $this->mymodel->selectWithQuery("SELECT ta.file_path FROM task_attachments ta JOIN tasks t ON t.id=ta.task_id WHERE t.team_id=$tid");
        foreach ($lampiranTugas as $lf) { if (!empty($lf['file_path']) && file_exists(FCPATH.$lf['file_path'])) @unlink(FCPATH.$lf['file_path']); }
        $lampiranAnn = $this->mymodel->selectWithQuery("SELECT af.file_path FROM announcement_files af JOIN team_announcements a ON a.id=af.ann_id WHERE a.team_id=$tid");
        foreach ($lampiranAnn as $lf) { if (!empty($lf['file_path']) && file_exists(FCPATH.$lf['file_path'])) @unlink(FCPATH.$lf['file_path']); }
        $lampiranDok = $this->mymodel->selectWithQuery("SELECT file_path FROM team_files WHERE team_id=$tid");
        foreach ($lampiranDok as $lf) { if (!empty($lf['file_path']) && file_exists(FCPATH.$lf['file_path'])) @unlink(FCPATH.$lf['file_path']); }

        $this->db->query("DELETE tc FROM task_checklist tc JOIN tasks t ON t.id=tc.task_id WHERE t.team_id=$tid");
        $this->db->query("DELETE ta FROM task_attachments ta JOIN tasks t ON t.id=ta.task_id WHERE t.team_id=$tid");
        $this->db->query("DELETE FROM tasks WHERE team_id=$tid");
        $this->db->query("DELETE FROM task_lists WHERE team_id=$tid");

        $this->db->query("DELETE ac FROM announcement_comments ac JOIN team_announcements a ON a.id=ac.ann_id WHERE a.team_id=$tid");
        $this->db->query("DELETE af FROM announcement_files af JOIN team_announcements a ON a.id=af.ann_id WHERE a.team_id=$tid");
        $this->db->query("DELETE ar FROM announcement_reactions ar JOIN team_announcements a ON a.id=ar.ann_id WHERE a.team_id=$tid");
        $this->db->query("DELETE av FROM announcement_views av JOIN team_announcements a ON a.id=av.announcement_id WHERE a.team_id=$tid");
        $this->db->query("DELETE FROM team_announcements WHERE team_id=$tid");

        $this->db->query("DELETE dc FROM document_comments dc JOIN team_files f ON f.id=dc.file_id WHERE f.team_id=$tid");
        $this->db->query("DELETE dr FROM document_reactions dr JOIN team_files f ON f.id=dr.file_id WHERE f.team_id=$tid");
        $this->db->query("DELETE FROM team_files WHERE team_id=$tid");

        $this->db->query("DELETE FROM team_chat_messages WHERE team_id=$tid");
        $this->db->query("DELETE FROM team_chat_reads WHERE team_id=$tid");
        $this->db->query("DELETE FROM team_events WHERE team_id=$tid");
        $this->db->query("DELETE FROM kinerja_pertanyaan WHERE team_id=$tid");
        $this->db->query("DELETE FROM notifications WHERE team_id=$tid");
        $this->db->query("DELETE FROM team_members WHERE team_id=$tid");
        $this->db->query("DELETE FROM teams WHERE id=$tid");

        echo json_encode(array('status'=>true,'msg'=>'Tim dihapus.'));
    }

    public function alias_settings()
    {
        if (!$this->_kelola()) { show_404(); return; }
        $data['back_team'] = intval($this->input->get('team'));
        $rows = $this->mymodel->selectWithQuery("
            SELECT u.id, u.full_name, r.name AS role_name, a.alias_name
            FROM user u
            JOIN user_roles ur ON ur.user_id = u.id
            JOIN roles r ON r.id = ur.role_id
            LEFT JOIN user_chat_alias a ON a.user_id = u.id
            WHERE r.name IN ('developer','super_admin')
            GROUP BY u.id
            ORDER BY u.full_name
        ");
        $data['title']   = 'Nama Samaran Admin';
        $data['baris']   = $rows;
        $data['content'] = $this->load->view('kinerja/alias_settings', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function save_alias()
    {
        header('Content-Type: application/json');
        if (!$this->_kelola()) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $uid2 = intval($this->input->post('user_id'));
        $nama = trim($this->input->post('alias_name'));
        if (!$uid2) { echo json_encode(array('status'=>false,'msg'=>'User tidak valid.')); return; }
        $ada = $this->mymodel->selectWithQuery("SELECT user_id FROM user_chat_alias WHERE user_id=$uid2 LIMIT 1");
        if (!empty($ada)) {
            $this->db->where('user_id', $uid2)->update('user_chat_alias', array('alias_name'=>$nama));
        } else {
            $this->db->insert('user_chat_alias', array('user_id'=>$uid2,'alias_name'=>$nama));
        }
        echo json_encode(array('status'=>true,'msg'=>'Tersimpan.'));
    }

    public function save_team()
    {
        header('Content-Type: application/json');
        if (!$this->_kelola()) { echo json_encode(array('status'=>false,'msg'=>'Tidak punya izin.')); return; }
        $nama = trim($this->input->post('name'));
        if ($nama === '') { echo json_encode(array('status'=>false,'msg'=>'Nama tim wajib diisi.')); return; }

        $this->db->insert('teams', array(
            'name' => $nama,
            'description' => $this->input->post('description'),
            'color' => $this->input->post('color') ? $this->input->post('color') : '#6E4FA8',
            'created_by' => $this->_uid(),
            'created_at' => date('Y-m-d H:i:s')
        ));
        $tid = $this->db->insert_id();

        $baku = array(
            array('To Do List', '#94a3b8', 'todo', 1),
            array('Dikerjakan', '#3b82f6', 'doing', 2),
            array('Selesai', '#22c55e', 'done', 3),
            array('Batal', '#ef4444', 'cancel', 4)
        );
        foreach ($baku as $b) {
            $this->db->insert('task_lists', array('team_id'=>$tid,'name'=>$b[0],'color'=>$b[1],'kind'=>$b[2],'sort_order'=>$b[3]));
        }

        $anggota = $this->input->post('members');
        if (is_array($anggota)) {
            foreach ($anggota as $u) {
                $this->db->query("INSERT IGNORE INTO team_members (team_id,user_id) VALUES ($tid, " . intval($u) . ")");
            }
        }
        echo json_encode(array('status'=>true,'msg'=>'Tim dibuat.','id'=>$tid));
    }

    public function save_task()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->post('team_id'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false,'msg'=>'Tidak punya akses tim ini.')); return; }
        $judul = trim($this->input->post('title'));
        if ($judul === '') { echo json_encode(array('status'=>false,'msg'=>'Nama tugas wajib diisi.')); return; }

        $id  = intval($this->input->post('id'));
        $due = $this->input->post('due_date');
        $d = array(
            'team_id'     => $tid,
            'list_id'     => intval($this->input->post('list_id')),
            'title'       => $judul,
            'description' => $this->input->post('description'),
            'assignee_id' => intval($this->input->post('assignee_id')) ? intval($this->input->post('assignee_id')) : null,
            'due_date'    => $due ? $due : null,
            'label'       => $this->input->post('label'),
            'drive_link'  => $this->input->post('drive_link')
        );

        if ($id > 0) {
            $this->db->update('tasks', $d, array('id'=>$id));
        } else {
            $d['created_by'] = $this->_uid();
            $d['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('tasks', $d);
            $id = $this->db->insert_id();

            $pembuat = $this->mymodel->selectWithQuery("SELECT full_name FROM user WHERE id=".intval($this->_uid())." LIMIT 1");
            $namaPembuat = !empty($pembuat) ? $pembuat[0]['full_name'] : 'Seseorang';
            $this->_notify_team($tid, 'task_new', 'Tugas baru: '.$judul, 'Dibuat oleh '.$namaPembuat, base_url().'kinerja?team='.$tid, $this->_uid());
        }
        echo json_encode(array('status'=>true,'msg'=>'Tersimpan.','id'=>$id));
    }

    public function move_task()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $to = intval($this->input->post('list_id'));
        $t = $this->mymodel->selectWithQuery("SELECT team_id FROM tasks WHERE id=$id LIMIT 1");
        if (empty($t) || !$this->_boleh_tim($t[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }

        $l = $this->mymodel->selectWithQuery("SELECT kind FROM task_lists WHERE id=$to LIMIT 1");
        $selesai = (!empty($l) && $l[0]['kind'] === 'done') ? "'" . date('Y-m-d H:i:s') . "'" : "NULL";
        $this->db->query("UPDATE tasks SET list_id=$to, completed_at=$selesai WHERE id=$id");

        if (!empty($l) && $l[0]['kind'] === 'done') {
            $tk = $this->mymodel->selectWithQuery("SELECT title, created_by FROM tasks WHERE id=$id LIMIT 1");
            if (!empty($tk) && intval($tk[0]['created_by']) && intval($tk[0]['created_by']) !== intval($this->_uid())) {
                $this->db->insert('notifications', array(
                    'team_id'     => intval($t[0]['team_id']),
                    'user_id'     => intval($tk[0]['created_by']),
                    'type'        => 'success',
                    'category'    => 'team',
                    'subcategory' => 'task_done',
                    'title'       => 'Tugas selesai: '.substr($tk[0]['title'], 0, 200),
                    'message'     => 'Ditandai selesai',
                    'ref_url'     => $this->_url_aman('kinerja?team='.intval($t[0]['team_id'])),
                    'is_read'     => 0,
                    'created_at'  => date('Y-m-d H:i:s')
                ));
            }
        }
        echo json_encode(array('status'=>true));
    }

    public function delete_task()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $t = $this->mymodel->selectWithQuery("SELECT team_id FROM tasks WHERE id=$id LIMIT 1");
        if (empty($t) || !$this->_boleh_tim($t[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $this->db->query("DELETE FROM tasks WHERE id=$id");
        echo json_encode(array('status'=>true,'msg'=>'Tugas dihapus.'));
    }

    /* ===== PENGUMUMAN ===== */
    public function pengumuman()
    {
        $tim   = $this->_tim_saya();
        $aktif = intval($this->input->get('team'));
        if (!$aktif && !empty($tim)) $aktif = intval($tim[0]['id']);
        if ($aktif && !$this->_boleh_tim($aktif)) $aktif = 0;
        $list = array();
        if ($aktif) {
            $list = $this->mymodel->selectWithQuery("SELECT a.*, u.full_name AS penulis FROM team_announcements a LEFT JOIN user u ON u.id=a.created_by WHERE a.team_id=$aktif ORDER BY a.is_pinned DESC, a.created_at DESC");
            foreach ($list as $k => $v) {
                $aid = intval($v['id']);
                $list[$k]['files']     = $this->mymodel->selectWithQuery("SELECT * FROM announcement_files WHERE ann_id=$aid ORDER BY id");
                $list[$k]['comments']  = $this->mymodel->selectWithQuery("SELECT c.*, u.full_name AS nama FROM announcement_comments c LEFT JOIN user u ON u.id=c.user_id WHERE c.ann_id=$aid ORDER BY c.id ASC");
                $list[$k]['reactions'] = $this->mymodel->selectWithQuery("SELECT emoji, COUNT(*) AS jml FROM announcement_reactions WHERE ann_id=$aid GROUP BY emoji");
                $mineR = $this->mymodel->selectWithQuery("SELECT emoji FROM announcement_reactions WHERE ann_id=$aid AND user_id=".intval($this->_uid())." LIMIT 1");
                $list[$k]['my_reaction'] = !empty($mineR) ? $mineR[0]['emoji'] : null;
            }
        }
        $data['title']   = 'Pengumuman Tim';
        $data['teams']   = $tim;
        $data['team_id'] = $aktif;
        $data['items']   = $list;
        $data['boleh']   = $this->_kelola();
        $data['uid']     = $this->_uid();
        $data['content'] = $this->load->view('kinerja/pengumuman', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function save_pengumuman()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->post('team_id'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false,'msg'=>'Tidak punya akses.')); return; }
        $judul = trim($this->input->post('title'));
        if ($judul === '') { echo json_encode(array('status'=>false,'msg'=>'Judul wajib diisi.')); return; }
        $id = intval($this->input->post('id'));
        $d = array(
            'team_id'   => $tid,
            'title'     => $judul,
            'body'      => $this->input->post('body'),
            'is_pinned' => $this->input->post('is_pinned') ? 1 : 0
        );
        if ($id > 0) {
            $this->db->update('team_announcements', $d, array('id'=>$id));
        } else {
            $d['created_by'] = $this->_uid();
            $d['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('team_announcements', $d);
        }
        echo json_encode(array('status'=>true,'msg'=>'Tersimpan.'));
    }

    public function delete_pengumuman()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_announcements WHERE id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $this->db->query("DELETE FROM team_announcements WHERE id=$id");
        echo json_encode(array('status'=>true,'msg'=>'Dihapus.'));
    }

    /* ===== DOKUMEN & FILE ===== */
    public function dokumen()
    {
        $tim   = $this->_tim_saya();
        $aktif = intval($this->input->get('team'));
        if (!$aktif && !empty($tim)) $aktif = intval($tim[0]['id']);
        if ($aktif && !$this->_boleh_tim($aktif)) $aktif = 0;
        $list = array();
        if ($aktif) {
            $list = $this->mymodel->selectWithQuery("
                SELECT f.*, u.full_name AS pengunggah,
                  (SELECT COUNT(*) FROM document_comments c WHERE c.file_id = f.id) AS jml_komentar
                FROM team_files f
                LEFT JOIN user u ON u.id = f.uploaded_by
                WHERE f.team_id = $aktif
                ORDER BY f.created_at DESC");
        }
        $data['title']   = 'Dokumen & File';
        $data['teams']   = $tim;
        $data['team_id'] = $aktif;
        $data['items']   = $list;
        $data['boleh']   = $this->_kelola();
        $data['content'] = $this->load->view('kinerja/dokumen', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function upload_file()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->post('team_id'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false,'msg'=>'Tidak punya akses.')); return; }
        if (empty($_FILES['berkas']['name'])) { echo json_encode(array('status'=>false,'msg'=>'Pilih berkas dulu.')); return; }
        $dir = FCPATH . 'uploads/team_files/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $asli = $_FILES['berkas']['name'];
        $ext  = strtolower(pathinfo($asli, PATHINFO_EXTENSION));
        $ok   = array('jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','zip','mp4','webm','mov','avi','mkv','mp3','wav');
        if (!in_array($ext, $ok)) { echo json_encode(array('status'=>false,'msg'=>'Jenis berkas tidak diizinkan.')); return; }
        if ($_FILES['berkas']['size'] > 100*1024*1024) { echo json_encode(array('status'=>false,'msg'=>'Ukuran maksimal 100 MB.')); return; }
        $nama = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES['berkas']['tmp_name'], $dir . $nama)) {
            echo json_encode(array('status'=>false,'msg'=>'Gagal menyimpan berkas.')); return;
        }
        $this->db->insert('team_files', array(
            'team_id'     => $tid,
            'name'        => substr($asli, 0, 250),
            'judul'       => substr(trim($this->input->post('judul')) ?: $asli, 0, 190),
            'project_url' => substr(trim((string)$this->input->post('project_url')), 0, 500),
            'file_path'   => 'uploads/team_files/' . $nama,
            'file_type'   => $ext,
            'file_size'   => intval($_FILES['berkas']['size']),
            'note'        => $this->input->post('note'),
            'uploaded_by' => $this->_uid(),
            'created_at'  => date('Y-m-d H:i:s')
        ));
        echo json_encode(array('status'=>true,'msg'=>'Berkas diunggah.'));
    }

    public function delete_file()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id, file_path FROM team_files WHERE id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $p = FCPATH . $r[0]['file_path'];
        if ($r[0]['file_path'] && is_file($p)) @unlink($p);
        $this->db->query("DELETE FROM team_files WHERE id=$id");
        echo json_encode(array('status'=>true,'msg'=>'Berkas dihapus.'));
    }

    /* ===== DETAIL TUGAS: lampiran + ceklis ===== */
    public function task_detail()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->get('id'));
        $t = $this->mymodel->selectWithQuery("SELECT * FROM tasks WHERE id=$id LIMIT 1");
        if (empty($t) || !$this->_boleh_tim($t[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }

        $lampiran = $this->mymodel->selectWithQuery("SELECT a.*, u.full_name AS pengunggah FROM task_attachments a LEFT JOIN user u ON u.id=a.uploaded_by WHERE a.task_id=$id ORDER BY a.created_at DESC");
        $ceklis   = $this->mymodel->selectWithQuery("SELECT c.*, u.full_name AS pelaku FROM task_checklist c LEFT JOIN user u ON u.id=c.done_by WHERE c.task_id=$id ORDER BY c.sort_order, c.id");
        echo json_encode(array('status'=>true,'task'=>$t[0],'attachments'=>$lampiran,'checklist'=>$ceklis));
    }

    public function upload_bukti()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->post('task_id'));
        $t = $this->mymodel->selectWithQuery("SELECT team_id FROM tasks WHERE id=$tid LIMIT 1");
        if (empty($t) || !$this->_boleh_tim($t[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        if (empty($_FILES['berkas']['name'])) { echo json_encode(array('status'=>false,'msg'=>'Pilih berkas dulu.')); return; }

        $dir = FCPATH . 'uploads/task_files/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $asli = $_FILES['berkas']['name'];
        $ext  = strtolower(pathinfo($asli, PATHINFO_EXTENSION));
        $ok   = array('jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','txt','csv','zip','mp4','webm','mov');
        if (!in_array($ext, $ok)) { echo json_encode(array('status'=>false,'msg'=>'Jenis berkas tidak diizinkan.')); return; }
        if ($_FILES['berkas']['size'] > 100*1024*1024) { echo json_encode(array('status'=>false,'msg'=>'Ukuran maksimal 100 MB. Untuk berkas lebih besar, gunakan link Drive.')); return; }

        $nama = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES['berkas']['tmp_name'], $dir . $nama)) {
            echo json_encode(array('status'=>false,'msg'=>'Gagal menyimpan.')); return;
        }
        $this->db->insert('task_attachments', array(
            'task_id'     => $tid,
            'name'        => substr($asli, 0, 250),
            'file_path'   => 'uploads/task_files/' . $nama,
            'file_type'   => $ext,
            'file_size'   => intval($_FILES['berkas']['size']),
            'uploaded_by' => $this->_uid(),
            'created_at'  => date('Y-m-d H:i:s')
        ));
        echo json_encode(array('status'=>true,'msg'=>'Bukti diunggah.'));
    }

    public function delete_bukti()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT a.file_path, t.team_id FROM task_attachments a JOIN tasks t ON t.id=a.task_id WHERE a.id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $p = FCPATH . $r[0]['file_path'];
        if (is_file($p)) @unlink($p);
        $this->db->query("DELETE FROM task_attachments WHERE id=$id");
        echo json_encode(array('status'=>true));
    }

    public function save_ceklis()
    {
        header('Content-Type: application/json');
        $tid  = intval($this->input->post('task_id'));
        $t = $this->mymodel->selectWithQuery("SELECT team_id FROM tasks WHERE id=$tid LIMIT 1");
        if (empty($t) || !$this->_boleh_tim($t[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $item = trim($this->input->post('item'));
        if ($item === '') { echo json_encode(array('status'=>false,'msg'=>'Isi rincian dulu.')); return; }
        $this->db->insert('task_checklist', array('task_id'=>$tid,'item'=>substr($item,0,250),'keterangan'=>trim($this->input->post('keterangan')),'created_at'=>date('Y-m-d H:i:s')));
        echo json_encode(array('status'=>true,'id'=>$this->db->insert_id()));
    }

    public function toggle_ceklis()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT c.is_done, t.team_id FROM task_checklist c JOIN tasks t ON t.id=c.task_id WHERE c.id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $baru = $r[0]['is_done'] ? 0 : 1;
        if ($baru) {
            $w = date('Y-m-d H:i:s'); $u = $this->_uid();
            $this->db->query("UPDATE task_checklist SET is_done=1, done_at='$w', done_by=$u WHERE id=$id");
        } else {
            $this->db->query("UPDATE task_checklist SET is_done=0, done_at=NULL, done_by=NULL WHERE id=$id");
        }
        echo json_encode(array('status'=>true,'is_done'=>$baru));
    }

    public function delete_ceklis()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT t.team_id FROM task_checklist c JOIN tasks t ON t.id=c.task_id WHERE c.id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $this->db->query("DELETE FROM task_checklist WHERE id=$id");
        echo json_encode(array('status'=>true));
    }

    /* ===== PERSETUJUAN (ACC) ===== */
    public function set_approval()
    {
        header('Content-Type: application/json');
        if (!$this->_kelola()) { echo json_encode(array('status'=>false,'msg'=>'Hanya atasan yang bisa menyetujui.')); return; }
        $id  = intval($this->input->post('id'));
        $st  = $this->input->post('approval');
        if (!in_array($st, array('menunggu','disetujui','revisi'))) { echo json_encode(array('status'=>false,'msg'=>'Status tidak dikenali.')); return; }

        $t = $this->mymodel->selectWithQuery("SELECT team_id FROM tasks WHERE id=$id LIMIT 1");
        if (empty($t)) { echo json_encode(array('status'=>false,'msg'=>'Tugas tidak ditemukan.')); return; }

        $this->db->update('tasks', array(
            'approval'      => $st,
            'approval_note' => substr(trim($this->input->post('note')), 0, 390),
            'approved_by'   => $this->_uid(),
            'approved_at'   => date('Y-m-d H:i:s')
        ), array('id'=>$id));
        echo json_encode(array('status'=>true,'msg'=>'Status diperbarui.'));
    }

    /* ===== LAMPIRAN PENGUMUMAN ===== */
    public function upload_ann_file()
    {
        header('Content-Type: application/json');
        $aid = intval($this->input->post('ann_id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_announcements WHERE id=$aid LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        if (empty($_FILES['berkas']['name'])) { echo json_encode(array('status'=>false,'msg'=>'Pilih berkas dulu.')); return; }

        $dir = FCPATH . 'uploads/ann_files/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $asli = $_FILES['berkas']['name'];
        $ext  = strtolower(pathinfo($asli, PATHINFO_EXTENSION));
        $ok   = array('jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','mp4','webm','zip');
        if (!in_array($ext, $ok)) { echo json_encode(array('status'=>false,'msg'=>'Jenis berkas tidak diizinkan.')); return; }
        if ($_FILES['berkas']['size'] > 100*1024*1024) { echo json_encode(array('status'=>false,'msg'=>'Maksimal 100 MB.')); return; }

        $nama = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($_FILES['berkas']['tmp_name'], $dir . $nama)) {
            echo json_encode(array('status'=>false,'msg'=>'Gagal menyimpan.')); return;
        }
        $this->db->insert('announcement_files', array(
            'ann_id'     => $aid,
            'name'       => substr($asli, 0, 250),
            'file_path'  => 'uploads/ann_files/' . $nama,
            'file_type'  => $ext,
            'file_size'  => intval($_FILES['berkas']['size']),
            'created_at' => date('Y-m-d H:i:s')
        ));
        echo json_encode(array('status'=>true,'msg'=>'Lampiran ditambahkan.'));
    }

    public function delete_ann_file()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT f.file_path, a.team_id FROM announcement_files f JOIN team_announcements a ON a.id=f.ann_id WHERE f.id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $p = FCPATH . $r[0]['file_path'];
        if (is_file($p)) @unlink($p);
        $this->db->query("DELETE FROM announcement_files WHERE id=$id");
        echo json_encode(array('status'=>true));
    }

    /* ===== KOMENTAR DOKUMEN ===== */
    public function list_doc_comments()
    {
        header('Content-Type: application/json');
        $fid = intval($this->input->get('file_id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_files WHERE id=$fid LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $rows = $this->mymodel->selectWithQuery("SELECT c.*, u.full_name AS nama FROM document_comments c LEFT JOIN user u ON u.id=c.user_id WHERE c.file_id=$fid ORDER BY c.id ASC");
        echo json_encode(array('status'=>true,'data'=>$rows,'uid'=>$this->_uid(),'boleh'=>$this->_kelola()));
    }

    public function add_doc_comment()
    {
        header('Content-Type: application/json');
        $fid = intval($this->input->post('file_id'));
        $txt = trim($this->input->post('comment'));
        if ($txt === '') { echo json_encode(array('status'=>false,'msg'=>'Komentar tidak boleh kosong.')); return; }
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_files WHERE id=$fid LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $this->db->insert('document_comments', array(
            'file_id'    => $fid,
            'user_id'    => $this->_uid(),
            'comment'    => substr($txt, 0, 990),
            'created_at' => date('Y-m-d H:i:s')
        ));
        $namaSaya = $this->_nama_user($this->_uid());
        $this->_notify_team(
            $r[0]['team_id'], 'doc_comment',
            $namaSaya . ' berkomentar di Dokumen',
            (function($t){
                $t = trim(preg_replace('/\s+/', ' ', $t));
                return mb_strlen($t) > 120 ? mb_substr($t, 0, 120) . '\xE2\x80\xA6' : $t;
            })($txt),
            $this->_url_aman('kinerja/dokumen?team=' . intval($r[0]['team_id']) . '&file=' . $fid),
            $this->_uid()
        );
        echo json_encode(array('status'=>true,'msg'=>'Komentar ditambahkan.'));
    }

    public function delete_doc_comment()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT c.user_id, f.team_id FROM document_comments c JOIN team_files f ON f.id=c.file_id WHERE c.id=$id LIMIT 1");
        if (empty($r)) { echo json_encode(array('status'=>false,'msg'=>'Tidak ditemukan.')); return; }
        if (!$this->_kelola() && intval($r[0]['user_id']) !== intval($this->_uid())) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $this->db->query("DELETE FROM document_comments WHERE id=$id");
        echo json_encode(array('status'=>true));
    }

    /* ===== REAKSI DOKUMEN ===== */
    public function list_doc_reactions()
    {
        header('Content-Type: application/json');
        $fid = intval($this->input->get('file_id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_files WHERE id=$fid LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false)); return; }
        $rows = $this->mymodel->selectWithQuery("SELECT emoji, COUNT(*) AS jml FROM document_reactions WHERE file_id=$fid GROUP BY emoji");
        $uid = $this->_uid();
        $mine = $this->mymodel->selectWithQuery("SELECT emoji FROM document_reactions WHERE file_id=$fid AND user_id=$uid LIMIT 1");
        echo json_encode(array('status'=>true,'data'=>$rows,'mine'=>(!empty($mine) ? $mine[0]['emoji'] : null)));
    }

    public function toggle_doc_reaction()
    {
        header('Content-Type: application/json');
        $fid = intval($this->input->post('file_id'));
        $emo = trim($this->input->post('emoji'));
        if ($emo === '') { echo json_encode(array('status'=>false,'msg'=>'Emoji kosong.')); return; }
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_files WHERE id=$fid LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $uid = $this->_uid();
        $ex = $this->mymodel->selectWithQuery("SELECT id, emoji FROM document_reactions WHERE file_id=$fid AND user_id=$uid LIMIT 1");
        if (!empty($ex) && $ex[0]['emoji'] === $emo) {
            $this->db->query("DELETE FROM document_reactions WHERE id=".intval($ex[0]['id']));
        } elseif (!empty($ex)) {
            $this->db->update('document_reactions', array('emoji'=>$emo,'created_at'=>date('Y-m-d H:i:s')), array('id'=>intval($ex[0]['id'])));
        } else {
            $this->db->insert('document_reactions', array('file_id'=>$fid,'user_id'=>$uid,'emoji'=>$emo,'created_at'=>date('Y-m-d H:i:s')));
        }
        echo json_encode(array('status'=>true));
    }

    /* ===== KOMENTAR & REAKSI PENGUMUMAN ===== */
    public function add_ann_comment()
    {
        header('Content-Type: application/json');
        $aid = intval($this->input->post('ann_id'));
        $txt = trim($this->input->post('comment'));
        if ($txt === '') { echo json_encode(array('status'=>false,'msg'=>'Komentar tidak boleh kosong.')); return; }
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_announcements WHERE id=$aid LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $this->db->insert('announcement_comments', array(
            'ann_id'     => $aid,
            'user_id'    => $this->_uid(),
            'comment'    => substr($txt, 0, 990),
            'created_at' => date('Y-m-d H:i:s')
        ));
        $namaSaya = $this->_nama_user($this->_uid());
        $this->_notify_team(
            $r[0]['team_id'], 'ann_comment',
            $namaSaya . ' berkomentar di Pengumuman',
            $txt,
            $this->_url_aman('kinerja/pengumuman?team=' . intval($r[0]['team_id'])),
            $this->_uid()
        );
        echo json_encode(array('status'=>true));
    }

    public function delete_ann_comment()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT c.user_id, a.team_id FROM announcement_comments c JOIN team_announcements a ON a.id=c.ann_id WHERE c.id=$id LIMIT 1");
        if (empty($r)) { echo json_encode(array('status'=>false,'msg'=>'Tidak ditemukan.')); return; }
        if (!$this->_kelola() && intval($r[0]['user_id']) !== intval($this->_uid())) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $this->db->query("DELETE FROM announcement_comments WHERE id=$id");
        echo json_encode(array('status'=>true));
    }

    public function toggle_ann_reaction()
    {
        header('Content-Type: application/json');
        $aid = intval($this->input->post('ann_id'));
        $emo = trim($this->input->post('emoji'));
        if ($emo === '') { echo json_encode(array('status'=>false,'msg'=>'Emoji kosong.')); return; }
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_announcements WHERE id=$aid LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $uid = $this->_uid();
        $ex = $this->mymodel->selectWithQuery("SELECT id, emoji FROM announcement_reactions WHERE ann_id=$aid AND user_id=$uid LIMIT 1");
        if (!empty($ex) && $ex[0]['emoji'] === $emo) {
            $this->db->query("DELETE FROM announcement_reactions WHERE id=".intval($ex[0]['id']));
        } elseif (!empty($ex)) {
            $this->db->update('announcement_reactions', array('emoji'=>$emo,'created_at'=>date('Y-m-d H:i:s')), array('id'=>intval($ex[0]['id'])));
        } else {
            $this->db->insert('announcement_reactions', array('ann_id'=>$aid,'user_id'=>$uid,'emoji'=>$emo,'created_at'=>date('Y-m-d H:i:s')));
        }
        echo json_encode(array('status'=>true));
    }

    public function edit_ann_comment()
    {
        header('Content-Type: application/json');
        $id  = intval($this->input->post('id'));
        $txt = trim($this->input->post('comment'));
        if ($txt === '') { echo json_encode(array('status'=>false,'msg'=>'Komentar tidak boleh kosong.')); return; }
        $r = $this->mymodel->selectWithQuery("SELECT c.user_id, c.comment, a.team_id FROM announcement_comments c JOIN team_announcements a ON a.id=c.ann_id WHERE c.id=$id LIMIT 1");
        if (empty($r)) { echo json_encode(array('status'=>false,'msg'=>'Tidak ditemukan.')); return; }
        if (!$this->_kelola() && intval($r[0]['user_id']) !== intval($this->_uid())) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }

        $baru = substr($txt, 0, 990);
        if ($baru === $r[0]['comment']) {
            echo json_encode(array('status'=>true,'berubah'=>false));
            return;
        }
        $this->db->update('announcement_comments', array(
            'comment'    => $baru,
            'edited'     => 1,
            'edited_at'  => date('Y-m-d H:i:s')
        ), array('id'=>$id));
        echo json_encode(array('status'=>true,'berubah'=>true));
    }

    public function edit_doc_comment()
    {
        header('Content-Type: application/json');
        $id  = intval($this->input->post('id'));
        $txt = trim($this->input->post('comment'));
        if ($txt === '') { echo json_encode(array('status'=>false,'msg'=>'Komentar tidak boleh kosong.')); return; }
        $r = $this->mymodel->selectWithQuery("SELECT c.user_id, c.comment, f.team_id FROM document_comments c JOIN team_files f ON f.id=c.file_id WHERE c.id=$id LIMIT 1");
        if (empty($r)) { echo json_encode(array('status'=>false,'msg'=>'Tidak ditemukan.')); return; }
        if (!$this->_kelola() && intval($r[0]['user_id']) !== intval($this->_uid())) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }

        $baru = substr($txt, 0, 990);
        if ($baru === $r[0]['comment']) {
            echo json_encode(array('status'=>true,'berubah'=>false));
            return;
        }
        $this->db->update('document_comments', array(
            'comment'   => $baru,
            'edited'    => 1,
            'edited_at' => date('Y-m-d H:i:s')
        ), array('id'=>$id));
        echo json_encode(array('status'=>true,'berubah'=>true));
    }

    public function edit_ceklis()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT c.task_id, t.team_id FROM task_checklist c JOIN tasks t ON t.id=c.task_id WHERE c.id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }

        $item = trim($this->input->post('item'));
        if ($item === '') { echo json_encode(array('status'=>false,'msg'=>'Rincian tidak boleh kosong.')); return; }

        $this->db->update('task_checklist', array(
            'item'       => substr($item, 0, 250),
            'keterangan' => trim($this->input->post('keterangan'))
        ), array('id'=>$id));
        echo json_encode(array('status'=>true));
    }

    /* ===== JADWAL ===== */
    public function jadwal()
    {
        $tim   = $this->_tim_saya();
        $aktif = intval($this->input->get('team'));
        if (!$aktif && !empty($tim)) $aktif = intval($tim[0]['id']);
        if ($aktif && !$this->_boleh_tim($aktif)) $aktif = 0;

        $bulan = $this->input->get('bulan');
        if (!preg_match('/^\d{4}-\d{2}$/', (string)$bulan)) $bulan = date('Y-m');
        $awal  = $bulan . '-01';
        $akhir = date('Y-m-t', strtotime($awal));

        $agenda = array(); $tenggat = array();
        if ($aktif) {
            $agenda = $this->mymodel->selectWithQuery("
                SELECT e.*, u.full_name AS pembuat FROM team_events e
                LEFT JOIN user u ON u.id=e.created_by
                WHERE e.team_id=$aktif AND e.tanggal BETWEEN '$awal' AND '$akhir'
                ORDER BY e.tanggal, e.jam_mulai");
            $tenggat = $this->mymodel->selectWithQuery("
                SELECT t.id, t.title, t.due_date, l.name AS list_nama, l.kind, u.full_name AS pj
                FROM tasks t
                LEFT JOIN task_lists l ON l.id=t.list_id
                LEFT JOIN user u ON u.id=t.assignee_id
                WHERE t.team_id=$aktif AND t.due_date BETWEEN '$awal' AND '$akhir'
                ORDER BY t.due_date");
        }

        $libur = $this->mymodel->selectWithQuery("SELECT * FROM holidays WHERE tanggal BETWEEN '$awal' AND '$akhir' ORDER BY tanggal");

        $data['title']   = 'Jadwal Tim';
        $data['teams']   = $tim;
        $data['team_id'] = $aktif;
        $data['bulan']   = $bulan;
        $data['agenda']  = $agenda;
        $data['tenggat'] = $tenggat;
        $data['libur']   = $libur;
        $data['boleh']   = $this->_kelola();
        $data['content'] = $this->load->view('kinerja/jadwal', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function save_event()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->post('team_id'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false,'msg'=>'Tidak punya akses.')); return; }
        $judul = trim($this->input->post('title'));
        $tgl   = $this->input->post('tanggal');
        if ($judul === '' || !$tgl) { echo json_encode(array('status'=>false,'msg'=>'Judul dan tanggal wajib diisi.')); return; }

        $id = intval($this->input->post('id'));
        $d = array(
            'team_id'     => $tid,
            'title'       => substr($judul, 0, 190),
            'description' => $this->input->post('description'),
            'tanggal'     => $tgl,
            'jam_mulai'   => $this->input->post('jam_mulai') ?: null,
            'jam_selesai' => $this->input->post('jam_selesai') ?: null,
            'warna'       => $this->input->post('warna') ?: '#6E4FA8'
        );
        if ($id > 0) { $this->db->update('team_events', $d, array('id'=>$id)); }
        else {
            $d['created_by'] = $this->_uid();
            $d['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('team_events', $d);

            $pembuat = $this->mymodel->selectWithQuery("SELECT full_name FROM user WHERE id=".intval($this->_uid())." LIMIT 1");
            $namaPembuat = !empty($pembuat) ? $pembuat[0]['full_name'] : 'Seseorang';
            $tglBaca = date('j M Y', strtotime($tgl));
            $this->_notify_team($tid, 'event', 'Jadwal baru: '.substr($judul,0,190), $tglBaca.' &middot; oleh '.$namaPembuat, base_url().'kinerja/jadwal?team='.$tid, $this->_uid());
        }
        echo json_encode(array('status'=>true,'msg'=>'Tersimpan.'));
    }

    public function delete_event()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_events WHERE id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $this->db->query("DELETE FROM team_events WHERE id=$id");
        echo json_encode(array('status'=>true,'msg'=>'Agenda dihapus.'));
    }

    public function save_libur()
    {
        header('Content-Type: application/json');
        if (!$this->_kelola()) { echo json_encode(array('status'=>false,'msg'=>'Hanya admin.')); return; }
        $tgl  = $this->input->post('tanggal');
        $nama = trim($this->input->post('nama'));
        if (!$tgl || $nama === '') { echo json_encode(array('status'=>false,'msg'=>'Tanggal dan nama wajib diisi.')); return; }
        $jenis = $this->input->post('jenis');
        if (!in_array($jenis, array('nasional','cuti_bersama','perusahaan'))) $jenis = 'perusahaan';

        $this->db->query("INSERT IGNORE INTO holidays (tanggal, nama, jenis, sumber) VALUES ("
            . $this->db->escape($tgl) . ", " . $this->db->escape(substr($nama,0,190)) . ", "
            . $this->db->escape($jenis) . ", 'manual')");
        echo json_encode(array('status'=>true,'msg'=>'Hari libur ditambahkan.'));
    }

    public function delete_libur()
    {
        header('Content-Type: application/json');
        if (!$this->_kelola()) { echo json_encode(array('status'=>false,'msg'=>'Hanya admin.')); return; }
        $id = intval($this->input->post('id'));
        $this->db->query("DELETE FROM holidays WHERE id=$id");
        echo json_encode(array('status'=>true,'msg'=>'Dihapus.'));
    }

    /* ===== CHAT GRUP ===== */
    public function chat()
    {
        $tim   = $this->_tim_saya();
        $aktif = intval($this->input->get('team'));
        if (!$aktif && !empty($tim)) $aktif = intval($tim[0]['id']);
        if ($aktif && !$this->_boleh_tim($aktif)) $aktif = 0;
        $anggota = array();
        if ($aktif) {
            $me = $this->_uid();
            // hanya masuk otomatis kalau tim masih kosong (pembuat tim)
            $kosong = $this->mymodel->selectWithQuery("SELECT COUNT(*) AS n FROM team_members WHERE team_id=$aktif");
            if (!empty($kosong) && intval($kosong[0]['n']) === 0) {
                $this->db->query("INSERT IGNORE INTO team_members (team_id,user_id) VALUES ($aktif, $me)");
            }
            $anggota = $this->mymodel->selectWithQuery("SELECT u.id, u.full_name, m.role_in_team FROM team_members m JOIN user u ON u.id=m.user_id WHERE m.team_id=$aktif AND u.id NOT IN (SELECT ur.user_id FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.name IN ('developer','super_admin')) ORDER BY u.full_name");
        }
        $data['title']   = 'Chat Grup';
        $data['teams']   = $tim;
        $data['team_id'] = $aktif;
        $data['anggota'] = $anggota;
        $data['uid']     = $this->_uid();
        $data['boleh']   = $this->_kelola();
        $data['is_pj']   = $aktif ? $this->_is_team_admin($aktif) : false;
        $data['content'] = $this->load->view('kinerja/chat', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function poll_chat()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->get('team_id'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false)); return; }
        $uid = $this->_uid();

        $rows = $this->mymodel->selectWithQuery("
            SELECT c.*, u.full_name AS nama,
                   r.message AS balas_teks, r.deleted AS balas_dihapus,
                   ru.full_name AS balas_nama, r.file_name AS balas_file, r.user_id AS balas_uid
            FROM team_chat_messages c
            LEFT JOIN user u ON u.id = c.user_id
            LEFT JOIN team_chat_messages r ON r.id = c.reply_to
            LEFT JOIN user ru ON ru.id = r.user_id
            WHERE c.team_id = $tid ORDER BY c.id ASC");
        foreach ($rows as &$rw) {
            $rw['nama'] = $this->_nama_tampil($rw['user_id'], $uid);
            if (!empty($rw['balas_uid'])) { $rw['balas_nama'] = $this->_nama_tampil($rw['balas_uid'], $uid); }
        }
        unset($rw);

        $now = date('Y-m-d H:i:s');
        $this->db->query("INSERT INTO user_presence (user_id, last_seen) VALUES ($uid, '$now')
                          ON DUPLICATE KEY UPDATE last_seen = '$now'");
        $hadir = $this->mymodel->selectWithQuery("
            SELECT p.user_id FROM user_presence p
            JOIN team_members m ON m.user_id = p.user_id AND m.team_id = $tid
            WHERE p.last_seen >= DATE_SUB(NOW(), INTERVAL 70 SECOND)");
        $online = array();
        foreach ($hadir as $h) { $online[] = intval($h['user_id']); }

        $maxRow = $this->mymodel->selectWithQuery("SELECT MAX(id) AS m FROM team_chat_messages WHERE team_id=$tid");
        $maxId  = !empty($maxRow) && $maxRow[0]['m'] ? intval($maxRow[0]['m']) : 0;
        if ($maxId > 0) {
            $ex = $this->mymodel->selectWithQuery("SELECT id FROM team_chat_reads WHERE team_id=$tid AND user_id=$uid LIMIT 1");
            if (!empty($ex)) {
                $this->db->update('team_chat_reads', array('last_read_msg_id'=>$maxId,'updated_at'=>date('Y-m-d H:i:s')), array('id'=>intval($ex[0]['id'])));
            } else {
                $this->db->insert('team_chat_reads', array('team_id'=>$tid,'user_id'=>$uid,'last_read_msg_id'=>$maxId,'updated_at'=>date('Y-m-d H:i:s')));
            }
        }

        $reads = $this->mymodel->selectWithQuery("SELECT user_id, last_read_msg_id FROM team_chat_reads WHERE team_id=$tid");
        $readMap = array();
        foreach ($reads as $r) { $readMap[intval($r['user_id'])] = intval($r['last_read_msg_id']); }

        $bintangSaya = $this->mymodel->selectWithQuery("SELECT message_id FROM chat_message_stars WHERE user_id=$uid");
        $starredIds = array_map(function($x){ return intval($x['message_id']); }, $bintangSaya);

        $anggotaBaru = $this->mymodel->selectWithQuery("SELECT u.id, u.full_name, m.role_in_team FROM team_members m JOIN user u ON u.id=m.user_id WHERE m.team_id=$tid AND u.id NOT IN (SELECT ur.user_id FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.name IN ('developer','super_admin')) ORDER BY u.full_name");
        foreach ($anggotaBaru as &$ab) { $ab['full_name'] = $this->_nama_tampil($ab['id'], $uid); }
        unset($ab);

        echo json_encode(array('status'=>true,'messages'=>$rows,'reads'=>$readMap,'uid'=>$uid,'boleh'=>$this->_kelola(),'online'=>$online,'starred_ids'=>$starredIds,'anggota'=>$anggotaBaru));
    }

    public function send_chat_message()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->post('team_id'));
        $txt = trim($this->input->post('message'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }

        $path = null; $type = null; $fname = null;
        if (!empty($_FILES['berkas']['name'])) {
            $dir = FCPATH . 'uploads/chat_files/';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $asli = $_FILES['berkas']['name'];
            $ext  = strtolower(pathinfo($asli, PATHINFO_EXTENSION));
            $ok   = array('jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','mp4','webm','zip');
            if (!in_array($ext, $ok)) { echo json_encode(array('status'=>false,'msg'=>'Jenis berkas tidak diizinkan.')); return; }
            if ($_FILES['berkas']['size'] > 100*1024*1024) { echo json_encode(array('status'=>false,'msg'=>'Maksimal 100 MB.')); return; }
            $nama = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['berkas']['tmp_name'], $dir . $nama)) { echo json_encode(array('status'=>false,'msg'=>'Gagal menyimpan.')); return; }
            $path  = 'uploads/chat_files/' . $nama;
            $type  = $ext;
            $fname = substr($asli, 0, 250);
        }

        if ($txt === '' && !$path) { echo json_encode(array('status'=>false,'msg'=>'Pesan kosong.')); return; }

        $balas = intval($this->input->post('reply_to'));
        $this->db->insert('team_chat_messages', array(
            'team_id'    => $tid,
            'user_id'    => $this->_uid(),
            'reply_to'   => $balas > 0 ? $balas : null,
            'message'    => ($txt !== '' ? substr($txt, 0, 1990) : null),
            'file_path'  => $path,
            'file_type'  => $type,
            'file_name'  => $fname,
            'created_at' => date('Y-m-d H:i:s')
        ));

        $namaPengirim = $this->_nama_tampil($this->_uid());
        $ringkas = $txt !== '' ? $txt : ($fname ? '📎 '.$fname : 'mengirim pesan');
        $this->_notify_team($tid, 'chat', 'Pesan baru dari '.$namaPengirim, $ringkas, base_url().'kinerja/chat?team='.$tid, $this->_uid());

        echo json_encode(array('status'=>true));
    }

    public function edit_chat_message()
    {
        header('Content-Type: application/json');
        $id  = intval($this->input->post('id'));
        $txt = trim($this->input->post('message'));
        if ($txt === '') { echo json_encode(array('status'=>false,'msg'=>'Pesan tidak boleh kosong.')); return; }
        $r = $this->mymodel->selectWithQuery("SELECT user_id, message FROM team_chat_messages WHERE id=$id LIMIT 1");
        if (empty($r)) { echo json_encode(array('status'=>false,'msg'=>'Tidak ditemukan.')); return; }
        if (intval($r[0]['user_id']) !== intval($this->_uid())) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $baru = substr($txt, 0, 1990);
        if ($baru === $r[0]['message']) { echo json_encode(array('status'=>true,'berubah'=>false)); return; }
        $this->db->update('team_chat_messages', array('message'=>$baru,'edited'=>1,'edited_at'=>date('Y-m-d H:i:s')), array('id'=>$id));
        echo json_encode(array('status'=>true,'berubah'=>true));
    }

    public function delete_chat_message()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT user_id, file_path, deleted FROM team_chat_messages WHERE id=$id LIMIT 1");
        if (empty($r)) { echo json_encode(array('status'=>false,'msg'=>'Tidak ditemukan.')); return; }
        if (intval($r[0]['user_id']) !== intval($this->_uid()) && !$this->_kelola()) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }

        $rp = $this->mymodel->selectWithQuery("SELECT pinned FROM team_chat_messages WHERE id=$id LIMIT 1");
        $lagiPin = !empty($rp) && intval($rp[0]['pinned']) === 1;
        $konfirmasiPin = intval($this->input->post('confirm_pinned'));
        if ($lagiPin && !$konfirmasiPin) {
            echo json_encode(array('status'=>false,'butuh_konfirmasi_pin'=>true,'msg'=>'Pesan ini disematkan.'));
            return;
        }

        if (intval($r[0]['deleted']) === 1) {
            $this->db->query("DELETE FROM team_chat_messages WHERE id=$id");
            echo json_encode(array('status'=>true,'permanen'=>true));
            return;
        }

        if (!empty($r[0]['file_path'])) { $p = FCPATH . $r[0]['file_path']; if (is_file($p)) @unlink($p); }
        $this->db->update('team_chat_messages', array('deleted'=>1,'message'=>null,'file_path'=>null,'file_type'=>null,'file_name'=>null), array('id'=>$id));
        echo json_encode(array('status'=>true,'permanen'=>false));
    }

    public function clear_chat_history()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->post('team_id'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        if (!$this->_kelola()) { echo json_encode(array('status'=>false,'msg'=>'Hanya atasan yang bisa menghapus semua riwayat.')); return; }

        $hapusPinned  = intval($this->input->post('hapus_pinned'));
        $hapusStarred = intval($this->input->post('hapus_starred'));

        $where = "team_id=$tid";
        if (!$hapusPinned)  { $where .= " AND pinned=0"; }
        if (!$hapusStarred) { $where .= " AND id NOT IN (SELECT message_id FROM chat_message_stars)"; }

        $rows = $this->mymodel->selectWithQuery("SELECT id, file_path FROM team_chat_messages WHERE $where AND file_path IS NOT NULL");
        $ids = array();
        foreach ($rows as $r) {
            $p = FCPATH . $r['file_path']; if (is_file($p)) @unlink($p);
            $ids[] = intval($r['id']);
        }
        $this->db->query("DELETE FROM team_chat_messages WHERE $where");
        if (!$hapusPinned || !$hapusStarred) {
            $sisa = $this->mymodel->selectWithQuery("SELECT COUNT(*) AS n FROM team_chat_messages WHERE team_id=$tid");
            if (!empty($sisa) && intval($sisa[0]['n']) === 0) {
                $this->db->query("DELETE FROM team_chat_reads WHERE team_id=$tid");
            }
        } else {
            $this->db->query("DELETE FROM team_chat_reads WHERE team_id=$tid");
        }
        echo json_encode(array('status'=>true));
    }

    public function list_addable_members()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->get('team_id'));
        if (!$this->_boleh_tim($tid) || !($this->_kelola() || $this->_is_team_admin($tid))) { echo json_encode(array('status'=>false)); return; }
        $rows = $this->mymodel->selectWithQuery("SELECT u.id, u.full_name FROM user u WHERE u.id NOT IN (SELECT user_id FROM team_members WHERE team_id=$tid) AND u.id NOT IN (SELECT ur.user_id FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.name IN ('developer','super_admin')) ORDER BY u.full_name");
        echo json_encode(array('status'=>true,'data'=>$rows));
    }

    public function add_chat_member()
    {
        header('Content-Type: application/json');
        $tid  = intval($this->input->post('team_id'));
        $uid2 = intval($this->input->post('user_id'));
        if (!$this->_boleh_tim($tid) || !($this->_kelola() || $this->_is_team_admin($tid))) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $sudah = $this->mymodel->selectWithQuery("SELECT id FROM team_members WHERE team_id=$tid AND user_id=$uid2 LIMIT 1");
        $this->db->query("INSERT IGNORE INTO team_members (team_id,user_id) VALUES ($tid, $uid2)");
        if (empty($sudah)) {
            $this->_pesan_sistem($tid, $this->_nama_user($uid2) . ' ditambahkan ke grup oleh ' . $this->_nama_user($this->_uid()));
        }
        echo json_encode(array('status'=>true));
    }

    private function _pesan_sistem($team_id, $teks)
    {
        $this->db->insert('team_chat_messages', array(
            'team_id'    => intval($team_id),
            'user_id'    => intval($this->_uid()),
            'message'    => substr($teks, 0, 1990),
            'is_system'  => 1,
            'created_at' => date('Y-m-d H:i:s')
        ));
    }

    private function _admin_disamarkan($uid)
    {
        $r = $this->mymodel->selectWithQuery("SELECT r.id FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=".intval($uid)." AND r.name IN ('developer','super_admin') LIMIT 1");
        return !empty($r);
    }

    private function _nama_tampil($uid, $viewer_uid = null)
    {
        $uid = intval($uid);
        if ($viewer_uid !== null && intval($viewer_uid) === $uid) {
            $r = $this->mymodel->selectWithQuery("SELECT full_name FROM user WHERE id=$uid LIMIT 1");
            return !empty($r) ? $r[0]['full_name'] : 'Pengguna';
        }
        if ($this->_admin_disamarkan($uid)) {
            $al = $this->mymodel->selectWithQuery("SELECT alias_name FROM user_chat_alias WHERE user_id=$uid LIMIT 1");
            if (!empty($al) && trim($al[0]['alias_name']) !== '') return $al[0]['alias_name'];
            return 'Admin Tim';
        }
        $r = $this->mymodel->selectWithQuery("SELECT full_name FROM user WHERE id=$uid LIMIT 1");
        return !empty($r) ? $r[0]['full_name'] : 'Pengguna';
    }

    private function _nama_user($id)
    {
        return $this->_nama_tampil($id);
    }

    /* ===== KELUAR / KELUARKAN ANGGOTA ===== */
    public function remove_chat_member()
    {
        header('Content-Type: application/json');
        $tid    = intval($this->input->post('team_id'));
        $target = intval($this->input->post('user_id'));
        $saya   = $this->_uid();

        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }

        // keluar sendiri = boleh. mengeluarkan orang lain = harus punya izin kelola.
        if ($target !== $saya && !($this->_kelola() || $this->_is_team_admin($tid))) {
            echo json_encode(array('status'=>false,'msg'=>'Hanya atasan yang bisa mengeluarkan anggota.'));
            return;
        }

        $jml = $this->mymodel->selectWithQuery("SELECT COUNT(*) AS n FROM team_members WHERE team_id=$tid");
        if (!empty($jml) && intval($jml[0]['n']) <= 1) {
            echo json_encode(array('status'=>false,'msg'=>'Tidak bisa keluar — kamu satu-satunya anggota tim ini.'));
            return;
        }

        $namaTarget = $this->_nama_user($target);
        $this->db->query("DELETE FROM team_members WHERE team_id=$tid AND user_id=$target");
        $this->db->query("DELETE FROM team_chat_reads WHERE team_id=$tid AND user_id=$target");

        if ($target === $saya) {
            $this->_pesan_sistem($tid, $namaTarget . ' telah keluar dari grup');
        } else {
            $this->_pesan_sistem($tid, $namaTarget . ' dikeluarkan dari grup oleh ' . $this->_nama_user($saya));
        }

        echo json_encode(array('status'=>true,'saya_keluar'=>($target === $saya)));
    }

    public function edit_file_judul()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_files WHERE id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $judul = trim($this->input->post('judul'));
        if ($judul === '') { echo json_encode(array('status'=>false,'msg'=>'Judul tidak boleh kosong.')); return; }
        $this->db->update('team_files', array(
            'judul'       => substr($judul, 0, 190),
            'project_url' => substr(trim((string)$this->input->post('project_url')), 0, 500),
            'note'        => trim($this->input->post('note'))
        ), array('id'=>$id));
        echo json_encode(array('status'=>true));
    }

    /* ===== MEDIA & DOKUMEN DI CHAT ===== */
    public function chat_media()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->get('team_id'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false)); return; }

        $rows = $this->mymodel->selectWithQuery("
            SELECT c.id, c.file_path, c.file_type, c.file_name, c.created_at, u.full_name AS nama
            FROM team_chat_messages c
            LEFT JOIN user u ON u.id = c.user_id
            WHERE c.team_id = $tid AND c.deleted = 0 AND c.file_path IS NOT NULL
            ORDER BY c.id DESC");

        $media = array(); $dok = array();
        $gambar = array('jpg','jpeg','png','gif','webp');
        $video  = array('mp4','webm','mov');
        foreach ($rows as $r) {
            $e = strtolower($r['file_type']);
            if (in_array($e, $gambar) || in_array($e, $video)) $media[] = $r;
            else $dok[] = $r;
        }

        // kumpulkan tautan dari isi pesan
        $pesan = $this->mymodel->selectWithQuery("
            SELECT c.id, c.message, c.created_at, u.full_name AS nama
            FROM team_chat_messages c
            LEFT JOIN user u ON u.id = c.user_id
            WHERE c.team_id = $tid AND c.deleted = 0 AND c.is_system = 0
              AND c.message REGEXP 'https?://'
            ORDER BY c.id DESC");

        $tautan = array();
        foreach ($pesan as $p) {
            if (preg_match_all('#https?://[^\s<>"\']+#i', $p['message'], $m)) {
                foreach ($m[0] as $u) {
                    $u = rtrim($u, '.,;:)]}');
                    $host = parse_url($u, PHP_URL_HOST);
                    $tautan[] = array(
                        'url'        => $u,
                        'host'       => $host ? preg_replace('/^www\./', '', $host) : $u,
                        'nama'       => $p['nama'],
                        'created_at' => $p['created_at'],
                        'msg_id'     => $p['id']
                    );
                }
            }
        }

        echo json_encode(array('status'=>true,'media'=>$media,'dokumen'=>$dok,'tautan'=>$tautan));
    }

    /* ===== PERTANYAAN (CHECK-IN RUTIN) ===== */
    public function pertanyaan()
    {
        $tim   = $this->_tim_saya();
        $aktif = intval($this->input->get('team'));
        if (!$aktif && !empty($tim)) $aktif = intval($tim[0]['id']);
        if ($aktif && !$this->_boleh_tim($aktif)) $aktif = 0;

        $daftar = array();
        if ($aktif) {
            $daftar = $this->mymodel->selectWithQuery("
                SELECT q.*, u.full_name AS pembuat,
                  (SELECT COUNT(*) FROM kinerja_pertanyaan_penerima p WHERE p.pertanyaan_id=q.id) AS jml_penerima,
                  (SELECT COUNT(*) FROM kinerja_pertanyaan_jawaban j WHERE j.pertanyaan_id=q.id AND j.tanggal=CURDATE()) AS jml_jawab_hari_ini
                FROM kinerja_pertanyaan q
                LEFT JOIN user u ON u.id=q.created_by
                WHERE q.team_id=$aktif ORDER BY q.created_at DESC");
            foreach ($daftar as $k => $v) {
                $qid2 = intval($v['id']);
                $daftar[$k]['files'] = $this->mymodel->selectWithQuery("SELECT * FROM kinerja_pertanyaan_files WHERE pertanyaan_id=$qid2 ORDER BY id");
            }
        }

        $data['title']   = 'Pertanyaan Rutin';
        $data['teams']   = $tim;
        $data['team_id'] = $aktif;
        $data['daftar']  = $daftar;
        $data['boleh']   = $this->_kelola();
        $data['anggota'] = $aktif ? $this->mymodel->selectWithQuery("SELECT u.id, u.full_name FROM team_members m JOIN user u ON u.id=m.user_id WHERE m.team_id=$aktif ORDER BY u.full_name") : array();
        $data['content'] = $this->load->view('kinerja/pertanyaan', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function pertanyaan_buat()
    {
        $tim   = $this->_tim_saya();
        $aktif = intval($this->input->get('team'));
        if (!$aktif && !empty($tim)) $aktif = intval($tim[0]['id']);
        if (!$aktif || !$this->_boleh_tim($aktif)) { redirect(base_url().'kinerja/pertanyaan'); return; }

        $data['title']   = 'Buat Pertanyaan';
        $data['teams']   = $tim;
        $data['team_id'] = $aktif;
        $data['anggota'] = $this->mymodel->selectWithQuery("SELECT u.id, u.full_name FROM team_members m JOIN user u ON u.id=m.user_id WHERE m.team_id=$aktif ORDER BY u.full_name");
        $data['content'] = $this->load->view('kinerja/pertanyaan_buat', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function save_pertanyaan()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->post('team_id'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false,'msg'=>'Tidak punya akses.')); return; }
        $teks = trim($this->input->post('pertanyaan'));
        if ($teks === '') { echo json_encode(array('status'=>false,'msg'=>'Pertanyaan wajib diisi.')); return; }
        $hariArr = $this->input->post('hari');
        if (!is_array($hariArr) || empty($hariArr)) { echo json_encode(array('status'=>false,'msg'=>'Pilih minimal satu hari.')); return; }
        $jam = $this->input->post('jam') ?: '09:00';

        $this->db->insert('kinerja_pertanyaan', array(
            'team_id'    => $tid,
            'pertanyaan' => substr($teks, 0, 490),
            'hari'       => implode(',', $hariArr),
            'jam'        => $jam . ':00',
            'rahasia'    => $this->input->post('rahasia') ? 1 : 0,
            'created_by' => $this->_uid(),
            'created_at' => date('Y-m-d H:i:s')
        ));
        $qid = $this->db->insert_id();

        $penerima = $this->input->post('penerima');
        if (is_array($penerima)) {
            foreach ($penerima as $u) {
                $this->db->query("INSERT IGNORE INTO kinerja_pertanyaan_penerima (pertanyaan_id,user_id) VALUES ($qid, " . intval($u) . ")");
            }
        } else {
            // tanpa pilih -> default ke diri sendiri
            $this->db->query("INSERT IGNORE INTO kinerja_pertanyaan_penerima (pertanyaan_id,user_id) VALUES ($qid, " . intval($this->_uid()) . ")");
        }

        // unggah gambar (boleh lebih dari satu)
        if (!empty($_FILES['gambar']['name'][0])) {
            $dir = FCPATH . 'uploads/pertanyaan_files/';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $ok = array('jpg','jpeg','png','gif','webp');
            $jml = count($_FILES['gambar']['name']);
            for ($i = 0; $i < $jml; $i++) {
                if (empty($_FILES['gambar']['name'][$i])) continue;
                $ext = strtolower(pathinfo($_FILES['gambar']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $ok)) continue;
                if ($_FILES['gambar']['size'][$i] > 8*1024*1024) continue;
                $nm = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['gambar']['tmp_name'][$i], $dir . $nm)) {
                    $this->db->insert('kinerja_pertanyaan_files', array(
                        'pertanyaan_id' => $qid,
                        'file_path'     => 'uploads/pertanyaan_files/' . $nm,
                        'file_type'     => $ext,
                        'created_at'    => date('Y-m-d H:i:s')
                    ));
                }
            }
        }

        echo json_encode(array('status'=>true,'msg'=>'Pertanyaan dipublikasikan.','id'=>$qid));
    }

    public function delete_pertanyaan()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM kinerja_pertanyaan WHERE id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $this->db->query("DELETE FROM kinerja_pertanyaan WHERE id=$id");
        $this->db->query("DELETE FROM kinerja_pertanyaan_penerima WHERE pertanyaan_id=$id");
        $this->db->query("DELETE FROM kinerja_pertanyaan_jawaban WHERE pertanyaan_id=$id");
        echo json_encode(array('status'=>true,'msg'=>'Pertanyaan dihapus.'));
    }

    public function jawab_pertanyaan()
    {
        header('Content-Type: application/json');
        $qid = intval($this->input->post('pertanyaan_id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM kinerja_pertanyaan WHERE id=$qid LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $jwb = trim($this->input->post('jawaban'));
        $adaGambar = !empty($_FILES['gambar']['name']);
        if ($jwb === '' && !$adaGambar) { echo json_encode(array('status'=>false,'msg'=>'Jawaban tidak boleh kosong.')); return; }

        $pathGambar = null;
        if ($adaGambar) {
            $dir = FCPATH . 'uploads/pertanyaan_files/';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            $ok  = array('jpg','jpeg','png','gif','webp');
            if (in_array($ext, $ok) && $_FILES['gambar']['size'] <= 8*1024*1024) {
                $nm = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $dir . $nm)) {
                    $pathGambar = 'uploads/pertanyaan_files/' . $nm;
                }
            }
        }

        $this->db->insert('kinerja_pertanyaan_jawaban', array(
            'pertanyaan_id' => $qid,
            'user_id'       => $this->_uid(),
            'jawaban'       => $jwb,
            'gambar'        => $pathGambar,
            'tanggal'       => date('Y-m-d'),
            'created_at'    => date('Y-m-d H:i:s')
        ));
        $namaSaya = $this->_nama_user($this->_uid());
        $this->_notify_team(
            $r[0]['team_id'], 'pertanyaan_jawaban',
            $namaSaya . ' menjawab pertanyaan',
            $jwb !== '' ? $jwb : '📷 Mengirim gambar',
            $this->_url_aman('kinerja/pertanyaan?team=' . intval($r[0]['team_id'])),
            $this->_uid()
        );
        echo json_encode(array('status'=>true,'msg'=>'Jawaban terkirim.'));
    }

    public function list_jawaban()
    {
        header('Content-Type: application/json');
        $qid = intval($this->input->get('pertanyaan_id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id, rahasia, created_by FROM kinerja_pertanyaan WHERE id=$qid LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false)); return; }

        // rahasia: cuma pembuat/admin yang bisa lihat semua jawaban
        if (intval($r[0]['rahasia']) === 1 && !$this->_kelola() && intval($r[0]['created_by']) !== $this->_uid()) {
            $jwb = $this->mymodel->selectWithQuery("SELECT j.*, u.full_name AS nama FROM kinerja_pertanyaan_jawaban j LEFT JOIN user u ON u.id=j.user_id WHERE j.pertanyaan_id=$qid AND j.user_id=" . $this->_uid() . " ORDER BY j.tanggal DESC");
        } else {
            $jwb = $this->mymodel->selectWithQuery("SELECT j.*, u.full_name AS nama FROM kinerja_pertanyaan_jawaban j LEFT JOIN user u ON u.id=j.user_id WHERE j.pertanyaan_id=$qid ORDER BY j.tanggal DESC, j.id DESC");
        }
        echo json_encode(array('status'=>true,'data'=>$jwb));
    }

    /* ===== DETAIL PERTANYAAN + KOMENTAR ===== */
    public function pertanyaan_detail($id = 0)
    {
        $id = intval($id);
        $q = $this->mymodel->selectWithQuery("
            SELECT q.*, u.full_name AS pembuat
            FROM kinerja_pertanyaan q LEFT JOIN user u ON u.id=q.created_by
            WHERE q.id=$id LIMIT 1");
        if (empty($q) || !$this->_boleh_tim($q[0]['team_id'])) { redirect(base_url().'kinerja/pertanyaan'); return; }
        $q = $q[0];

        $komentar = $this->mymodel->selectWithQuery("
            SELECT k.*, u.full_name AS nama
            FROM kinerja_pertanyaan_komentar k LEFT JOIN user u ON u.id=k.user_id
            WHERE k.pertanyaan_id=$id ORDER BY k.created_at ASC");

        $tim = $this->_tim_saya();
        $data['title']    = 'Detail Pertanyaan';
        $data['teams']    = $tim;
        $data['team_id']  = intval($q['team_id']);
        $data['q']        = $q;
        $data['komentar'] = $komentar;
        $data['uid']      = $this->_uid();
        $data['boleh']    = $this->_kelola();
        $data['content']  = $this->load->view('kinerja/pertanyaan_detail', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function save_komentar()
    {
        header('Content-Type: application/json');
        $qid = intval($this->input->post('pertanyaan_id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM kinerja_pertanyaan WHERE id=$qid LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $isi = trim($this->input->post('isi'));
        if ($isi === '') { echo json_encode(array('status'=>false,'msg'=>'Komentar tidak boleh kosong.')); return; }

        $this->db->insert('kinerja_pertanyaan_komentar', array(
            'pertanyaan_id' => $qid,
            'user_id'       => $this->_uid(),
            'isi'           => $isi,
            'created_at'    => date('Y-m-d H:i:s')
        ));
        $kid = $this->db->insert_id();
        $row = $this->mymodel->selectWithQuery("
            SELECT k.*, u.full_name AS nama FROM kinerja_pertanyaan_komentar k
            LEFT JOIN user u ON u.id=k.user_id WHERE k.id=$kid LIMIT 1");
        $namaSaya = !empty($row) ? $row[0]['nama'] : $this->_nama_user($this->_uid());
        $this->_notify_team(
            $r[0]['team_id'], 'pertanyaan_komentar',
            $namaSaya . ' berkomentar di Pertanyaan',
            $isi,
            $this->_url_aman('kinerja/pertanyaan_detail/' . $qid),
            $this->_uid()
        );
        echo json_encode(array('status'=>true,'data'=>$row[0]));
    }

    public function edit_komentar()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT user_id, isi FROM kinerja_pertanyaan_komentar WHERE id=$id LIMIT 1");
        if (empty($r) || intval($r[0]['user_id']) !== $this->_uid()) { echo json_encode(array('status'=>false,'msg'=>'Hanya pemilik komentar yang bisa mengedit.')); return; }
        $isi = trim($this->input->post('isi'));
        if ($isi === '') { echo json_encode(array('status'=>false,'msg'=>'Komentar tidak boleh kosong.')); return; }

        $berubah = ($isi !== $r[0]['isi']) ? 1 : 0;
        $this->db->update('kinerja_pertanyaan_komentar', array(
            'isi'        => $isi,
            'is_edited'  => $berubah ? 1 : $this->db->select('is_edited')->get_where('kinerja_pertanyaan_komentar', array('id'=>$id))->row()->is_edited,
            'updated_at' => $berubah ? date('Y-m-d H:i:s') : null
        ), array('id'=>$id));

        if ($berubah) {
            $this->db->query("UPDATE kinerja_pertanyaan_komentar SET is_edited=1, updated_at=NOW() WHERE id=$id");
        }
        echo json_encode(array('status'=>true,'edited'=>$berubah));
    }

    public function delete_komentar()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT user_id FROM kinerja_pertanyaan_komentar WHERE id=$id LIMIT 1");
        if (empty($r) || (intval($r[0]['user_id']) !== $this->_uid() && !$this->_kelola())) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $this->db->query("DELETE FROM kinerja_pertanyaan_komentar WHERE id=$id");
        echo json_encode(array('status'=>true));
    }

    /* ===== RINGKASAN ===== */
    public function ringkasan()
    {
        $tim   = $this->_tim_saya();
        $aktif = intval($this->input->get('team'));
        if (!$aktif && !empty($tim)) $aktif = intval($tim[0]['id']);
        if ($aktif && !$this->_boleh_tim($aktif)) $aktif = 0;

        $tugas = array(); $pengumuman = null; $dokumen = array(); $chat = array(); $jml_anggota = 0;
        if ($aktif) {
            $tugas = $this->mymodel->selectWithQuery("
                SELECT t.id, t.title, u.full_name AS assignee_name,
                  (SELECT a.file_path FROM task_attachments a
                    WHERE a.task_id = t.id AND LOWER(a.file_type) IN ('jpg','jpeg','png','gif','webp')
                    ORDER BY a.id ASC LIMIT 1) AS cover_path,
                  (SELECT l.name FROM task_lists l WHERE l.id = t.list_id) AS list_nama
                FROM tasks t
                LEFT JOIN user u ON u.id = t.assignee_id
                WHERE t.team_id=$aktif ORDER BY t.id DESC LIMIT 4");
            $pg = $this->mymodel->selectWithQuery("SELECT a.*, u.full_name AS penulis FROM team_announcements a LEFT JOIN user u ON u.id=a.created_by WHERE a.team_id=$aktif ORDER BY a.is_pinned DESC, a.created_at DESC LIMIT 1");
            $pengumuman = !empty($pg) ? $pg[0] : null;
            $dokumen = $this->mymodel->selectWithQuery("SELECT f.*, u.full_name AS pengunggah FROM team_files f LEFT JOIN user u ON u.id=f.uploaded_by WHERE f.team_id=$aktif ORDER BY f.created_at DESC LIMIT 3");
            $chat = $this->mymodel->selectWithQuery("SELECT c.*, u.full_name AS nama FROM team_chat_messages c LEFT JOIN user u ON u.id=c.user_id WHERE c.team_id=$aktif AND c.deleted=0 ORDER BY c.id DESC LIMIT 4");
            $chat = array_reverse($chat);
            $jr = $this->mymodel->selectWithQuery("SELECT COUNT(*) AS j FROM team_members WHERE team_id=$aktif");
            $jml_anggota = !empty($jr) ? intval($jr[0]['j']) : 0;
        }

        $data['title']       = 'Ringkasan Tim';
        $data['teams']       = $tim;
        $data['team_id']     = $aktif;
        $data['tugas']       = $tugas;
        $data['pengumuman']  = $pengumuman;
        $data['dokumen']     = $dokumen;
        $data['chat']        = $chat;
        $data['jml_anggota'] = $jml_anggota;
        $data['content']     = $this->load->view('kinerja/ringkasan', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    /* ===== NOTIFIKASI ===== */
    public function poll_notifications()
    {
        header('Content-Type: application/json');
        $uid = $this->_uid();
        if (!$uid) { echo json_encode(array('status'=>false)); return; }
        // Hanya membaca sesi: kunci dilepas supaya permintaan lain dari user
        // yang sama tidak ikut antre menunggu (sesi CI memakai berkas + kunci).
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
        $rows = $this->mymodel->selectWithQuery("SELECT * FROM notifications WHERE user_id=$uid AND read_at IS NULL ORDER BY id ASC LIMIT 20");
        echo json_encode(array('status'=>true,'data'=>$rows));
    }

    public function mark_notif_read()
    {
        header('Content-Type: application/json');
        $id  = intval($this->input->post('id'));
        $uid = $this->_uid();
        if ($id > 0) {
            $this->db->query("UPDATE notifications SET read_at=NOW() WHERE id=$id AND user_id=$uid");
        } else {
            $this->db->query("UPDATE notifications SET read_at=NOW() WHERE user_id=$uid AND read_at IS NULL");
        }
        echo json_encode(array('status'=>true));
    }

    public function set_team_admin()
    {
        header('Content-Type: application/json');
        $tid  = intval($this->input->post('team_id'));
        $uid2 = intval($this->input->post('user_id'));
        if (!$this->_boleh_tim($tid) || !$this->_kelola()) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $r = $this->mymodel->selectWithQuery("SELECT id, role_in_team FROM team_members WHERE team_id=$tid AND user_id=$uid2 LIMIT 1");
        if (empty($r)) { echo json_encode(array('status'=>false,'msg'=>'User bukan anggota tim ini.')); return; }
        $baru = ($r[0]['role_in_team'] === 'admin') ? 'member' : 'admin';
        $this->db->query("UPDATE team_members SET role_in_team='$baru' WHERE id=".intval($r[0]['id']));
        $this->_pesan_sistem($tid, $this->_nama_user($uid2) . ($baru==='admin' ? ' dijadikan Penanggung Jawab tim oleh ' : ' dicopot dari Penanggung Jawab tim oleh ') . $this->_nama_user($this->_uid()));
        echo json_encode(array('status'=>true,'role_in_team'=>$baru));
    }

    public function toggle_pin_chat()
    {
        header('Content-Type: application/json');
        $id = intval($this->input->post('id'));
        $r = $this->mymodel->selectWithQuery("SELECT team_id, pinned FROM team_chat_messages WHERE id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $baru = intval($r[0]['pinned']) === 1 ? 0 : 1;
        $this->db->update('team_chat_messages', array(
            'pinned'    => $baru,
            'pinned_by' => $baru ? $this->_uid() : null,
            'pinned_at' => $baru ? date('Y-m-d H:i:s') : null
        ), array('id'=>$id));
        echo json_encode(array('status'=>true,'pinned'=>$baru));
    }

    public function toggle_star_chat()
    {
        header('Content-Type: application/json');
        $id  = intval($this->input->post('id'));
        $uid = $this->_uid();
        $r = $this->mymodel->selectWithQuery("SELECT team_id FROM team_chat_messages WHERE id=$id LIMIT 1");
        if (empty($r) || !$this->_boleh_tim($r[0]['team_id'])) { echo json_encode(array('status'=>false,'msg'=>'Ditolak.')); return; }
        $ex = $this->mymodel->selectWithQuery("SELECT id FROM chat_message_stars WHERE message_id=$id AND user_id=$uid LIMIT 1");
        if (!empty($ex)) {
            $this->db->query("DELETE FROM chat_message_stars WHERE id=".intval($ex[0]['id']));
            echo json_encode(array('status'=>true,'starred'=>false));
        } else {
            $this->db->insert('chat_message_stars', array('message_id'=>$id,'user_id'=>$uid,'created_at'=>date('Y-m-d H:i:s')));
            echo json_encode(array('status'=>true,'starred'=>true));
        }
    }

    public function list_pinned_chat()
    {
        header('Content-Type: application/json');
        $tid = intval($this->input->get('team_id'));
        if (!$this->_boleh_tim($tid)) { echo json_encode(array('status'=>false)); return; }
        $rows = $this->mymodel->selectWithQuery("SELECT c.*, u.full_name AS nama FROM team_chat_messages c LEFT JOIN user u ON u.id=c.user_id WHERE c.team_id=$tid AND c.pinned=1 AND c.deleted=0 ORDER BY c.pinned_at DESC");
        echo json_encode(array('status'=>true,'data'=>$rows));
    }
}
