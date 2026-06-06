<?php
session_start();
$host     = "localhost"; $username = "root"; $password = ""; $database = "db_undangan";
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) { die("Koneksi gagal: " . mysqli_connect_error()); }

$users_list = [
    'admin' => ['password' => 'admin123', 'role' => 'superadmin'],
    'user'  => ['password' => 'user123', 'role' => 'regular']
];

if (isset($_POST['login'])) {
    $input_user = $_POST['username'] ?? ''; $input_pass = $_POST['password'] ?? '';
    if (array_key_exists($input_user, $users_list) && $users_list[$input_user]['password'] === $input_pass) {
        $_SESSION['loggedin'] = true; $_SESSION['username'] = $input_user; $_SESSION['role'] = $users_list[$input_user]['role'];
    } else { $error_login = "Username atau password salah!"; }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit; }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true):
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login Panel Akses</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center" style="height: 100vh;"><div class="container px-4"><div class="row justify-content-center"><div class="col-12 col-sm-8 col-md-6 col-lg-4"><div class="card shadow border-0 rounded-3"><div class="card-body p-4"><h5 class="text-center mb-4 fw-bold">Admin Login</h5><?php if(isset($error_login)): ?><div class="alert alert-danger text-center small"><?= $error_login; ?></div><?php endif; ?><form action="" method="POST"><div class="mb-3"><label class="form-label small">Username</label><input type="text" name="username" class="form-control form-control-sm" required></div><div class="mb-3"><label class="form-label small">Password</label><input type="password" name="password" class="form-control form-control-sm" required></div><button type="submit" name="login" class="btn btn-dark btn-sm w-100 py-2">Masuk</button></form></div></div></div></div></div></body>
</html>
<?php exit; endif; 

$user_role = $_SESSION['role'] ?? 'regular'; $success_msg = "";

// =========================================================================
// BACKEND PROCESSOR: CRUD & REPLY BUKU UCAPAN (DENGAN BEBAS ERROR LINE 63)
// =========================================================================
if (isset($_GET['hapus_ucapan'])) {
    $id_ucapan = intval($_GET['hapus_ucapan']);
    mysqli_query($conn, "DELETE FROM ucapan WHERE parent_id = $id_ucapan");
    mysqli_query($conn, "DELETE FROM ucapan WHERE id = $id_ucapan");
    $success_msg = "Data ucapan berhasil dihapus bersih dari sistem!";
}

if (isset($_GET['hapus_balasan'])) {
    $id_balasan = intval($_GET['hapus_balasan']);
    mysqli_query($conn, "DELETE FROM ucapan WHERE id = $id_balasan");
    $success_msg = "Balasan spesifik berhasil dihapus!";
}

if (isset($_POST['update_ucapan'])) {
    $id_ucapan = intval($_POST['id_ucapan']);
    $nama_tamu = mysqli_real_escape_string($conn, $_POST['edit_nama'] ?? '');
    $kehadiran = mysqli_real_escape_string($conn, $_POST['edit_kehadiran'] ?? 'Hadir');
    $pesan_tamu = mysqli_real_escape_string($conn, $_POST['edit_pesan'] ?? '');
    mysqli_query($conn, "UPDATE ucapan SET nama = '$nama_tamu', kehadiran = '$kehadiran', pesan = '$pesan_tamu' WHERE id = $id_ucapan");
    $success_msg = "Perubahan data respons berhasil disimpan!";
}

