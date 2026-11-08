<?php
session_start();
include "auth/cek_role.php";
blokirAdminMitraRoot();
// koneksi database
include 'config/koneksi.php';
// checklist.php – Checklist Logistik Pendakian NdakiYuk
// Disesuaikan dengan pembaruan struktur database: logistik, kategori_logistik, kategori_gunung, gunung, gunung_logistik
// ── Ambil daftar gunung untuk dropdown filter ────────────
$gunung_list = [];
$res = $conn->query("SELECT id_gunung, nama_gunung, lokasi FROM gunung ORDER BY nama_gunung");
while ($row = $res->fetch_assoc()) {
    $gunung_list[] = $row;
}

// ── Ambil parameter URL ──────────────────────────────────
$id_gunung_sel = (int)($_GET['id_gunung'] ?? 0);
$mode          = $_GET['mode'] ?? 'umum';   // 'umum' | 'gunung'

if ($id_gunung_sel > 0) {
    $mode = 'gunung';
}

$data = [];
$total_wajib_db = 0;
$total_saran_db = 0;

if ($mode === 'gunung' && $id_gunung_sel > 0) {
    // ── MODE GUNUNG: Tarik logistik spesifik tantangan gunung via tabel gunung_logistik ──
    $sql = "
        SELECT
            kl.id_kategori_logistik,
            kl.nama_kategori_logistik,
            kg.nama_kategori AS nama_kategori_gunung,
            l.id_logistik,
            l.nama_logistik,
            l.deskripsi,
            gl.status_wajib
        FROM gunung_logistik gl
        JOIN logistik l ON gl.id_logistik = l.id_logistik
        JOIN kategori_logistik kl ON l.id_kategori_logistik = kl.id_kategori_logistik
        JOIN kategori_gunung kg ON kl.id_kategori = kg.id_kategori
        WHERE gl.id_gunung = ?
        ORDER BY kl.id_kategori_logistik, l.id_logistik
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_gunung_sel);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // ── MODE UMUM: Tampilkan semua master logistik bawaan sistem NdakiYuk ──
    $mode = 'umum';
    $result = $conn->query("
        SELECT
            kl.id_kategori_logistik,
            kl.nama_kategori_logistik,
            kg.nama_kategori AS nama_kategori_gunung,
            l.id_logistik,
            l.nama_logistik,
            l.deskripsi,
            'Disarankan' AS status_wajib 
        FROM logistik l
        JOIN kategori_logistik kl ON l.id_kategori_logistik = kl.id_kategori_logistik
        JOIN kategori_gunung kg ON kl.id_kategori = kg.id_kategori
        ORDER BY kl.id_kategori_logistik, l.id_logistik
    ");
}

// ── Kelompokkan hasil kueri ke dalam struktur hirarki array ──
$grouped = [];
while ($row = $result->fetch_assoc()) {
    $kid = $row['id_kategori_logistik'];
    if (!isset($grouped[$kid])) {
        $grouped[$kid] = [
            'nama_kat_logistik' => $row['nama_kategori_logistik'],
            'nama_kat_gunung'   => $row['nama_kategori_gunung'],
            'items'             => [],
        ];
    }

    // Hitung total stat bawaan database untuk di-render ke badge indikator filter
    if ($row['status_wajib'] === 'Wajib') {
        $total_wajib_db++;
    } else {
        $total_saran_db++;
    }

    $grouped[$kid]['items'][] = [
        'id'           => $row['id_logistik'],
        'nama'         => $row['nama_logistik'],
        'deskripsi'    => $row['deskripsi'],
        'status_wajib' => $row['status_wajib'],
    ];
}

// Hitung total item kumulatif
$total_items = array_sum(array_map(fn($g) => count($g['items']), $grouped));

// Ambil metadata string nama gunung terpilih
$nama_gunung_sel = '';
if ($id_gunung_sel > 0) {
    foreach ($gunung_list as $g) {
        if ($g['id_gunung'] == $id_gunung_sel) {
            $nama_gunung_sel = $g['nama_gunung'] . ' – ' . $g['lokasi'];
            break;
        }
    }
}

