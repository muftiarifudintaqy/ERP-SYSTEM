<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Asesmen_skor — semua perhitungan asesmen di satu tempat.
 *
 * Sengaja tidak menyentuh database, sesi, maupun HTTP. Masuk array,
 * keluar array. Jadi bisa diuji langsung dari baris perintah tanpa
 * membuka browser, dan kalau nanti rumusnya berubah, yang disentuh
 * cuma berkas ini.
 *
 * Pakai:
 *   $this->load->library('asesmen_skor');
 *   $h = $this->asesmen_skor->disc($jawaban, $soal);
 */
class Asesmen_skor
{
    /* ================================================================
     * STIFIn — dari tanggal lahir
     * ============================================================== */

    /** Jumlahkan semua angka lalu reduksi sampai satu digit (1..9). */
    public function reduksi($n)
    {
        $n = abs((int)$n);
        while ($n > 9) {
            $j = 0;
            foreach (str_split((string)$n) as $c) $j += (int)$c;
            $n = $j;
        }
        return $n;
    }

    /**
     * @param string $tanggal  'YYYY-MM-DD'
     * @return array|null      ['kode' => 1..9]
     */
    public function stifin($tanggal)
    {
        $t = date_create($tanggal);
        if (!$t) return NULL;

        // DDMMYYYY, contoh 16-05-1998 -> 1+6+0+5+1+9+9+8 = 39 -> 12 -> 3
        $angka = $t->format('dmY');
        $j = 0;
        foreach (str_split($angka) as $c) $j += (int)$c;

        return ['kode' => $this->reduksi($j)];
    }

    /**
     * Numerologi nama. A=1..I=9, J=1..R=9, S=1..Z=8.
     * Karakter selain huruf (titik, spasi, tanda hubung) dilewati.
     */
    public function angka_nama($nama)
    {
        $nama = strtoupper($nama);
        $j = 0;
        for ($i = 0, $n = strlen($nama); $i < $n; $i++) {
            $c = $nama[$i];
            if ($c < 'A' || $c > 'Z') continue;
            $urut = ord($c) - 64;                 // A=1 .. Z=26
            $nilai = (($urut - 1) % 9) + 1;       // ulang tiap 9 huruf
            $j += $nilai;
        }
        return $j > 0 ? $this->reduksi($j) : NULL;
    }

    /* ================================================================
     * DISC — 10 skenario, pilih satu dari A-D
     * ============================================================== */

    /**
     * @param array $jawaban  ['1' => 'D', '2' => 'S', ...] dimensi terpilih
     * @return array
     */
    public function disc($jawaban)
    {
        $hitung = ['D' => 0, 'I' => 0, 'S' => 0, 'C' => 0];
        foreach ($jawaban as $dim) {
            $dim = strtoupper(trim($dim));
            if (isset($hitung[$dim])) $hitung[$dim]++;
        }

        $total = array_sum($hitung);
        $persen = [];
        foreach ($hitung as $k => $v) {
            $persen[$k] = $total > 0 ? (int)round($v / $total * 100) : 0;
        }

        $urut = $this->urutkan($persen);

        return [
            'most'   => $hitung,          // jumlah pilihan tiap dimensi
            'persen' => $persen,          // disimpan ke kolom net_*
            'utama'  => $urut[0],
            'kedua'  => $urut[1],
            'sebar'  => max($persen) - min($persen),
            'total'  => $total,
        ];
    }

    /* ================================================================
     * BIG FIVE — 10 skenario, pilih satu dari A-E
     * ============================================================== */

    /**
     * @param array $jawaban  ['1' => 'O', '2' => 'C', ...]
     */
    public function bigfive($jawaban)
    {
        $skor = ['O' => 0, 'C' => 0, 'E' => 0, 'A' => 0, 'N' => 0];
        foreach ($jawaban as $dim) {
            $dim = strtoupper(trim($dim));
            if (isset($skor[$dim])) $skor[$dim]++;
        }

        $total = array_sum($skor);
        $persen = [];
        foreach ($skor as $k => $v) {
            $persen[$k] = $total > 0 ? (int)round($v / $total * 100) : 0;
        }

        $urut = $this->urutkan($persen);

        return [
            'skor'      => $skor,
            'persen'    => $persen,
            'dominan'   => $urut[0],
            'pendukung' => $urut[1],
            'sebar'     => max($persen) - min($persen),
            'total'     => $total,
        ];
    }

    /* ================================================================
     * KARAKTERISTIK — 10 dilema, tiap opsi punya bobot 1..5
     * ============================================================== */

