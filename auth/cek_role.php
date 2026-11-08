<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function arahkanSesuaiRoleRoot() {
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] == 3) {
            header("Location: admin/dashboard.php");
            exit;
        } elseif ($_SESSION['role'] == 2) {
            header("Location: mitra/dashboard.php");
            exit;
        } elseif ($_SESSION['role'] == 1) {
            header("Location: index.php");
            exit;
        }
    }

    header("Location: login.php");
    exit;
}

function arahkanSesuaiRoleSubfolder() {
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] == 3) {
            header("Location: ../admin/dashboard.php");
            exit;
        } elseif ($_SESSION['role'] == 2) {
            header("Location: ../mitra/dashboard.php");
            exit;
        } elseif ($_SESSION['role'] == 1) {
            header("Location: ../index.php");
            exit;
        }
    }

    header("Location: ../login.php");
    exit;
}

function hanyaPendakiRoot() {
    if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
        header("Location: login.php");
        exit;
    }

    if ($_SESSION['role'] != 1) {
        arahkanSesuaiRoleRoot();
    }
}

function hanyaAdminSubfolder() {
    if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
        header("Location: ../login.php");
        exit;
    }

    if ($_SESSION['role'] != 3) {
        arahkanSesuaiRoleSubfolder();
    }
}

function hanyaMitraSubfolder() {
    if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
        header("Location: ../login.php");
        exit;
    }

    if ($_SESSION['role'] != 2) {
        arahkanSesuaiRoleSubfolder();
    }
}

function blokirAdminMitraRoot() {
    if (isset($_SESSION['id_user']) && isset($_SESSION['role'])) {
        if ($_SESSION['role'] == 3) {
            header("Location: admin/dashboard.php");
            exit;
        } elseif ($_SESSION['role'] == 2) {
            header("Location: mitra/dashboard.php");
            exit;
        }
    }
}
?>