// ── Icon helper dinamis per kategori logistik ────────────────────
function iconKat(string $nama): string
{
    $n = strtolower($nama);
    if (str_contains($n, 'dasar'))    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><polyline points="16 21 12 17 8 21"/><polyline points="8 3 12 7 16 3"/></svg>';
    if (str_contains($n, 'cuaca'))    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>';
    if (str_contains($n, 'camping'))  return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 17 5.5-11L12 12l3.5-7L21 17H3z"/></svg>';
    if (str_contains($n, 'navigasi')) return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>';
    if (str_contains($n, 'dingin'))   return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="2" x2="12" y2="22"/><path d="m17 7-5 5-5-5"/><path d="m17 17-5-5-5 5"/><line x1="2" y1="12" x2="22" y2="12"/><path d="m7 7-5 5 5 5"/><path d="m17 7 5 5-5 5"/></svg>';
    if (str_contains($n, 'survival')) return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
    if (str_contains($n, 'fisik'))    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 11h1a3 3 0 0 1 0 6h-1"/><path d="M6 11h-1a3 3 0 0 0 0 6h1"/><path d="M6 8h12"/><path d="M6 16h12"/><rect x="6" y="8" width="12" height="8" rx="2"/></svg>';
    if (str_contains($n, 'tinggi'))   return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="18 15 12 9 6 15"/></svg>';
    if (str_contains($n, 'extreme') || str_contains($n, 'ekstrem')) return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>';
    if (str_contains($n, 'safety'))   return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>';
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>';
}

