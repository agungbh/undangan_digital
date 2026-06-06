<?php
// =========================================================================
// 1. KONEKSI & PROSES BACKEND (ANTI-SQLI, FILTER KATA KASAR & XSS)
// =========================================================================
$host     = "localhost"; 
$username = "root"; 
$password = ""; 
$database = "db_undangan";
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) { 
    die("Koneksi database gagal: " . mysqli_connect_error()); 
}

// Ambil seluruh data konfigurasi dari tabel pengaturan
$settings = [];
$res_config = mysqli_query($conn, "SELECT * FROM pengaturan");
while ($row = mysqli_fetch_assoc($res_config)) { 
    $settings[$row['kunci']] = $row['nilai']; 
}

// Konfigurasi target countdown dinamis
$date_parts = explode('-', $settings['tanggal_acara'] ?? '2026-12-12'); 
$c_year  = $date_parts[0] ?? 2026;
$c_month = isset($date_parts[1]) ? intval($date_parts[1]) - 1 : 11; 
$c_day   = $date_parts[2] ?? 12;

// Proses Simpan Ucapan / Balasan Tamu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kirim_ucapan'])) {
    $nama      = trim($_POST['nama']);
    $kehadiran = isset($_POST['kehadiran']) ? trim($_POST['kehadiran']) : 'Hadir'; 
    $pesan     = trim($_POST['pesan']);
    $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null;

    // FILTER KATA KOTOR
    $kata_kotor = ["ewe", "anjing", "babi", "setan", "bangsat", "bugil", "belah", "kontol", "itil", "kanjut"];
    $teks_diperiksa = strtolower($nama . " " . $pesan);
    foreach ($kata_kotor as $kata) {
        if (preg_match("/\b" . preg_quote($kata, '/') . "\b/i", $teks_diperiksa)) {
            echo "<script>alert('Mohon gunakan bahasa yang sopan.'); window.history.back();</script>";
            exit;
        }
    }

    if ($parent_id !== null) {
        $query = "INSERT INTO ucapan (parent_id, nama, kehadiran, pesan) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isss", $parent_id, $nama, $kehadiran, $pesan);
    } else {
        $query = "INSERT INTO ucapan (nama, kehadiran, pesan) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sss", $nama, $kehadiran, $pesan);
    }
    
    if ($stmt) {
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Pesan terkirim!'); window.location.href='index.php?to=".urlencode($_GET['to'] ?? '')."';</script>";
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}