    /**
     * @param array $jawaban  ['1' => 0, '2' => 3, ...] indeks opsi terpilih
     * @param array $soal     baris hrd_karakteristik_soal
     */
    public function karakteristik($jawaban, $soal)
    {
        // Kunci aspek harus sama persis dengan yang ditulis di opsi_json.
        // Sebelumnya kode memakai 'tanggungjawab' sementara bank soal
        // memakai 'tanggungjwb', sehingga aspek itu selalu bernilai nol
        // tanpa ada tanda kesalahan apa pun.
        $kosong = ['integritas' => 0, 'tanggungjwb' => 0,
                   'kerjasama'  => 0, 'ketahanan'   => 0];
        $skor  = 0;
        $aspek = $kosong;
        $maks_aspek = $kosong;
        $rinci = [];

        foreach ($soal as $s) {
            $nomor = (int)$s['nomor'];
            $opsi  = json_decode($s['opsi_json'], TRUE);
            if (!is_array($opsi)) continue;

            // aspek soal ini diambil dari opsi pertama
            $a = $opsi[0]['aspek'] ?? 'integritas';
            if (isset($maks_aspek[$a])) $maks_aspek[$a] += 5;

            if (!isset($jawaban[$nomor])) continue;
            $i = (int)$jawaban[$nomor];
            if (!isset($opsi[$i])) continue;

            $b = (int)($opsi[$i]['bobot'] ?? 0);
            $skor += $b;

            $aspek_opsi = $opsi[$i]['aspek'] ?? $a;
            if (isset($aspek[$aspek_opsi])) $aspek[$aspek_opsi] += $b;

            $rinci[$nomor] = ['pilih' => $i, 'bobot' => $b];
        }

        $maks   = count($soal) * 5;
        $persen = $maks > 0 ? (int)round($skor / $maks * 100) : 0;

        return [
            'skor'       => $skor,
            'maks'       => $maks,
            'persen'     => $persen,
            'kategori'   => $this->kategori_karakter($skor, $maks),
            'aspek'      => $aspek,
            'maks_aspek' => $maks_aspek,
            'rinci'      => $rinci,
        ];
    }

    private function kategori_karakter($skor, $maks)
    {
        if ($maks <= 0) return 'Tidak dapat dinilai';
        $p = $skor / $maks * 100;
        if ($p >= 85) return 'Sangat Baik';
        if ($p >= 70) return 'Baik';
        if ($p >= 55) return 'Cukup';
        return 'Perlu Perbaikan';
    }

    /* ================================================================
     * APTITUDE — 30 soal pilihan ganda
     * Rumus Pak Vikram: IQ = 100 + (benar - 20) x 3,5, minimum 70
     * ============================================================== */

    /**
     * @param array $jawaban  ['1' => 'A', '2' => 'C', ...]
     * @param array $soal     baris hrd_aptitude_soal (ada kunci & kategori)
     */
    public function aptitude($jawaban, $soal)
    {
        $benar = $salah = $kosong = 0;
        $per_kategori = ['Numerik' => 0, 'Verbal' => 0, 'Logika' => 0, 'Spasial' => 0];
        $rinci = [];

        foreach ($soal as $s) {
            $nomor = (int)$s['nomor'];
            $kunci = strtoupper(trim($s['kunci']));
            $isi   = isset($jawaban[$nomor]) ? strtoupper(trim($jawaban[$nomor])) : '';

            if ($isi === '') {
                $kosong++;
                $rinci[$nomor] = ['jawab' => '', 'kunci' => $kunci, 'benar' => FALSE];
                continue;
            }

            if ($isi === $kunci) {
                $benar++;
                $kat = $s['kategori'];
                if (isset($per_kategori[$kat])) $per_kategori[$kat]++;
                $rinci[$nomor] = ['jawab' => $isi, 'kunci' => $kunci, 'benar' => TRUE];
            } else {
                $salah++;
                $rinci[$nomor] = ['jawab' => $isi, 'kunci' => $kunci, 'benar' => FALSE];
            }
        }

        $iq = $this->iq($benar);

        return [
            'benar'        => $benar,
            'salah'        => $salah,
            'kosong'       => $kosong,
            'total'        => count($soal),
            'iq'           => $iq,
            'kategori_iq'  => $this->kategori_iq($iq),
            'per_kategori' => $per_kategori,
            'rinci'        => $rinci,
        ];
    }

    /** Rumus Pak Vikram. Angka 20 adalah patokan dari 30 soal. */
    public function iq($benar)
    {
        $iq = 100 + (((int)$benar - 20) * 3.5);
        return (int)round(max(70, $iq));
    }

    public function kategori_iq($iq)
    {
        if ($iq >= 130) return 'Sangat superior';
        if ($iq >= 120) return 'Superior';
        if ($iq >= 110) return 'Di atas rata-rata';
        if ($iq >= 90)  return 'Rata-rata';
        if ($iq >= 80)  return 'Di bawah rata-rata';
        return 'Batas ambang';
    }

    /* ================================================================
     * PEMBANTU
     * ============================================================== */

    /**
     * Urutkan kunci dari nilai terbesar. Kalau seri, urutan aslinya
     * dipertahankan supaya hasilnya konsisten, tidak berubah-ubah
     * tiap kali dihitung.
     */
    private function urutkan($nilai)
    {
        $pasang = [];
        $i = 0;
        foreach ($nilai as $k => $v) $pasang[] = [$k, $v, $i++];

        usort($pasang, function ($a, $b) {
            if ($a[1] !== $b[1]) return $b[1] - $a[1];
            return $a[2] - $b[2];
        });

        $keluar = [];
        foreach ($pasang as $p) $keluar[] = $p[0];
        return $keluar;
    }
}
