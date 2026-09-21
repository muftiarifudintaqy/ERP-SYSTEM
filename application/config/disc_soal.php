<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Soal DISC - 24 kelompok, tiap kelompok 4 kata.
 * Peserta memilih 1 kata PALING menggambarkan dirinya (MOST)
 * dan 1 kata PALING TIDAK menggambarkan dirinya (LEAST).
 * Kode dimensi: D = Dominance, I = Influence, S = Steadiness, C = Compliance.
 * Aman diedit: cukup ubah teksnya, jangan ubah kode dimensinya.
 */
$config['disc_soal'] = [
    [['D','Tegas'],            ['I','Ceria'],              ['S','Sabar'],                  ['C','Teliti']],
    [['D','Berani ambil alih'],['I','Ramah'],              ['S','Setia'],                  ['C','Hati-hati']],
    [['D','Cepat memutuskan'], ['I','Banyak bicara'],      ['S','Tenang'],                 ['C','Suka menganalisa']],
    [['D','Suka tantangan'],   ['I','Penuh semangat'],     ['S','Pendengar yang baik'],    ['C','Rapi']],
    [['D','Kompetitif'],       ['I','Optimis'],            ['S','Konsisten'],              ['C','Sistematis']],
    [['D','Langsung ke inti'], ['I','Menghibur'],          ['S','Rendah hati'],            ['C','Perfeksionis']],
    [['D','Mandiri'],          ['I','Mudah bergaul'],      ['S','Suka menolong'],          ['C','Taat aturan']],
    [['D','Fokus hasil'],      ['I','Pandai membujuk'],    ['S','Sabar menunggu'],         ['C','Berbasis data']],
    [['D','Pantang menyerah'], ['I','Spontan'],            ['S','Tenang saat ditekan'],    ['C','Kritis']],
    [['D','Suka memimpin'],    ['I','Antusias'],           ['S','Kooperatif'],             ['C','Akurat']],
    [['D','Blak-blakan'],      ['I','Pandai bercerita'],   ['S','Lembut'],                 ['C','Hati-hati bicara']],
    [['D','Suka perubahan'],   ['I','Suka keramaian'],     ['S','Suka rutinitas'],         ['C','Suka prosedur']],
    [['D','Percaya diri'],     ['I','Ekspresif'],          ['S','Penyabar'],               ['C','Logis']],
    [['D','Tegar'],            ['I','Hangat'],             ['S','Pengertian'],             ['C','Pendiam']],
    [['D','Suka mengatur'],    ['I','Cepat akrab'],        ['S','Bisa diandalkan'],        ['C','Detail']],
    [['D','Kejar target'],     ['I','Senang diapresiasi'], ['S','Menghindari konflik'],    ['C','Menghindari kesalahan']],
    [['D','Berinisiatif'],     ['I','Kreatif'],            ['S','Stabil'],                 ['C','Terencana']],
    [['D','Keras pendirian'],  ['I','Impulsif'],           ['S','Menunda konfrontasi'],    ['C','Ragu tanpa data']],
    [['D','Berani ambil risiko'],['I','Suka kenalan baru'],['S','Butuh kepastian'],        ['C','Butuh standar jelas']],
    [['D','Menuntut'],         ['I','Ramai'],              ['S','Sabar mendengar keluhan'],['C','Cermat']],
    [['D','Tangguh'],          ['I','Menyenangkan'],       ['S','Tulus'],                  ['C','Objektif']],
    [['D','Cepat bertindak'],  ['I','Mudah percaya orang'],['S','Sabar mengulang'],        ['C','Suka cek ulang']],
    [['D','Suka bersaing'],    ['I','Suka tampil'],        ['S','Suka kerja sama'],        ['C','Fokus sendiri saat kerja']],
    [['D','Bicara tegas'],     ['I','Bicara berapi-api'],  ['S','Bicara pelan'],           ['C','Bicara seperlunya']],
];

/**
 * Kalimat pertanyaan untuk tiap kelompok, ditulis dalam konteks kerja
 * sehari-hari di perusahaan. Urutannya mengikuti $config['disc_soal'].
 * Bebas diubah HRD lewat halaman Susun DISC test.
 */