// LOG COUNTER STATISTIK
$total_comments = 0; $total_hadir = 0; $total_tidak_hadir = 0;
$row_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM ucapan WHERE parent_id IS NULL"));
$total_comments = $row_total['total'] ?? 0;
$row_hadir = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM ucapan WHERE kehadiran = 'Hadir' AND parent_id IS NULL"));
$total_hadir = $row_hadir['total'] ?? 0;
$row_tidak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM ucapan WHERE kehadiran = 'Tidak Hadir' AND parent_id IS NULL"));
$total_tidak_hadir = $row_tidak['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Wedding of <?= htmlspecialchars($settings['nama_pria'] ?? 'Pria'); ?> & <?= htmlspecialchars($settings['nama_wanita'] ?? 'Wanita'); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Montserrat', sans-serif; color: #444; background-color: #fcfbf7; overflow-x: hidden; }
        
        #cover { 
            background: linear-gradient(rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0.65)), url('<?= (!empty($settings['bg_cover']) && file_exists($settings['bg_cover'])) ? $settings['bg_cover'] : 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1920'; ?>') no-repeat center center; 
            background-size: cover; height: 100vh; position: fixed; width: 100%; top: 0; left: 0; z-index: 9999; 
            transition: transform 0.8s cubic-bezier(0.77, 0, 0.175, 1); 
        }
        
        .font-aesthetic { font-family: 'Great Vibes', cursive; }
        .text-gold { color: #c5a880 !important; }
        .btn-gold { background-color: #c5a880; color: white; border: none; padding: 10px 25px; border-radius: 25px; font-weight: 600; transition: 0.3s; }
        .btn-gold:hover { background-color: #b3946b; color: white; }
        
        /* DESIGN KARTU MEMPELAI ELEGAN VERTIKAL MELENGKUNG (IMAGE_013EDB.JPG) */
        .mempelai-card {
            width: 100%; max-width: 260px; height: 360px;
            border-radius: 24px; border: 4px solid #ffffff;
            object-fit: cover; object-position: center;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.15), 0px 1px 3px rgba(0, 0, 0, 0.05);
            transition: transform 0.4s ease;
        }
        .mempelai-card:hover { transform: translateY(-5px); }
        .font-serif-mempelai { font-family: 'Playfair Display', Georgia, serif; font-size: 1.75rem; color: #2c3e50; font-weight: 700; }
        .ortu-text { font-family: 'Playfair Display', serif; font-style: italic; font-size: 0.95rem; color: #666; }
        
        .section-acara { background: linear-gradient(rgba(20, 20, 20, 0.95), rgba(20, 20, 20, 0.95)), url('https://www.transparenttextures.com/patterns/black-paper.png'); }
        #music-control { position: fixed; bottom: 20px; right: 20px; z-index: 999; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; box-shadow: 0px 4px 10px rgba(0,0,0,0.3); }
        
        .gallery-img-wrapper { width: 100%; height: 280px; overflow: hidden; border-radius: 15px; box-shadow: 0px 4px 20px rgba(0,0,0,0.08); }
        .gallery-img { width: 100%; height: 100%; object-fit: cover; object-position: center; }
        
        .bg-gold-light { background-color: #f7f3ec; } 
        .text-gold-dark { color: #a48459; font-size: 0.75rem; } 
        .tracking { letter-spacing: 3px; }
        
        .quote-text { font-size: 0.9rem; line-height: 1.8; font-style: italic; }
        
        .counter-box { border-radius: 12px; font-weight: 600; }
        .bg-hadir-light { background-color: #e2f6e9; color: #1f6e43; }
        .bg-tidak-light { background-color: #ffebe9; color: #d9381e; }
        .reply-box { margin-left: 3rem; background-color: #f1f0ea; border-left: 3px solid #c5a880; border-radius: 4px; padding: 8px 12px; }

        @media (min-width: 576px) {
            .mempelai-card { max-width: 290px; height: 400px; }
            .gallery-img-wrapper { height: 450px; }
            #music-control { bottom: 30px; right: 30px; width: 50px; height: 50px; }
            .quote-text { font-size: 1rem; }
        }
    </style>
</head>
<body>

    <audio id="backsound" loop>
        <source src="<?= !empty($settings['backsound']) ? $settings['backsound'] : 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3'; ?>" type="audio/mp3">
    </audio>
    
    <button id="music-control" class="btn btn-gold rounded-circle" onclick="toggleMusic()" style="display: none;"><i id="music-icon" class="fa-solid fa-disc fa-spin"></i></button>

    <section id="cover" class="d-flex align-items-center justify-content-center text-center text-white px-3">
        <div>
            <h5 class="text-uppercase tracking small">The Wedding of</h5>
            <h1 class="display-3 my-4 font-aesthetic text-gold"><?= htmlspecialchars($settings['nama_pria'] ?? ''); ?> & <?= htmlspecialchars($settings['nama_wanita'] ?? ''); ?></h1>
            <p class="lead fs-6">Kepada Yth. Bapak/Ibu/Saudara/i: <br>
               <strong class="fs-4 text-white d-block mt-2"><?= isset($_GET['to']) ? htmlspecialchars($_GET['to']) : 'Tamu Undangan'; ?></strong>
            </p>
            <button class="btn btn-gold mt-2 shadow-sm" onclick="bukaUndangan()"><i class="fa-solid fa-envelope-open me-2"></i> Buka Undangan</button>
        </div>
    </section>

    <div id="main-content" style="display: none;">
        
        <section class="container text-center py-5 px-4" data-aos="fade-up" data-aos-duration="1200">
            <div class="row justify-content-center mb-5" data-aos="fade-up" data-aos-delay="100">
                <div class="col-12 col-md-10 col-lg-8">
                    <i class="fa-solid fa-heart text-gold mb-3 fs-4"></i>
                    <p class="text-muted quote-text">
                        “Dan diantara tanda-tanda kekuasaanNya ialah Dia menciptakan untukmu pasangan-pasangan dari jenismu sendiri, supaya kamu cenderung dan merasa tenteram kepadanya, dan dijadikanNya diantaramu rasa kasih dan sayang. Sesungguhnya pada yang demikian itu benar-benar terdapat tanda-tanda bagi kaum yang berpikir.”
                    </p>
                    <small class="fw-semibold text-gold-dark d-block mt-2">(QS. Ar-Rum : 21)</small>
                    <hr class="w-25 mx-auto mt-4 border-secondary opacity-25">
                </div>
            </div>

            <div class="mb-5" data-aos="fade-up" data-aos-delay="200">
                <h2 class="font-aesthetic display-4 text-gold">Mempelai</h2>
                <p class="text-muted small mt-2">Maha suci Allah yang telah menciptakan makhluk-Nya berpasang-pasangan...</p>
            </div>
            
            <div class="row justify-content-center align-items-center g-5">
                <div class="col-12 col-md-5 d-flex flex-column align-items-center" data-aos="slide-right" data-aos-duration="1200" data-aos-delay="300">
                    <img src="<?= (!empty($settings['foto_pria']) && file_exists($settings['foto_pria'])) ? $settings['foto_pria'] : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400'; ?>" class="mempelai-card mb-4" alt="Foto Pria">
                    <h3 class="font-serif-mempelai mb-2"><?= htmlspecialchars($settings['nama_pria'] ?? ''); ?></h3>
                    <p class="ortu-text px-3"><?= htmlspecialchars($settings['ortu_pria'] ?? ''); ?></p>
                </div>
                
                <div class="col-12 col-md-2" data-aos="zoom-in" data-aos-duration="1000" data-aos-delay="400">
                    <h2 class="font-aesthetic display-2 text-gold my-0">&</h2>
                </div>
                
                <div class="col-12 col-md-5 d-flex flex-column align-items-center" data-aos="slide-left" data-aos-duration="1200" data-aos-delay="300">
                    <img src="<?= (!empty($settings['foto_wanita']) && file_exists($settings['foto_wanita'])) ? $settings['foto_wanita'] : 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400'; ?>" class="mempelai-card mb-4" alt="Foto Wanita">
                    <h3 class="font-serif-mempelai mb-2"><?= htmlspecialchars($settings['nama_wanita'] ?? ''); ?></h3>
                    <p class="ortu-text px-3"><?= htmlspecialchars($settings['ortu_wanita'] ?? ''); ?></p>
                </div>
            </div>
        </section>

        <section class="section-acara text-white py-5 px-3" data-aos="fade-up" data-aos-duration="1200">
            <div class="container text-center">
                <h2 class="font-aesthetic text-gold display-4 mb-4" data-aos="fade-down" data-aos-duration="1000">Rangkaian Acara</h2>
                
                <div class="row justify-content-center mb-5" data-aos="zoom-in" data-aos-delay="100">
                    <div class="col-12 col-md-9 col-lg-7">
                        <div class="row g-2 text-dark text-center">
                            <div class="col-3"><div class="p-2 p-sm-3 bg-white rounded shadow-sm"><h4 id="days" class="fw-bold mb-0 fs-5">00</h4><small style="font-size:0.7rem">Hari</small></div></div>
                            <div class="col-3"><div class="p-2 p-sm-3 bg-white rounded shadow-sm"><h4 id="hours" class="fw-bold mb-0 fs-5">00</h4><small style="font-size:0.7rem">Jam</small></div></div>
                            <div class="col-3"><div class="p-2 p-sm-3 bg-white rounded shadow-sm"><h4 id="minutes" class="fw-bold mb-0 fs-5">00</h4><small style="font-size:0.7rem">Menit</small></div></div>
                            <div class="col-3"><div class="p-2 p-sm-3 bg-white rounded shadow-sm"><h4 id="seconds" class="fw-bold mb-0 fs-5">00</h4><small style="font-size:0.7rem">Detik</small></div></div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 text-dark text-start mb-5 justify-content-center">
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="card h-100 border-0 shadow-sm" data-aos="slide-right" data-aos-delay="200" data-aos-duration="1100">
                            <div class="card-body p-4 text-center">
                                <i class="fa-solid fa-ring text-gold fs-2 mb-3"></i>
                                <h4 class="h5 fw-bold">Akad Nikah</h4>
                                <p class="text-muted small mb-1"><i class="fa-regular fa-calendar me-2"></i> <?= htmlspecialchars($settings['tanggal_acara'] ?? ''); ?></p>
                                <p class="text-muted small mb-3"><i class="fa-regular fa-clock me-2"></i> <?= htmlspecialchars($settings['jam_akad'] ?? ''); ?></p>
                                <p class="fw-semibold mb-0 small"><?= htmlspecialchars($settings['nama_gedung'] ?? ''); ?></p>
                                <p class="text-muted px-2" style="font-size:0.8rem;"><?= htmlspecialchars($settings['alamat_gedung'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="card h-100 border-0 shadow-sm" data-aos="slide-left" data-aos-delay="200" data-aos-duration="1100">
                            <div class="card-body p-4 text-center">
                                <i class="fa-solid fa-champagne-glasses text-gold fs-2 mb-3"></i>
                                <h4 class="h5 fw-bold">Resepsi</h4>
                                <p class="text-muted small mb-1"><i class="fa-regular fa-calendar me-2"></i> <?= htmlspecialchars($settings['tanggal_acara'] ?? ''); ?></p>
                                <p class="text-muted small mb-3"><i class="fa-regular fa-clock me-2"></i> <?= htmlspecialchars($settings['jam_resepsi'] ?? ''); ?></p>
                                <p class="fw-semibold mb-0 small"><?= htmlspecialchars($settings['nama_gedung'] ?? ''); ?></p>
                                <p class="text-muted px-2" style="font-size:0.8rem;"><?= htmlspecialchars($settings['alamat_gedung'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-12 col-lg-10" data-aos="zoom-in" data-aos-duration="1200" data-aos-delay="100">
                        <h3 class="font-aesthetic text-gold mb-3 h4">Lokasi Acara</h3>
                        <div class="shadow-sm rounded overflow-hidden border border-secondary ratio ratio-16x9" style="max-height: 350px;">
                            <iframe src="<?= htmlspecialchars($settings['link_maps'] ?? ''); ?>" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-5 bg-white text-center px-3" data-aos="fade-up" data-aos-duration="1200">
            <div class="container" style="max-width: 750px;">
                <h2 class="font-aesthetic text-gold display-4 mb-2" data-aos="fade-down" data-aos-duration="1000">Galeri Foto</h2>
                <p class="text-muted small mb-4" data-aos="fade-up" data-aos-delay="100">Momen Indah Kami</p>
                <div id="weddingGallery" class="carousel slide shadow-sm rounded-4 overflow-hidden" data-bs-ride="carousel" data-aos="zoom-in" data-aos-delay="200" data-aos-duration="1100">
                    <div class="carousel-indicators">
                        <?php for($i=0; $i<5; $i++): ?>
                            <button type="button" data-bs-target="#weddingGallery" data-bs-slide-to="<?= $i; ?>" class="<?= ($i === 0) ? 'active' : ''; ?>"></button>
                        <?php endfor; ?>
                    </div>
                    <div class="carousel-inner">
                        <?php 
                        for($i=1; $i<=5; $i++): 
                            $img_src = (!empty($settings['foto_galeri_'.$i]) && file_exists($settings['foto_galeri_'.$i])) ? $settings['foto_galeri_'.$i] : 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800';
                        ?>
                            <div class="carousel-item <?= ($i === 1) ? 'active' : ''; ?>">
                                <div class="gallery-img-wrapper">
                                    <img src="<?= $img_src; ?>" class="gallery-img" alt="Foto Galeri <?= $i; ?>">
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#weddingGallery" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#weddingGallery" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                </div>
            </div>
        </section>

        <section class="py-5 text-center bg-light px-3" data-aos="fade-up" data-aos-duration="1200">
            <div class="container" style="max-width: 500px;">
                <h2 class="font-aesthetic text-gold display-5 mb-3" data-aos="fade-down" data-aos-duration="1000">Wedding Gift</h2>
                <p class="text-muted small mb-4" data-aos="fade-up" data-aos-delay="100">Kirim tanda kasih melalui rekening/e-wallet berikut:</p>
                <div class="row g-3 justify-content-center">
                    <?php
                    $get_all_reks = mysqli_query($conn, "SELECT * FROM rekening ORDER BY id ASC");
                    if(mysqli_num_rows($get_all_reks) > 0):
                        while($rek_data = mysqli_fetch_assoc($get_all_reks)):
                    ?>
                        <div class="col-12" data-aos="slide-up" data-aos-duration="1000">
                            <div class="card border-0 bg-white p-3 shadow-sm rounded-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="text-start d-flex align-items-center">
                                        <?php if(!empty($rek_data['logo_bank']) && file_exists($rek_data['logo_bank'])): ?>
                                            <img src="<?= $rek_data['logo_bank']; ?>" style="max-height: 30px; max-width: 65px;" class="me-3">
                                        <?php endif; ?>
                                        <div>
                                            <span class="badge bg-secondary mb-1" style="font-size:0.65rem;"><?= htmlspecialchars($rek_data['nama_bank']); ?></span>
                                            <h5 id="norek_<?= $rek_data['id']; ?>" class="mb-0 fw-bold text-dark h6"><?= htmlspecialchars($rek_data['norek'] ?? ''); ?></h5>
                                            <small class="text-muted" style="font-size:0.75rem;">a.n. <?= htmlspecialchars($rek_data['pemilik'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary px-3 ms-auto" onclick="copyText('norek_<?= $rek_data['id']; ?>', this)">Salin</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; else: echo '<p class="text-muted small">Hubungi mempelai.</p>'; endif; ?>
                </div>
            </div>
        </section>

        <section class="bg-white py-5 px-3" data-aos="fade-up" data-aos-duration="1200">
            <div class="container" style="max-width: 580px;">
                <h2 class="text-center font-aesthetic text-gold display-5 mb-4" data-aos="fade-down" data-aos-duration="1000">Berikan Ucapan</h2>
                
                <form action="" method="POST" id="mainCommentForm" class="mb-5 p-4 bg-light rounded-3 shadow-sm" data-aos="zoom-in" data-aos-duration="1100">
                    <input type="hidden" name="parent_id" id="parent_id_field" value="">
                    <div id="reply_indicator" class="alert alert-info py-2 px-3 small d-none justify-content-between align-items-center mb-3">
                        <span>Membalas ucapan dari: <strong id="reply_target_name"></strong></span>
                        <button type="button" class="btn-close" style="font-size:0.75rem;" onclick="cancelReply()"></button>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Nama Anda</label><input type="text" name="nama" class="form-control form-control-sm" required placeholder="Nama Lengkap"></div>
                    <div class="mb-3" id="kehadiran_box"><label class="form-label small fw-semibold">Konfirmasi Kehadiran</label><select name="kehadiran" class="form-select form-select-sm"><option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option></select></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Ucapan / Doa</label><textarea name="pesan" id="pesan_textarea" class="form-control form-control-sm" rows="3" required placeholder="Tulis doa restu..."></textarea></div>
                    <button type="submit" name="kirim_ucapan" class="btn btn-gold btn-sm w-100 py-2"><i class="fa-regular fa-paper-plane me-2"></i> Kirim</button>
                </form>

                <div class="row g-2 mb-4 text-center small fw-bold" data-aos="fade-up" data-aos-delay="100">
                    <div class="col-12 text-muted mb-1"><?= $total_comments; ?> Comments</div>
                    <div class="col-6"><div class="p-3 bg-hadir-light counter-box shadow-sm"><h4 class="mb-0 fw-bold"><?= $total_hadir; ?></h4>Hadir</div></div>
                    <div class="col-6"><div class="p-3 bg-tidak-light counter-box shadow-sm"><h4 class="mb-0 fw-bold"><?= $total_tidak_hadir; ?></h4>Tidak Hadir</div></div>
                </div>

                <div class="ucapan-box" style="max-height: 500px; overflow-y: auto;" data-aos="fade-up" data-aos-delay="200">
                    <?php 
                    $query_parent = "SELECT * FROM ucapan WHERE parent_id IS NULL ORDER BY id DESC";
                    $res_parent = mysqli_query($conn, $query_parent);
                    while($p = mysqli_fetch_assoc($res_parent)): 
                    ?>
                        <div class="mb-3 border-bottom pb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-dark small"><?= htmlspecialchars($p['nama']); ?></strong>
                                <span class="badge bg-gold-light text-gold-dark small"><?= htmlspecialchars($p['kehadiran']); ?></span>
                            </div>
                            <p class="text-muted my-1 small px-1"><?= nl2br(htmlspecialchars($p['pesan'])); ?></p>
                            
                            <div class="text-start px-1">
                                <button type="button" class="btn btn-link p-0 text-decoration-none text-gold fw-bold" style="font-size:0.75rem;" onclick="setReply(<?= $p['id']; ?>, '<?= htmlspecialchars($p['nama'], ENT_QUOTES); ?>')"><i class="fa-solid fa-reply me-1"></i> Balas</button>
                            </div>

                            <?php 
                            $parent_id_check = $p['id'];
                            $res_replies = mysqli_query($conn, "SELECT * FROM ucapan WHERE parent_id = $parent_id_check ORDER BY id ASC");
                            while($r = mysqli_fetch_assoc($res_replies)):
                            ?>
                                <div class="reply-box mt-2 shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="text-dark" style="font-size:0.75rem;"><i class="fa-solid fa-turn-up fa-rotate-90 me-1 text-muted"></i> <?= htmlspecialchars($r['nama']); ?> <span class="text-muted fw-normal">(Mempelai/Keluarga)</span></strong>
                                    </div>
                                    <p class="text-muted my-1 mb-0" style="font-size:0.75rem;"><?= nl2br(htmlspecialchars($r['pesan'])); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // AOS diinisialisasi agar dinamis memicu animasi saat scroll-down dan up
        AOS.init({ 
            once: false, 
            mirror: true 
        });

        var audio = document.getElementById("backsound"); var m_ctrl = document.getElementById("music-control");
        function bukaUndangan() { 
            audio.play().catch(e=>1); 
            m_ctrl.style.display="block"; 
            document.getElementById("cover").style.transform="translateY(-100vh)"; 
            document.getElementById("main-content").style.display="block"; 
            setTimeout(() => { 
                document.getElementById("cover").style.display="none"; 
                window.scrollTo(0,0); 
                AOS.refresh(); 
            }, 800); 
        }
        function toggleMusic() { if (audio.paused) { audio.play(); } else { audio.pause(); } }
        
        function setReply(id, name) {
            document.getElementById('parent_id_field').value = id;
            document.getElementById('reply_target_name').innerText = name;
            document.getElementById('reply_indicator').classList.remove('d-none');
            document.getElementById('reply_indicator').classList.add('d-flex');
            document.getElementById('kehadiran_box').style.display = 'none'; 
            document.getElementById('pesan_textarea').placeholder = "Tulis balasan ucapan Anda ke " + name + "...";
            document.getElementById('mainCommentForm').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelReply() {
            document.getElementById('parent_id_field').value = '';
            document.getElementById('reply_indicator').classList.remove('d-flex');
            document.getElementById('reply_indicator').classList.add('d-none');
            document.getElementById('kehadiran_box').style.display = 'block';
            document.getElementById('pesan_textarea').placeholder = "Tulis doa restu...";
        }

        var targetDate = new Date(<?= $c_year; ?>, <?= $c_month; ?>, <?= $c_day; ?>, 8, 0, 0).getTime();
        setInterval(function() {
            var now = new Date().getTime(); var distance = targetDate - now; if (distance < 0) return;
            document.getElementById("days").innerText = Math.floor(distance / (1000*60*60*24));
            document.getElementById("hours").innerText = Math.floor((distance % (1000*60*60*24)) / (1000*60*60));
            document.getElementById("minutes").innerText = Math.floor((distance % (1000*60*60)) / (1000*60));
            document.getElementById("seconds").innerText = Math.floor((distance % (1000*60)) / 1000);
        }, 1000);
        function copyText(id, btn) { navigator.clipboard.writeText(document.getElementById(id).innerText); btn.innerText="✓"; setTimeout(()=>btn.innerText="Salin", 2000); }
    </script>
</body>
</html>