<nav class="navbar" id="navbar">
    <div class="nav-container">

        <a href="index.php" class="nav-brand">
            <div class="nav-logo">
                <img src="assets/img/logo_ndakiyuk.png" alt="Logo NdakiYuk">
            </div>
            <span class="nav-brand-text">NdakiYuk</span>
        </a>

        <ul class="nav-links">
            <li><a href="index.php" class="nav-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                    Beranda
                </a></li>
            <li><a href="gunung.php" class="nav-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="3 11 22 2 13 21 11 13 3 11" />
                    </svg>
                    Gunung
                </a></li>
            <li><a href="tiket.php" class="nav-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 11 12 14 22 4" />
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                    </svg>
                    Tiketku
                </a></li>
            <li><a href="profil.php" class="nav-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    Profile
                </a></li>
            <?php if (isset($_SESSION['username'])): ?>
                <li class="mobile-only-nav">
                    <a href="auth/logout.php" class="nav-link logout-mobile-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                        Logout
                    </a>
                </li>
            <?php else: ?>
                <li class="mobile-only-nav">
                    <a href="register.php" class="nav-link signup-mobile-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="8.5" cy="7" r="4" />
                            <line x1="20" y1="8" x2="20" y2="14" />
                            <line x1="23" y1="11" x2="17" y2="11" />
                        </svg>
                        Sign-up
                    </a>
                </li>
                <li class="mobile-only-nav">
                    <a href="login.php" class="nav-link login-mobile-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                            <polyline points="10 17 15 12 10 7" />
                            <line x1="15" y1="12" x2="3" y2="12" />
                        </svg>
                        Login
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="nav-actions">
            <a href="#" class="nav-search" id="searchTriggerBtn" aria-label="Cari">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
            </a>
            <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Buka Menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <?php if (isset($_SESSION['username'])): ?>
                <div class="nav-profile-dropdown">
                    <button class="nav-user-toggle" id="dropdownUserBtn">
                        <img src="<?= !empty($_SESSION['foto_profile']) ? htmlspecialchars($_SESSION['foto_profile']) : 'assets/img/profile/default.jpg'; ?>"
                            alt="User Avatar"
                            class="nav-user-avatar">

                        <span class="nav-user-name"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User'); ?></span>
                        <svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>

                    <div class="dropdown-menu-box" id="dropdownMenuBox">
                        <div class="dropdown-arrow"></div>
                        <a href="profil.php" class="dropdown-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            Profile
                        </a>
                        <a href="auth/logout.php" class="dropdown-item logout-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="register.php" class="btn-outline">Sign-up</a>
                <a href="login.php" class="btn-primary">Login</a>
            <?php endif; ?>
        </div>

    </div>
</nav>

<div class="search-popup-overlay" id="searchPopup">
    <div class="search-popup-box">
        <div class="search-popup-header">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7a9487" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input type="text" id="searchInputField" placeholder="Ketik nama gunung tujuanmu (cth: Arjuno)..." autocomplete="off">
            </div>
            <button class="search-popup-close" id="searchCloseBtn">&times;</button>
        </div>
        <div class="search-results-container" id="searchResults">
            <div class="search-empty-state">Mulai ketikkan destinasi puncak yang ingin kamu tuju.</div>
        </div>
    </div>
</div>

