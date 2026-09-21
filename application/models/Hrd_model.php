<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hrd_model extends CI_Model
{
    /* =============================== KARYAWAN =============================== */

    public function karyawan_list($filter = [])
    {
        $this->db->from('hrd_karyawan');
        if (!empty($filter['q'])) {
            $q = $filter['q'];
            $this->db->group_start()
                ->like('nama', $q)->or_like('jabatan', $q)->or_like('divisi', $q)
                ->or_like('email', $q)->or_like('no_hp', $q)->or_like('nik', $q)
                ->group_end();
        }
        if (!empty($filter['divisi']))  $this->db->where('divisi', $filter['divisi']);
        if (!empty($filter['status']))  $this->db->where('status_karyawan', $filter['status']);
        $this->db->order_by('nama', 'ASC');
        return $this->db->get()->result_array();
    }

    public function karyawan($id)
    {
        return $this->db->get_where('hrd_karyawan', ['id' => (int)$id])->row_array();
    }

    public function karyawan_by($kolom, $nilai)
    {
        if ($nilai === null || $nilai === '') return null;
        return $this->db->get_where('hrd_karyawan', [$kolom => $nilai])->row_array();
    }

    /** Simpan / perbarui. Kunci pencocokan: NIK dulu, kalau kosong pakai email. */
    public function karyawan_simpan($data, $id = null)
    {
        if (!$id) {
            $ada = null;
            if (!empty($data['nik']))        $ada = $this->karyawan_by('nik', $data['nik']);
            if (!$ada && !empty($data['email'])) $ada = $this->karyawan_by('email', $data['email']);
            if ($ada) $id = $ada['id'];
        }
        if ($id) {
            $this->db->where('id', (int)$id)->update('hrd_karyawan', $data);
            return (int)$id;
        }
        $this->db->insert('hrd_karyawan', $data);
        return (int)$this->db->insert_id();
    }

    public function karyawan_hapus($id)
    {
        $this->db->where('id', (int)$id)->delete('hrd_karyawan');
    }

    public function daftar_divisi()
    {
        $r = $this->db->distinct()->select('divisi')->where('divisi IS NOT NULL', null, false)
            ->where('divisi !=', '')->order_by('divisi')->get('hrd_karyawan')->result_array();
        return array_column($r, 'divisi');
    }

    /**
     * Ambil daftar karyawan dari tabel user ERP.
     * Nama kolom dideteksi otomatis supaya tidak bergantung pada skema tertentu.
     */
    public function user_erp()
    {
        if (!$this->db->table_exists('user')) return [];
        $kolom = $this->db->list_fields('user');

        $cari = function ($kandidat) use ($kolom) {
            foreach ($kandidat as $k) if (in_array($k, $kolom, true)) return $k;
            return null;
        };

        $k_nama    = $cari(['nama', 'name', 'full_name', 'fullname', 'nama_lengkap', 'username']);
        $k_email   = $cari(['email', 'e_mail']);
        $k_jabatan = $cari(['jabatan', 'position', 'posisi', 'title']);
        $k_divisi  = $cari(['divisi', 'department', 'departemen', 'divisi_name']);
        $k_hp      = $cari(['no_hp', 'phone', 'telp', 'no_telp', 'hp', 'nomor_hp']);
        $k_aktif   = $cari(['is_active', 'active', 'status']);

        if (!$k_nama) return [];

        $pilih = ['id', $k_nama . ' AS nama'];
        if ($k_email)   $pilih[] = $k_email . ' AS email';
        if ($k_jabatan) $pilih[] = $k_jabatan . ' AS jabatan';
        if ($k_divisi)  $pilih[] = $k_divisi . ' AS divisi';
        if ($k_hp)      $pilih[] = $k_hp . ' AS no_hp';

        $this->db->select(implode(', ', $pilih))->from('user');
        if ($k_aktif) $this->db->where("($k_aktif = 1 OR $k_aktif = '1' OR $k_aktif = 'aktif' OR $k_aktif = 'Aktif')", null, false);
        $this->db->order_by($k_nama, 'ASC');

        return $this->db->get()->result_array();
    }

    /** Masukkan user ERP yang belum ada di data karyawan. Kembalikan ringkasan. */
    public function impor_dari_user()
    {
        $hasil = ['baru' => 0, 'sudah_ada' => 0, 'dilewati' => 0, 'nama_baru' => []];

        foreach ($this->user_erp() as $u) {
            $nama  = trim($u['nama'] ?? '');
            $email = trim($u['email'] ?? '');
            if ($nama === '') { $hasil['dilewati']++; continue; }

            $ada = null;
            if ($email !== '') $ada = $this->karyawan_by('email', $email);
            if (!$ada)         $ada = $this->karyawan_by('nama', $nama);
            if ($ada) { $hasil['sudah_ada']++; continue; }

            $this->db->insert('hrd_karyawan', [
                'user_id'         => (int)$u['id'],
                'nama'            => $nama,
                'email'           => $email ?: null,
                'jabatan'         => trim($u['jabatan'] ?? '') ?: null,
                'divisi'          => trim($u['divisi'] ?? '') ?: null,
                'no_hp'           => trim($u['no_hp'] ?? '') ?: null,
                'status_karyawan' => 'Aktif',
                'sumber'          => 'hrd',
            ]);
            $hasil['baru']++;
            $hasil['nama_baru'][] = $nama;
        }
        return $hasil;
    }

    /* ============================== INVENTARIS ============================== */

    public function inventaris_list($filter = [])
    {
        $this->db->select('i.*, k.divisi')
            ->from('hrd_inventaris i')
            ->join('hrd_karyawan k', 'k.id = i.karyawan_id', 'left');
        if (!empty($filter['q'])) {
            $q = $filter['q'];
            $this->db->group_start()
                ->like('i.nama_pemegang', $q)->or_like('i.jenis_barang', $q)
                ->or_like('i.merek', $q)->or_like('i.serial_number', $q)
                ->or_like('i.no_hp_kantor', $q)
                ->group_end();
        }
        if (!empty($filter['kondisi'])) $this->db->where('i.kondisi', $filter['kondisi']);
        if (!empty($filter['karyawan_id'])) $this->db->where('i.karyawan_id', (int)$filter['karyawan_id']);
        $this->db->order_by('i.nama_pemegang', 'ASC');
        return $this->db->get()->result_array();
    }

    public function inventaris($id)
    {
        return $this->db->get_where('hrd_inventaris', ['id' => (int)$id])->row_array();
    }

    public function inventaris_simpan($data, $id = null)
    {
        if ($id) { $this->db->where('id', (int)$id)->update('hrd_inventaris', $data); return (int)$id; }
        $this->db->insert('hrd_inventaris', $data);
        return (int)$this->db->insert_id();
    }

    public function inventaris_hapus($id)
    {
        $this->db->where('id', (int)$id)->delete('hrd_inventaris');
    }

    /* ================================ FORM ================================= */

    public function form_list()
    {
        return $this->db->query("
            SELECT f.*, (SELECT COUNT(*) FROM hrd_form_jawaban j WHERE j.form_id = f.id) AS jml_jawaban
            FROM hrd_form f ORDER BY f.id DESC")->result_array();
    }

    public function form($id)      { return $this->db->get_where('hrd_form', ['id' => (int)$id])->row_array(); }
    public function form_slug($s)  { return $this->db->get_where('hrd_form', ['slug' => $s])->row_array(); }

    public function form_simpan($data, $id = null)
    {
        if ($id) { $this->db->where('id', (int)$id)->update('hrd_form', $data); return (int)$id; }
        $this->db->insert('hrd_form', $data);
        return (int)$this->db->insert_id();
    }

    public function form_hapus($id)
    {
        $id = (int)$id;
        $jw = $this->db->select('id')->get_where('hrd_form_jawaban', ['form_id' => $id])->result_array();
        if ($jw) $this->db->where_in('jawaban_id', array_column($jw, 'id'))->delete('hrd_form_jawaban_isi');
        $this->db->where('form_id', $id)->delete('hrd_form_jawaban');
        $this->db->where('form_id', $id)->delete('hrd_form_field');
        $this->db->where('id', $id)->delete('hrd_form');
    }

    public function field_list($form_id)
    {
        return $this->db->order_by('urutan', 'ASC')->order_by('id', 'ASC')
            ->get_where('hrd_form_field', ['form_id' => (int)$form_id])->result_array();
    }

    public function field_simpan($data, $id = null)
    {
        if ($id) { $this->db->where('id', (int)$id)->update('hrd_form_field', $data); return (int)$id; }
        $this->db->insert('hrd_form_field', $data);
        return (int)$this->db->insert_id();
    }

    public function field_hapus($id)
    {
        $this->db->where('field_id', (int)$id)->delete('hrd_form_jawaban_isi');
        $this->db->where('id', (int)$id)->delete('hrd_form_field');
    }

    public function jawaban_list($form_id)
    {
        return $this->db->order_by('created_at', 'DESC')
            ->get_where('hrd_form_jawaban', ['form_id' => (int)$form_id])->result_array();
    }

    public function jawaban($id)   { return $this->db->get_where('hrd_form_jawaban', ['id' => (int)$id])->row_array(); }

    public function jawaban_isi($jawaban_id)
    {
        return $this->db->select('i.*, f.label, f.tipe, f.urutan')
            ->from('hrd_form_jawaban_isi i')
            ->join('hrd_form_field f', 'f.id = i.field_id', 'left')
            ->where('i.jawaban_id', (int)$jawaban_id)
            ->order_by('f.urutan', 'ASC')->get()->result_array();
    }

    public function jawaban_sudah_isi($form_id, $email)
    {
        if (!$email) return false;
        return (bool)$this->db->get_where('hrd_form_jawaban', ['form_id' => (int)$form_id, 'email' => $email])->row_array();
    }

    public function jawaban_simpan($form_id, $meta, $isi)
    {
        $this->db->insert('hrd_form_jawaban', array_merge(['form_id' => (int)$form_id], $meta));
        $jid = (int)$this->db->insert_id();
        foreach ($isi as $row) {
            $row['jawaban_id'] = $jid;
            $this->db->insert('hrd_form_jawaban_isi', $row);
        }
        return $jid;
    }

    public function jawaban_hapus($id)
    {
        $this->db->where('jawaban_id', (int)$id)->delete('hrd_form_jawaban_isi');
        $this->db->where('id', (int)$id)->delete('hrd_form_jawaban');
    }

    /* ================================= DISC ================================ */

    public function disc_list($filter = [])
    {
        $this->db->from('hrd_disc');
        if (!empty($filter['q'])) {
            $this->db->group_start()->like('nama', $filter['q'])->or_like('email', $filter['q'])->group_end();
        }
        if (!empty($filter['tipe'])) $this->db->where('tipe_utama', $filter['tipe']);
        return $this->db->order_by('created_at', 'DESC')->get()->result_array();
    }

    public function disc($id)         { return $this->db->get_where('hrd_disc', ['id' => (int)$id])->row_array(); }
    public function disc_simpan($d)   { $this->db->insert('hrd_disc', $d); return (int)$this->db->insert_id(); }
    public function disc_hapus($id)   { $this->db->where('id', (int)$id)->delete('hrd_disc'); }

    public function disc_sudah_isi($email)
    {
        if (!$email) return false;
        return (bool)$this->db->get_where('hrd_disc', ['email' => $email])->row_array();
    }

    public function disc_terakhir_karyawan($karyawan_id, $email = null)
    {
        $this->db->from('hrd_disc')->group_start()->where('karyawan_id', (int)$karyawan_id);
        if ($email) $this->db->or_where('email', $email);
        $this->db->group_end()->order_by('created_at', 'DESC')->limit(1);
        return $this->db->get()->row_array();
    }

    /* ========================= DISC: SOAL & PENGATURAN ====================== */

    /** Semua kelompok kata, dikelompokkan: [nomor_grup => [ {kata, dimensi, posisi}, ... ]] */
    public function disc_soal()
    {
        $rows = $this->db->order_by('grup', 'ASC')->order_by('posisi', 'ASC')
            ->get('hrd_disc_soal')->result_array();
        $out = [];
        foreach ($rows as $r) $out[(int)$r['grup']][] = $r;
        return $out;
    }

    public function disc_grup($grup)
    {
        return $this->db->order_by('posisi', 'ASC')
            ->get_where('hrd_disc_soal', ['grup' => (int)$grup])->result_array();
    }

    public function disc_grup_berikutnya()
    {
        $r = $this->db->select_max('grup', 'maks')->get('hrd_disc_soal')->row_array();
        return (int)($r['maks'] ?? 0) + 1;
    }

    /** $kata = [ ['kata' => '...', 'dimensi' => 'D'], ... ] tepat 4 baris. */
    public function disc_grup_simpan($grup, $kata, $pertanyaan = '')
    {
        $grup = (int)$grup;
        $this->db->where('grup', $grup)->delete('hrd_disc_soal');
        foreach (array_values($kata) as $i => $k) {
            if (trim($k['kata']) === '') continue;
            $this->db->insert('hrd_disc_soal', [
                'grup'       => $grup,
                'posisi'     => $i,
                'kata'       => trim($k['kata']),
                'dimensi'    => in_array($k['dimensi'], ['D','I','S','C'], true) ? $k['dimensi'] : 'D',
                'pertanyaan' => trim($pertanyaan),
            ]);
        }
        return $grup;
    }

    public function disc_grup_hapus($grup)
    {
        $this->db->where('grup', (int)$grup)->delete('hrd_disc_soal');
        $this->disc_rapikan_nomor();
    }

    /** Rapatkan penomoran kelompok supaya tidak bolong setelah ada yang dihapus. */
    public function disc_rapikan_nomor()
    {
        $lama = $this->db->distinct()->select('grup')->order_by('grup', 'ASC')
            ->get('hrd_disc_soal')->result_array();
        $baru = 1;
        foreach ($lama as $g) {
            if ((int)$g['grup'] !== $baru) {
                $this->db->where('grup', (int)$g['grup'])->update('hrd_disc_soal', ['grup' => $baru]);
            }
            $baru++;
        }
    }

    public function disc_soal_kosongkan()
    {
        $this->db->truncate('hrd_disc_soal');
    }

    public function disc_jumlah_grup()
    {
        return (int)$this->db->query("SELECT COUNT(DISTINCT grup) n FROM hrd_disc_soal")->row()->n;
    }

    public function pengaturan($kunci, $bawaan = null)
    {
        $r = $this->db->get_where('hrd_pengaturan', ['kunci' => $kunci])->row_array();
        return $r ? $r['nilai'] : $bawaan;
    }

    public function pengaturan_simpan($kunci, $nilai)
    {
        if ($this->db->get_where('hrd_pengaturan', ['kunci' => $kunci])->row_array()) {
            $this->db->where('kunci', $kunci)->update('hrd_pengaturan', ['nilai' => $nilai]);
        } else {
            $this->db->insert('hrd_pengaturan', ['kunci' => $kunci, 'nilai' => $nilai]);
        }
    }

    /* =============================== RINGKASAN ============================== */

    public function ringkasan()
    {
        $q = function ($sql) { return (int)$this->db->query($sql)->row()->n; };
        return [
            'karyawan_aktif'  => $q("SELECT COUNT(*) n FROM hrd_karyawan WHERE status_karyawan='Aktif'"),
            'karyawan_total'  => $q("SELECT COUNT(*) n FROM hrd_karyawan"),
            'belum_lengkap'   => $q("SELECT COUNT(*) n FROM hrd_karyawan WHERE foto_ktp IS NULL OR foto_ktp='' OR no_rekening IS NULL OR no_rekening='' OR nik IS NULL OR nik=''"),
            'inventaris'      => $q("SELECT COUNT(*) n FROM hrd_inventaris"),
            'barang_rusak'    => $q("SELECT COUNT(*) n FROM hrd_inventaris WHERE kondisi <> 'Baik'"),
            'kartu_akan_habis'=> $q("SELECT COUNT(*) n FROM hrd_inventaris WHERE masa_aktif_kartu IS NOT NULL AND masa_aktif_kartu <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"),
            'disc_terisi'     => $q("SELECT COUNT(*) n FROM hrd_disc"),
            'form_terbuka'    => $q("SELECT COUNT(*) n FROM hrd_form WHERE status='terbuka'"),
            'disc_soal_grup'  => $q("SELECT COUNT(DISTINCT grup) n FROM hrd_disc_soal"),
        ];
    }

    public function karyawan_belum_disc()
    {
        return $this->db->query("
            SELECT k.id, k.nama, k.jabatan, k.divisi, k.email, k.user_id
            FROM hrd_karyawan k
            LEFT JOIN hrd_disc d ON (d.karyawan_id = k.id OR (d.email = k.email AND k.email <> ''))
            WHERE k.status_karyawan = 'Aktif' AND d.id IS NULL
            ORDER BY k.nama")->result_array();
    }

    /**
     * Asesmen yang sudah dimulai tapi berhenti di tengah jalan.
     *
     * Orang-orang ini tidak muncul di karyawan_belum_disc() karena DISC-nya
     * sudah terisi, padahal asesmennya belum kelar -- jadi tanpa daftar ini
     * mereka lolos dari pantauan sepenuhnya.
     */
    public function asesmen_tertunda()
    {
        return $this->db->query("
            SELECT a.id, a.nama, a.divisi, a.tahap, a.created_at
            FROM hrd_asesmen a
            WHERE a.tahap <> 'selesai'
            ORDER BY a.created_at")->result_array();
    }

    public function aktivitas_terbaru($limit = 12)
    {
        return $this->db->query("
            SELECT nama AS judul, 'Data karyawan' AS jenis, updated_at AS waktu FROM hrd_karyawan
            UNION ALL
            SELECT CONCAT(nama_pemegang,' - ',jenis_barang), 'Inventaris', updated_at FROM hrd_inventaris
            UNION ALL
            SELECT nama, 'DISC', created_at FROM hrd_disc
            ORDER BY waktu DESC LIMIT ?", [(int)$limit])->result_array();
    }

    public function log($aksi, $target = null)
    {
        $this->db->insert('hrd_log_akses', [
            'user_id'   => $this->session->userdata('id') ?: null,
            'user_nama' => $this->session->userdata('nama') ?: $this->session->userdata('name'),
            'aksi'      => $aksi,
            'target'    => $target,
            'ip'        => $this->input->ip_address(),
        ]);
    }
}