function katColor(string $nama): string
{
    $n = strtolower($nama);
    if (str_contains($n, 'mudah'))   return 'kat-mudah';
    if (str_contains($n, 'sedang'))  return 'kat-sedang';
    if (str_contains($n, 'berat'))   return 'kat-berat';
    return 'kat-extream';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" type="image/png" href="assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Checklist Logistik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/checklist.css">
    <link rel="stylesheet" href="assets/css/style.css?v=1000">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="ph-top">
                <div>
                    <h1 class="page-title">Checklist Logistik</h1>
                    <div class="page-title-bar"></div>
                    <p class="page-desc">Pastikan semua perlengkapan sudah siap sebelum memulai pendakian. Centang item yang sudah disiapkan.</p>
                </div>
                <div class="progress-wrap" id="progressWrap">
                    <svg class="progress-ring" width="88" height="88" viewBox="0 0 88 88">
                        <circle class="ring-bg" cx="44" cy="44" r="36" />
                        <circle class="ring-fill" id="ringFill" cx="44" cy="44" r="36"
                            stroke-dasharray="226.2"
                            stroke-dashoffset="226.2" />
                    </svg>
                    <div class="progress-text">
                        <span class="prog-num" id="progNum">0</span>
                        <span class="prog-of">/<span id="progTotal"><?= $total_items ?></span></span>
                    </div>
                </div>
            </div>

            <form method="GET" action="checklist.php" class="filter-form" id="filterForm">
                <div class="ff-group">
                    <label class="ff-label">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="3 11 22 2 13 21 11 13 3 11" />
                        </svg>
                        Filter Gunung Tujuan
                    </label>
                    <div class="ff-select-wrap">
                        <select name="id_gunung" class="ff-select" onchange="this.form.submit()">
                            <option value="0" <?= $id_gunung_sel === 0 ? 'selected' : '' ?>>Semua Perlengkapan Bawaan</option>
                            <?php foreach ($gunung_list as $g): ?>
                                <option value="<?= $g['id_gunung'] ?>" <?= $id_gunung_sel == $g['id_gunung'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g['nama_gunung']) ?> – <?= htmlspecialchars($g['lokasi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <svg class="ff-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </div>
                </div>
                <?php if ($id_gunung_sel > 0): ?>
                    <input type="hidden" name="mode" value="gunung">
                <?php endif; ?>

                <div class="ff-stat-pills">
                    <span class="ff-stat">
                        <span class="ff-stat-dot dot-green"></span>
                        <span class="stat-lbl-txt">Tersedia <strong id="cntWajib"><?= $total_wajib_db ?></strong> Wajib</span>
                    </span>
                    <span class="ff-stat">
                        <span class="ff-stat-dot dot-yellow"></span>
                        <span class="stat-lbl-txt">Tersedia <strong id="cntSaran"><?= $total_saran_db ?></strong> Disarankan</span>
                    </span>
                    <span class="ff-stat">
                        <span class="ff-stat-dot dot-gray"></span>
                        <span class="stat-lbl-txt"><strong><?= $total_items ?></strong> Total Item</span>
                    </span>
                </div>
            </form>

            <?php if ($nama_gunung_sel): ?>
                <div class="gunung-banner">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="3 11 22 2 13 21 11 13 3 11" />
                    </svg>
                    Menampilkan perlengkapan untuk: <strong><?= htmlspecialchars($nama_gunung_sel) ?></strong>
                    <a href="checklist.php" class="clear-filter">× Reset Filter</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <main class="page-main">
        <div class="container">

            <?php if (empty($grouped)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                            <polyline points="9 11 12 14 22 4" />
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                        </svg>
                    </div>
                    <h3 class="empty-title">Tidak Ada Data Logistik</h3>
                    <p class="empty-desc">Belum ada item perlengkapan khusus yang dikonfigurasi untuk gunung ini.</p>
                    <a href="checklist.php" class="btn-empty">Lihat Semua Kategori</a>
                </div>

            <?php else: ?>

                <div class="list-toolbar">
                    <span class="list-count">
                        Menampilkan <strong><?= count($grouped) ?></strong> kelompok kategori &nbsp;·&nbsp;
                        <strong id="runtimeTotalItems"><?= $total_items ?></strong> item logistik
                    </span>
                    <div class="toolbar-actions">
                        <button class="tbtn" onclick="checkAll(true)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            Centang Semua
                        </button>
                        <button class="tbtn" onclick="checkAll(false)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                            Reset
                        </button>
                    </div>
                </div>

                <div class="kat-grid">
                    <?php foreach ($grouped as $kid => $kat): ?>
                        <div class="kat-card <?= katColor($kat['nama_kat_gunung']) ?>" style="animation-delay:<?= (array_search($kid, array_keys($grouped)) * 0.05) ?>s" data-kat="<?= $kid ?>">

                            <div class="kat-header">
                                <div class="kat-icon">
                                    <?= iconKat($kat['nama_kat_logistik']) ?>
                                </div>
                                <div class="kat-title-wrap">
                                    <h3 class="kat-title"><?= htmlspecialchars($kat['nama_kat_logistik']) ?></h3>
                                    <span class="kat-sub">Tingkat Kesulitan: <?= htmlspecialchars($kat['nama_kat_gunung']) ?></span>
                                </div>
                                <div class="kat-progress-mini">
                                    <span class="kpm-num" id="kpm-<?= $kid ?>">0</span>/<span><?= count($kat['items']) ?></span>
                                </div>
                            </div>

                            <div class="kat-bar-wrap">
                                <div class="kat-bar" id="bar-<?= $kid ?>" style="width:0%"></div>
                            </div>

                            <ul class="item-list">
                                <?php foreach ($kat['items'] as $item): ?>
                                    <li class="item-row" id="item-<?= $item['id'] ?>">
                                        <label class="item-label">
                                            <input type="checkbox" class="item-cb"
                                                data-kat="<?= $kid ?>"
                                                data-wajib="<?= htmlspecialchars($item['status_wajib']) ?>"
                                                onchange="onCheck(this)">
                                            <span class="custom-cb">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                    <polyline points="20 6 9 17 4 12" />
                                                </svg>
                                            </span>
                                            <div class="item-info">
                                                <span class="item-nama"><?= htmlspecialchars($item['nama']) ?></span>
                                                <?php if ($item['deskripsi']): ?>
                                                    <span class="item-desc"><?= htmlspecialchars($item['deskripsi']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($item['status_wajib']): ?>
                                                <span class="item-badge <?= $item['status_wajib'] === 'Wajib' ? 'badge-wajib' : 'badge-saran' ?>">
                                                    <?= htmlspecialchars($item['status_wajib']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </div>
    </main>

    <?php include 'components/footer.php'; ?>

    <div class="toast" id="toast"></div>

    <script>
        // ── State runtime JS ───────────────────────────────────────
        const TOTAL_ITEMS = <?= $total_items ?>;
        const CIRCUMFERENCE = 226.2;

        // ── Hitung kalkulasi item per kategori ─────────────────────
        const katCounts = {};
        document.querySelectorAll('.item-cb').forEach(cb => {
            const kid = cb.dataset.kat;
            if (!katCounts[kid]) katCounts[kid] = {
                total: 0,
                checked: 0
            };
            katCounts[kid].total++;
        });

        // ── Menghitung progres dinamis centang user pada UI ───────
        function updateCheckedCounters() {
            let checkedWajib = 0,
                checkedSaran = 0;

            // Hitung berapa item Wajib dan Disarankan yang BERHASIL dicentang pengguna saat ini
            document.querySelectorAll('.item-cb:checked').forEach(cb => {
                if (cb.dataset.wajib === 'Wajib') checkedWajib++;
                else checkedWajib++; // Fallback disarankan
            });
        }

        // ── Update progress ring global header ────────────────────
        function updateGlobalProgress() {
            const checked = document.querySelectorAll('.item-cb:checked').length;
            document.getElementById('progNum').textContent = checked;
            const pct = TOTAL_ITEMS > 0 ? checked / TOTAL_ITEMS : 0;
            const offset = CIRCUMFERENCE * (1 - pct);
            document.getElementById('ringFill').style.strokeDashoffset = offset;

            if (pct >= 1 && TOTAL_ITEMS > 0) {
                showToast('🎒 Semua perlengkapan sudah siap! Selamat melakukan pendakian!', 'success');
            }
        }

        // ── Update mini progress per kategori box ─────────────────
        function updateKatProgress(kid) {
            const cbs = document.querySelectorAll(`.item-cb[data-kat="${kid}"]`);
            const total = cbs.length;
            let checked = 0;
            cbs.forEach(cb => {
                if (cb.checked) checked++;
            });

            document.getElementById(`kpm-${kid}`).textContent = checked;
            const pct = total > 0 ? (checked / total * 100).toFixed(1) : 0;
            document.getElementById(`bar-${kid}`).style.width = pct + '%';

            // Berikan efek tint hijau jika kategori sudah rampung 100%
            const card = document.querySelector(`.kat-card[data-kat="${kid}"]`);
            if (card) {
                card.classList.toggle('kat-done', checked === total && total > 0);
            }
        }

        // ── Handler Event Checkbox Trigger ───────────────────────
        function onCheck(cb) {
            const row = cb.closest('.item-row');
            if (row) row.classList.toggle('item-checked', cb.checked);

            updateKatProgress(cb.dataset.kat);
            updateGlobalProgress();
            updateCheckedCounters();
            saveState();
        }

        // ── Master action: Centang atau hapus massal ──────────────
        function checkAll(val) {
            document.querySelectorAll('.item-cb').forEach(cb => {
                cb.checked = val;
                const row = cb.closest('.item-row');
                if (row) row.classList.toggle('item-checked', val);
            });
            Object.keys(katCounts).forEach(updateKatProgress);
            updateGlobalProgress();
            updateCheckedCounters();
            saveState();
            showToast(val ? '✓ Semua item berhasil dicentang' : '↺ Progress checklist berhasil direset', val ? 'success' : 'info');
        }

        // ── Sinkronisasi Web Storage API (LocalStorage) per Gunung ──
        const STORAGE_KEY = 'ndakiyuk_checklist_id_<?= $id_gunung_sel ?>';

        function saveState() {
            const state = {};
            document.querySelectorAll('.item-cb').forEach(cb => {
                const row = cb.closest('.item-row');
                if (row) state[row.id] = cb.checked;
            });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        }

        function loadState() {
            try {
                const state = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
                document.querySelectorAll('.item-cb').forEach(cb => {
                    const row = cb.closest('.item-row');
                    if (row && state[row.id]) {
                        cb.checked = true;
                        row.classList.add('item-checked');
                    }
                });
            } catch (e) {
                console.error("Gagal memuat localstorage state: ", e);
            }
        }

        function showToast(msg, type = 'info') {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'toast show ' + type;
            clearTimeout(t._tm);
            t._tm = setTimeout(() => t.className = 'toast', 3200);
        }

        // Navbar scroll action
        const navbar = document.getElementById('navbar');
        if (navbar) {
            window.addEventListener('scroll', () => {
                navbar.classList.toggle('scrolled', window.scrollY > 30);
            });
        }

        // Inisialisasi awal dokumen load
        window.addEventListener('DOMContentLoaded', () => {
            loadState();
            Object.keys(katCounts).forEach(kid => updateKatProgress(kid));
            updateGlobalProgress();
            updateCheckedCounters();
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>