<style>
    .search-popup-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(13, 59, 34, 0.45);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 9999;
        padding: 40px 20px;
        justify-content: center;
        align-items: flex-start;
    }

    .search-popup-overlay.active {
        display: flex;
    }

    .search-popup-box {
        background: #ffffff;
        width: 100%;
        max-width: 600px;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        border: 1px solid #e0e8e3;
        overflow: hidden;
        animation: searchSlideDown 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .search-popup-header {
        display: flex;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #e0e8e3;
        gap: 16px;
    }

    .search-input-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        background: #f4f6f4;
        padding: 10px 16px;
        border-radius: 10px;
        border: 1px solid transparent;
        transition: 0.15s;
    }

    .search-input-wrapper:focus-within {
        border-color: #1a5c38;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(26, 92, 56, 0.06);
    }

    #searchInputField {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        font-family: inherit;
        font-size: 14px;
        color: #0f2419;
    }

    .search-popup-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #7a9487;
        cursor: pointer;
        transition: 0.15s;
        line-height: 1;
    }

    .search-popup-close:hover {
        color: #dc3545;
    }

    .search-results-container {
        max-height: 350px;
        overflow-y: auto;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .search-empty-state {
        text-align: center;
        padding: 32px 16px;
        color: #7a9487;
        font-size: 13.5px;
        font-style: italic;
    }

    /* Style Item Hasil Pencarian */
    .search-item-link {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 10px;
        border-radius: 10px;
        text-decoration: none;
        color: inherit;
        transition: 0.15s;
    }

    .search-item-link:hover {
        background: #eef7f2;
    }

    .search-item-thumb {
        width: 50px;
        height: 38px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid #e0e8e3;
    }

    .search-item-name {
        font-size: 14px;
        font-weight: 700;
        color: #0f2419;
        display: block;
    }

    .search-item-meta {
        font-size: 11.5px;
        color: #7a9487;
        margin-top: 2px;
    }

    @keyframes searchSlideDown {
        from {
            opacity: 0;
            transform: translateY(-15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ===== MOBILE MENU NAVBAR =====
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.querySelector('.nav-links');

        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                navLinks.classList.toggle('mobile-open');
            });

            document.addEventListener('click', function(e) {
                if (!navLinks.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    navLinks.classList.remove('mobile-open');
                }
            });
        }
        // ===== LOGIKA DROPDOWN PROFILE EXISTING =====
        const toggleBtn = document.getElementById('dropdownUserBtn');
        const menuBox = document.getElementById('dropdownMenuBox');

        if (toggleBtn && menuBox) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                menuBox.classList.toggle('show');
                toggleBtn.classList.toggle('active');
            });
            document.addEventListener('click', function() {
                menuBox.classList.remove('show');
                toggleBtn.classList.remove('active');
            });
        }

        // ===== LOGIKA BARU: INTERAKTIF POP-UP LIVE SEARCH ENGINE =====
        const searchPopup = document.getElementById('searchPopup');
        const searchTrigger = document.getElementById('searchTriggerBtn');
        const searchClose = document.getElementById('searchCloseBtn');
        const searchInput = document.getElementById('searchInputField');
        const searchResults = document.getElementById('searchResults');

        // Buka Pop-up & Otomatis Fokus ke Input Text
        if (searchTrigger) {
            searchTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                searchPopup.classList.add('active');
                setTimeout(() => searchInput.focus(), 50);
            });
        }

        // Tutup Pop-up
        if (searchClose) {
            searchClose.addEventListener('click', function() {
                closeSearch();
            });
        }

        // Tutup jika admin/user mengklik area blur luar box
        searchPopup.addEventListener('click', function(e) {
            if (e.target === searchPopup) closeSearch();
        });

        function closeSearch() {
            searchPopup.classList.remove('active');
            searchInput.value = '';
            searchResults.innerHTML = '<div class="search-empty-state">Mulai ketikkan destinasi puncak yang ingin kamu tuju.</div>';
        }

        // Pemancar Event AJAX (Fetch) saat user mengetik nama gunung
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const keyword = this.value.trim();

                if (keyword.length < 1) {
                    searchResults.innerHTML = '<div class="search-empty-state">Mulai ketikkan destinasi puncak yang ingin kamu tuju.</div>';
                    return;
                }

                // Jalankan stream request data ke berkas pencarian background
                fetch('auth/search_gunung_ajax.php?keyword=' + encodeURIComponent(keyword))
                    .then(response => response.text())
                    .then(htmlOutput => {
                        searchResults.innerHTML = htmlOutput;
                    })
                    .catch(err => {
                        searchResults.innerHTML = '<div class="search-empty-state" style="color:#dc3545;">Gagal memuat pencarian data.</div>';
                    });
            });
        }
    });
</script>