// SOLUSI UTAMA PERBAIKAN WARNING UNDEFINED ARRAY KEY & DEPRECATED WARNING
if (isset($_POST['tambah_ucapan_manual'])) {
    $nama_tamu = mysqli_real_escape_string($conn, $_POST['add_nama'] ?? 'Mempelai ✨');
    $pesan_tamu = mysqli_real_escape_string($conn, $_POST['add_pesan'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    
    // Berikan validasi agar jika add_kehadiran kosong/tidak dikirim (mode reply), default diisi "Hadir"
    $kehadiran = mysqli_real_escape_string($conn, $_POST['add_kehadiran'] ?? 'Hadir');

    if($parent_id !== null) {
        mysqli_query($conn, "INSERT INTO ucapan (parent_id, nama, kehadiran, pesan) VALUES ($parent_id, '$nama_tamu', 'Hadir', '$pesan_tamu')");
        $success_msg = "Balasan untuk ucapan berhasil dikirim!";
    } else {
        mysqli_query($conn, "INSERT INTO ucapan (nama, kehadiran, pesan) VALUES ('$nama_tamu', '$kehadiran', '$pesan_tamu')");
        $success_msg = "Data ucapan manual berhasil direkam!";
    }
}

// =========================================================================
// CONFIG CONTROLLER (SUPERADMIN ONLY)
// =========================================================================
if ($user_role === 'superadmin') {
    if (isset($_POST['tambah_rekening'])) {
        $nama_bank = mysqli_real_escape_string($conn, $_POST['nama_bank'] ?? ''); $norek = mysqli_real_escape_string($conn, $_POST['norek'] ?? ''); $pemilik = mysqli_real_escape_string($conn, $_POST['pemilik'] ?? ''); $logo_path = "";
        if (!empty($_FILES['logo_bank']['name'])) {
            $target_dir = "uploads/logos/"; if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $logo_path = $target_dir . "logo_" . time() . "." . pathinfo($_FILES["logo_bank"]["name"], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES["logo_bank"]["tmp_name"], $logo_path);
        }
        mysqli_query($conn, "INSERT INTO rekening (nama_bank, norek, pemilik, logo_bank) VALUES ('$nama_bank', '$norek', '$pemilik', '$logo_path')"); $success_msg = "Rekening ditambahkan!";
    }
    if (isset($_GET['hapus_rek'])) {
        $id_rek = intval($_GET['hapus_rek']); $res_img = mysqli_query($conn, "SELECT logo_bank FROM rekening WHERE id = $id_rek");
        if($r_img = mysqli_fetch_assoc($res_img)) { if(!empty($r_img['logo_bank']) && file_exists($r_img['logo_bank'])) { unlink($r_img['logo_bank']); } }
        mysqli_query($conn, "DELETE FROM rekening WHERE id = $id_rek"); $success_msg = "Rekening dihapus!";
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_setting'])) {
        if (isset($_POST['config'])) { foreach ($_POST['config'] as $kunci => $nilai) { $k_clean = mysqli_real_escape_string($conn, $kunci); $n_clean = mysqli_real_escape_string($conn, $nilai); mysqli_query($conn, "UPDATE pengaturan SET nilai = '$n_clean' WHERE kunci = '$k_clean'"); } }
        $target_dir_uploads = "uploads/"; if (!is_dir($target_dir_uploads)) { mkdir($target_dir_uploads, 0777, true); }
        
        if (!empty($_FILES['file_bg_cover']['name'])) { $target_file = $target_dir_uploads . "bg_cover_" . time() . "." . pathinfo($_FILES['file_bg_cover']["name"], PATHINFO_EXTENSION); if (move_uploaded_file($_FILES['file_bg_cover']["tmp_name"], $target_file)) { mysqli_query($conn, "UPDATE pengaturan SET nilai = '$target_file' WHERE kunci = 'bg_cover'"); } }
        
        $mempelai_fields = ['foto_pria' => 'file_pria', 'foto_wanita' => 'file_wanita'];
        foreach ($mempelai_fields as $kunci_db => $form_field) { if (!empty($_FILES[$form_field]['name'])) { $target_file = $target_dir_uploads . $form_field . "_" . time() . "." . pathinfo($_FILES[$form_field]["name"], PATHINFO_EXTENSION); if (move_uploaded_file($_FILES[$form_field]["tmp_name"], $target_file)) { mysqli_query($conn, "UPDATE pengaturan SET nilai = '$target_file' WHERE kunci = '$kunci_db'"); } } }
        for ($i = 1; $i <= 5; $i++) { $file_field = "file_galeri_" . $i; if (!empty($_FILES[$file_field]['name'])) { $target_file = $target_dir_uploads . "galeri_" . $i . "_" . time() . "." . pathinfo($_FILES[$file_field]["name"], PATHINFO_EXTENSION); if (move_uploaded_file($_FILES[$file_field]["tmp_name"], $target_file)) { mysqli_query($conn, "INSERT INTO pengaturan (kunci, nilai) VALUES ('foto_galeri_$i', '$target_file') ON DUPLICATE KEY UPDATE nilai = '$target_file'"); } } }
        if (!empty($_FILES['file_backsound']['name'])) { $target_file_audio = "uploads/audio/backsound_" . time() . ".mp3"; if (move_uploaded_file($_FILES['file_backsound']["tmp_name"], $target_file_audio)) { mysqli_query($conn, "UPDATE pengaturan SET nilai = '$target_file_audio' WHERE kunci = 'backsound'"); } }
        $success_msg = "Pengaturan diperbarui!";
    }
}
$settings = []; $res = mysqli_query($conn, "SELECT * FROM pengaturan"); while ($row = mysqli_fetch_assoc($res)) { $settings[$row['kunci']] = $row['nilai']; }
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['SCRIPT_NAME']) . "/index.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> 
        .preview-img { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; border: 2px dashed #ddd; } 
        .preview-avatar { width: 80px; height: 80px; object-fit: cover; border-radius: 50%; } 
        .admin-reply-box { font-size: 0.75rem; background-color: #f1f3f5; border-left: 3px solid #198754; padding: 6px 10px; margin-top: 5px; border-radius: 4px; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark navbar-expand"><div class="container px-3"><span class="navbar-brand small h1 mb-0"><i class="fa-solid fa-sliders me-1"></i> Dashboard (<span class="text-info"><?= htmlspecialchars($_SESSION['username']); ?></span>)</span><a href="?logout=1" class="btn btn-xs btn-outline-danger btn-sm px-2 ms-auto">Keluar</a></div></nav>
    <div class="container my-4 px-3">
        <?php if(!empty($success_msg)): ?><div class="alert alert-success p-2 small shadow-sm"><?= $success_msg; ?></div><?php endif; ?>
        <div class="row g-3">
            
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm p-3 mb-3">
                    <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Generator Tautan Undangan</h6>
                    <div class="p-3 bg-white rounded border">
                        <div class="mb-3"><label class="form-label small fw-bold">Nama Tamu</label><input type="text" id="input_nama_tamu" class="form-control form-control-sm" placeholder="Contoh: Prof. Agung" oninput="generateGuestLink()"></div>
                        <input type="text" id="hasil_link_undangan" class="form-control form-control-sm bg-light mb-3" readonly>
                        <div class="d-flex gap-2"><button class="btn btn-sm btn-secondary" onclick="copyGuestLink()">Salin Tautan</button><a id="btn_share_wa" href="#" target="_blank" class="btn btn-sm btn-success disabled"><i class="fa-brands fa-whatsapp me-1"></i> WhatsApp</a></div>
                    </div>
                </div>

                <?php if ($user_role === 'superadmin'): ?>
                <div class="card border-0 shadow-sm p-3 mb-3">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">Data Pokok Undangan</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6"><label class="small">Nama Pria</label><input type="text" name="config[nama_pria]" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['nama_pria']??'');?>"></div>
                            <div class="col-sm-6"><label class="small">Nama Wanita</label><input type="text" name="config[nama_wanita]" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['nama_wanita']??'');?>"></div>
                            <div class="col-12"><label class="small">Iframe URL Maps src</label><input type="text" id="link_maps" name="config[link_maps]" class="form-control form-control-sm" value="<?=htmlspecialchars($settings['link_maps']??'');?>"></div>
                        </div>
                        <h6 class="text-primary fw-bold border-bottom pb-2 mb-2">Upload 5 Gambar Galeri</h6>
                        <div class="row g-2 mb-3 row-cols-5">
                            <?php for($i=1;$i<=5;$i++): ?>
                                <div class="col text-center"><img id="prev_g_<?=$i;?>" src="<?=$settings['foto_galeri_'.$i]??'';?>" class="preview-img mb-1"><input type="file" name="file_galeri_<?=$i;?>" class="form-control form-control-sm" onchange="previewImage(this,'prev_g_<?=$i;?>')" style="font-size:0.6rem;"></div>
                            <?php endfor; ?>
                        </div>
                        <div class="mb-2"><label class="small fw-bold">Ganti Backsound (.mp3)</label><input type="file" name="file_backsound" class="form-control form-control-sm" accept=".mp3"></div>
                        <button type="submit" name="update_setting" class="btn btn-primary btn-sm w-100 mt-2">Simpan Perubahan</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-comments me-2 text-primary"></i>Daftar Buku Ucapan</h6>
                        <button class="btn btn-xs btn-outline-secondary py-0 px-2 small" style="font-size: 0.7rem;" data-bs-toggle="modal" data-bs-target="#modalEditUcapan" data-mode="add"><i class="fa-solid fa-plus"></i> Manual</button>
                    </div>
                    <hr class="my-1">
                    
                    <div style="max-height: 650px; overflow-y: auto;" class="pe-1">
                        <?php 
                        $res_u = mysqli_query($conn, "SELECT * FROM ucapan WHERE parent_id IS NULL ORDER BY id DESC"); 
                        if(mysqli_num_rows($res_u) > 0):
                            while($r = mysqli_fetch_assoc($res_u)): $bc = ($r['kehadiran'] == 'Hadir') ? 'bg-success' : 'bg-danger'; 
                        ?>
                            <div class="p-3 mb-2 bg-white rounded border shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="small text-truncate" style="max-width:140px;"><?= htmlspecialchars($r['nama']); ?></strong>
                                    <span class="badge <?= $bc; ?>" style="font-size:0.6rem;"><?= $r['kehadiran']; ?></span>
                                </div>
                                <p class="text-muted mb-2 lh-sm" style="font-size:0.75rem;">"<?= htmlspecialchars($r['pesan']); ?>"</p>
                                
                                <?php 
                                $pid = $r['id'];
                                $rep_res = mysqli_query($conn, "SELECT * FROM ucapan WHERE parent_id = $pid ORDER BY id ASC");
                                while($rp = mysqli_fetch_assoc($rep_res)):
                                ?>
                                    <div class="admin-reply-box position-relative">
                                        <div class="d-flex justify-content-between">
                                            <strong>↳ <?= htmlspecialchars($rp['nama']); ?>:</strong>
                                            <a href="?hapus_balasan=<?= $rp['id']; ?>" class="text-danger border-0 p-0 ms-2 text-decoration-none" style="font-size: 0.65rem;" onclick="return confirm('Hapus balasan ini?')"><i class="fa-solid fa-xmark"></i></a>
                                        </div>
                                        <span class="d-block text-secondary mt-1">"<?= htmlspecialchars($rp['pesan']); ?>"</span>
                                    </div>
                                <?php endwhile; ?>

                                <div class="d-flex gap-3 justify-content-end border-top pt-2 mt-2" style="font-size:0.7rem;">
                                    <button class="btn btn-link p-0 text-success text-decoration-none small fw-bold" style="font-size:0.7rem;" data-bs-toggle="modal" data-bs-target="#modalReplyAdmin" data-id="<?= $r['id']; ?>" data-nama="<?= htmlspecialchars($r['nama']); ?>"><i class="fa-solid fa-reply me-1"></i>Balas</button>
                                    <button class="btn btn-link p-0 text-primary text-decoration-none small" style="font-size:0.7rem;" data-bs-toggle="modal" data-bs-target="#modalEditUcapan" data-mode="edit" data-id="<?= $r['id']; ?>" data-nama="<?= htmlspecialchars($r['nama']); ?>" data-kehadiran="<?= $r['kehadiran']; ?>" data-pesan="<?= htmlspecialchars($r['pesan']); ?>"><i class="fa-regular fa-edit me-1"></i>Edit</button>
                                    <a href="?hapus_ucapan=<?= $r['id']; ?>" class="btn btn-link p-0 text-danger text-decoration-none small" style="font-size:0.7rem;" onclick="return confirm('Hapus ucapan beserta seluruh balasannya?')"><i class="fa-regular fa-trash-can me-1"></i>Hapus</a>
                                </div>
                            </div>
                        <?php endwhile; else: echo '<p class="text-center text-muted small py-3">Belum ada komentar.</p>'; endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="modalReplyAdmin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2"><h6 class="modal-title small fw-bold"><i class="fa-solid fa-reply me-2"></i>Balas Ucapan Tamu</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <form action="" method="POST">
                    <input type="hidden" name="parent_id" id="reply_pid">
                    <div class="modal-body p-3">
                        <div class="mb-2 small text-muted">Membalas Pesan Dari: <strong id="reply_name_target" class="text-dark"></strong></div>
                        <div class="mb-2"><label class="form-label small fw-bold">Nama Pengirim Balasan</label><input type="text" name="add_nama" class="form-control form-control-sm" value="Mempelai ✨" required></div>
                        <div class="mb-1"><label class="form-label small fw-bold">Isi Balasan</label><textarea name="add_pesan" class="form-control form-control-sm" rows="3" required placeholder="Terima kasih banyak atas kehadiran dan doa restunya..."></textarea></div>
                    </div>
                    <div class="modal-footer p-2 bg-light"><button type="submit" name="tambah_ucapan_manual" class="btn btn-success btn-sm px-3">Kirim Balasan</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditUcapan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2"><h6 class="modal-title small fw-bold" id="m_title">Form Ucapan</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <form action="" method="POST" id="form_universal">
                    <input type="hidden" name="id_ucapan" id="edit_id">
                    <div class="modal-body p-3">
                        <div class="mb-2"><label class="form-label small fw-semibold">Nama Tamu</label><input type="text" name="edit_nama" id="edit_nama" class="form-control form-control-sm" required></div>
                        <div class="mb-2" id="box_keh"><label class="form-label small fw-semibold">Konfirmasi Kehadiran</label><select name="edit_kehadiran" id="edit_kehadiran" class="form-select form-select-sm"><option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option></select></div>
                        <div class="mb-1"><label class="form-label small fw-semibold">Isi Ucapan / Catatan</label><textarea name="edit_pesan" id="edit_pesan" class="form-control form-control-sm" rows="3" required></textarea></div>
                    </div>
                    <div class="modal-footer p-2 bg-light">
                        <button type="submit" id="btn_sub_universal" class="btn btn-sm btn-primary px-3">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var baseUrl = "<?= $base_url; ?>";
        function previewImage(i,t){ if(i.files&&i.files[0]){ var r=new FileReader(); r.onload=function(e){document.getElementById(t).src=e.target.result;}; r.readAsDataURL(i.files[0]); } }
        function getCoupleSlug(){ var p="<?= $settings['nama_pria']??''; ?>", w="<?= $settings['nama_wanita']??''; ?>"; return (p.split(',')[0]+"-"+w.split(',')[0]).toLowerCase().replace(/[^a-z0-9-]/g,"").replace(/\s+/g,"-"); }
        function generateGuestLink(){ var n=document.getElementById('input_nama_tamu').value.trim(), h=document.getElementById('hasil_link_undangan'), b=document.getElementById('btn_share_wa'); if(n!==""){ var fl=baseUrl.replace('index.php','') + getCoupleSlug() + "?to=" + encodeURIComponent(n); h.value=fl; b.href="https://api.whatsapp.com/send?text="+encodeURIComponent("Halo "+n+",\nBerikut undangan digital kami:\n"+fl); b.classList.remove('disabled'); }else{ h.value=""; b.classList.add('disabled'); } }
        function copyGuestLink(){ navigator.clipboard.writeText(document.getElementById("hasil_link_undangan").value); alert('Tersalin!'); }

        var mEdit = document.getElementById('modalEditUcapan');
        if(mEdit) {
            mEdit.addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                var mode = btn.getAttribute('data-mode');
                
                if(mode === 'edit') {
                    document.getElementById('m_title').innerText = "Ubah Data Respons Tamu";
                    document.getElementById('btn_sub_universal').name = "update_ucapan";
                    document.getElementById('edit_id').value = btn.getAttribute('data-id');
                    document.getElementById('edit_nama').name = "edit_nama";
                    document.getElementById('edit_nama').value = btn.getAttribute('data-nama');
                    document.getElementById('edit_kehadiran').name = "edit_kehadiran";
                    document.getElementById('edit_kehadiran').value = btn.getAttribute('data-kehadiran');
                    document.getElementById('edit_pesan').name = "edit_pesan";
                    document.getElementById('edit_pesan').value = btn.getAttribute('data-pesan');
                    document.getElementById('box_keh').style.display = "block";
                } else {
                    document.getElementById('m_title').innerText = "Tambah Buku Tamu Manual";
                    document.getElementById('btn_sub_universal').name = "tambah_ucapan_manual";
                    document.getElementById('edit_nama').name = "add_nama"; document.getElementById('edit_nama').value = "";
                    document.getElementById('edit_kehadiran').name = "add_kehadiran";
                    document.getElementById('edit_pesan').name = "add_pesan"; document.getElementById('edit_pesan').value = "";
                    document.getElementById('box_keh').style.display = "block";
                }
            });
        }

        var mReply = document.getElementById('modalReplyAdmin');
        if(mReply) {
            mReply.addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                document.getElementById('reply_pid').value = btn.getAttribute('data-id');
                document.getElementById('reply_name_target').innerText = btn.getAttribute('data-nama');
            });
        }
    </script>
</body>
</html>