$config['disc_pertanyaan'] = [
    'Saat kamu dapat tugas baru yang belum jelas caranya, seberapa menggambarkan kamu?',
    'Saat kamu bekerja bareng tim yang baru dikenal, seberapa menggambarkan kamu?',
    'Saat harus mengambil keputusan cepat tanpa data lengkap, seberapa menggambarkan kamu?',
    'Saat pekerjaan menumpuk dan tenggatnya dekat, seberapa menggambarkan kamu?',
    'Saat target divisi kamu sedang dikejar, seberapa menggambarkan kamu?',
    'Saat menyampaikan pendapat di rapat, seberapa menggambarkan kamu?',
    'Saat rekan kerja minta bantuan padahal kamu sendiri sibuk, seberapa menggambarkan kamu?',
    'Saat diminta menjelaskan hasil kerja ke atasan, seberapa menggambarkan kamu?',
    'Saat rencana kerja berubah mendadak, seberapa menggambarkan kamu?',
    'Saat kamu memimpin sebuah pekerjaan bersama, seberapa menggambarkan kamu?',
    'Saat kamu tidak setuju dengan keputusan tim, seberapa menggambarkan kamu?',
    'Saat perusahaan menerapkan cara kerja baru, seberapa menggambarkan kamu?',
    'Saat kamu mempresentasikan ide ke orang yang lebih senior, seberapa menggambarkan kamu?',
    'Saat ada rekan kerja yang sedang kesulitan, seberapa menggambarkan kamu?',
    'Saat mengatur pekerjaan yang melibatkan banyak orang, seberapa menggambarkan kamu?',
    'Saat hasil kerja kamu dikoreksi atasan, seberapa menggambarkan kamu?',
    'Saat kamu memulai proyek dari nol, seberapa menggambarkan kamu?',
    'Saat terjadi salah paham dengan rekan kerja, seberapa menggambarkan kamu?',
    'Saat ada peluang baru yang belum tentu berhasil, seberapa menggambarkan kamu?',
    'Saat menghadapi keluhan dari rekan atau pelanggan, seberapa menggambarkan kamu?',
    'Saat pekerjaan sedang berat dan melelahkan, seberapa menggambarkan kamu?',
    'Saat mengerjakan hal yang harus teliti dan berulang, seberapa menggambarkan kamu?',
    'Saat bekerja dalam tim besar lintas divisi, seberapa menggambarkan kamu?',
    'Saat kamu menyampaikan sesuatu yang penting ke tim, seberapa menggambarkan kamu?',
];

$config['disc_profil'] = [
    'D' => [
        'nama'     => 'Dominance',
        'julukan'  => 'Penggerak',
        'ringkas'  => 'Fokus pada hasil dan kecepatan. Nyaman mengambil keputusan dan memegang kendali.',
        'kuat'     => 'Cepat bertindak, berani ambil keputusan sulit, tahan tekanan, jelas menyampaikan maksud.',
        'hati'     => 'Bisa terkesan kurang sabar, mudah menyerobot proses, kadang mengabaikan detail dan perasaan tim.',
        'cara_kerja'=> 'Beri sasaran dan wewenang, jangan bertele-tele. Ia butuh tantangan, bukan pengawasan.',
        'warna'    => '#B4453A',
    ],
    'I' => [
        'nama'     => 'Influence',
        'julukan'  => 'Penggerak Suasana',
        'ringkas'  => 'Fokus pada orang dan antusiasme. Kuat membangun relasi dan meyakinkan orang lain.',
        'kuat'     => 'Komunikatif, optimis, mudah membangun jaringan, menghidupkan suasana tim.',
        'hati'     => 'Kurang telaten pada detail dan administrasi, mudah teralihkan, bisa terlalu optimis soal waktu.',
        'cara_kerja'=> 'Beri panggung dan pengakuan, pasangkan dengan orang yang rapi soal detail dan tenggat.',
        'warna'    => '#C98A1E',
    ],
    'S' => [
        'nama'     => 'Steadiness',
        'julukan'  => 'Penjaga Ritme',
        'ringkas'  => 'Fokus pada kestabilan dan kerja sama. Konsisten dan bisa diandalkan jangka panjang.',
        'kuat'     => 'Sabar, loyal, pendengar yang baik, menjaga tim tetap tenang saat ramai.',
        'hati'     => 'Sulit menolak, lambat menerima perubahan mendadak, cenderung memendam ketidaksetujuan.',
        'cara_kerja'=> 'Jelaskan perubahan lebih awal, beri waktu menyesuaikan, tanya pendapatnya secara langsung.',
        'warna'    => '#2E7D64',
    ],
    'C' => [
        'nama'     => 'Compliance',
        'julukan'  => 'Penjaga Standar',
        'ringkas'  => 'Fokus pada ketepatan dan aturan. Bekerja dengan data, standar, dan prosedur yang jelas.',
        'kuat'     => 'Teliti, disiplin, menemukan kesalahan sebelum jadi masalah, kualitas kerja konsisten.',
        'hati'     => 'Bisa terlalu lama menganalisa, sulit mengambil keputusan tanpa data lengkap, terkesan kaku.',
        'cara_kerja'=> 'Beri kriteria dan data yang jelas, hindari perubahan mendadak tanpa alasan tertulis.',
        'warna'    => '#3B5EA8',
    ],
];
