<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<?php
// Vooraf bepalen: XP, streak, notificaties (voor de header-widgets)
$__stats = null; $__unread = 0; $__recentNotifs = [];
if (isset($_SESSION['user_id'])) {
    $__stats = getUserStats((int)$_SESSION['user_id']);
    $__unread = getUnreadNotificationCount((int)$_SESSION['user_id']);
    $__recentNotifs = getRecentNotifications((int)$_SESSION['user_id'], 8);
}
?>
    <style>
        :root {
            --theme-blue: #002ef4;
            --theme-blue-hover: #0048ff;
            --theme-blue-soft: rgba(0, 46, 244, 0.18);
            --theme-blue-glow: rgba(0, 46, 244, 0.45);
            --theme-blue-rgb: 0, 46, 244;
            --bg-dark: #050508;
            --bg-card: rgba(14, 14, 20, 0.88);
            --bg-card-solid: #0e0e14;
            --bg-sidebar: #08080c;
            --border-dark: rgba(255,255,255,0.06);
            --border-light: rgba(255,255,255,0.09);
            --text-muted-dark: #8890a6;
            --font: 'Outfit', sans-serif;
        }
        * { font-family: var(--font); box-sizing: border-box; }
        body {
            background: var(--bg-dark);
            background-image:
                radial-gradient(ellipse 140% 100% at 50% -30%, rgba(0, 46, 244, 0.18) 0%, transparent 50%),
                radial-gradient(ellipse 80% 60% at 90% 80%, rgba(0, 46, 244, 0.06) 0%, transparent 45%),
                radial-gradient(ellipse 60% 50% at 10% 50%, rgba(0, 46, 244, 0.05) 0%, transparent 40%),
                linear-gradient(180deg, #050508 0%, #080810 50%, #06060a 100%);
            color: #e8eaef;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        ::selection {
            background: var(--theme-blue-soft);
            color: #fff;
        }
        .bg-primary, .bg-success {
            background: linear-gradient(145deg, var(--theme-blue) 0%, #001a99 50%, #001366 100%) !important;
            border: none !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
        }
        .btn-primary, .btn-success {
            background: linear-gradient(180deg, #0034ff 0%, var(--theme-blue) 50%, #001a99 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 14px;
            padding: 0.65rem 1.35rem;
            transition: transform 0.2s, box-shadow 0.25s, filter 0.2s;
            box-shadow: 0 4px 20px rgba(0, 46, 244, 0.35), inset 0 1px 0 rgba(255,255,255,0.12);
        }
        .btn-primary:hover, .btn-success:hover {
            background: linear-gradient(180deg, var(--theme-blue-hover) 0%, #0034ff 50%, var(--theme-blue) 100%);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 32px var(--theme-blue-glow), inset 0 1px 0 rgba(255,255,255,0.15);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-light);
            color: #e8eaef;
            border-radius: 14px;
            transition: all 0.2s;
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.08);
            border-color: rgba(0, 46, 244, 0.5);
            color: #fff;
        }
        .btn-sm { border-radius: 10px; padding: 0.4rem 0.85rem; }
        .btn-info {
            background: rgba(0, 46, 244, 0.18);
            border: 1px solid rgba(0, 46, 244, 0.5);
            color: #b8c8ff;
            border-radius: 10px;
        }
        .btn-info:hover {
            background: rgba(0, 46, 244, 0.3);
            border-color: var(--theme-blue-hover);
            color: #fff;
        }
        .navbar-dark.bg-success {
            background: linear-gradient(180deg, rgba(8, 8, 14, 0.97) 0%, rgba(5, 5, 10, 0.98) 100%) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-dark);
            box-shadow: 0 1px 0 rgba(0, 46, 244, 0.12), 0 20px 50px rgba(0, 0, 0, 0.3);
        }
        .navbar-brand {
            color: #fff !important;
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: -0.03em;
        }
        .navbar-brand i {
            color: var(--theme-blue);
            filter: drop-shadow(0 0 12px var(--theme-blue-glow));
            opacity: 0.95;
        }
        .nav-link, .navbar-text {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            border-radius: 12px;
            padding: 0.55rem 1rem !important;
            transition: color 0.2s, background 0.2s, transform 0.2s;
        }
        .nav-link:hover {
            color: #fff !important;
            background: var(--theme-blue-soft);
            transform: translateY(-1px);
        }
        .text-primary { color: var(--theme-blue) !important; }
        .sidebar {
            min-height: calc(100vh - 64px);
            background: linear-gradient(180deg, var(--bg-sidebar) 0%, #06060a 100%);
            border-right: 1px solid var(--border-dark);
            padding: 1.25rem 0.85rem;
        }
        .sidebar .nav-link {
            color: var(--text-muted-dark) !important;
            border-radius: 14px;
            margin-bottom: 6px;
            padding: 0.75rem 1.1rem !important;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            transition: all 0.25s ease;
        }
        .sidebar .nav-link:hover {
            background: var(--theme-blue-soft);
            color: #fff !important;
            padding-left: 1.35rem !important;
            transform: translateX(2px);
        }
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(0, 46, 244, 0.35) 0%, rgba(0, 46, 244, 0.12) 100%);
            color: #fff !important;
            border-left: 3px solid var(--theme-blue);
            padding-left: calc(1.1rem - 3px) !important;
            box-shadow: 0 0 24px rgba(0, 46, 244, 0.15);
        }
        .main-content {
            min-height: calc(100vh - 64px);
            padding: 1.75rem 2.25rem 3rem;
        }
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-dark);
            border-radius: 20px;
            color: #e8eaef;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.2s;
        }
        .card:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255,255,255,0.03);
            border-color: var(--border-light);
        }
        .card-header {
            background: linear-gradient(180deg, rgba(0, 46, 244, 0.12) 0%, rgba(0, 46, 244, 0.04) 100%);
            border-bottom: 1px solid var(--border-dark);
            color: #fff;
            font-weight: 600;
            padding: 1.15rem 1.5rem;
            border-radius: 20px 20px 0 0;
        }
        .card-body { padding: 1.35rem 1.6rem; }
        .card-footer {
            background: rgba(0,0,0,0.25);
            border-top: 1px solid var(--border-dark);
            border-radius: 0 0 20px 20px;
            padding: 1rem 1.5rem;
        }
        .card.text-white.bg-success,
        .card.text-white.bg-info,
        .card.text-white.bg-warning {
            border: 1px solid rgba(255,255,255,0.12);
            overflow: hidden;
            position: relative;
        }
        .card.text-white.bg-success::before,
        .card.text-white.bg-info::before,
        .card.text-white.bg-warning::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            pointer-events: none;
        }
        .card.text-white.bg-success { background: linear-gradient(155deg, rgba(0, 60, 255, 0.95) 0%, rgba(0, 35, 180, 0.98) 50%, rgba(0, 20, 120, 0.95) 100%) !important; }
        .card.text-white.bg-info { background: linear-gradient(155deg, rgba(0, 90, 255, 0.9) 0%, rgba(0, 55, 200, 0.95) 50%, rgba(0, 35, 140, 0.9) 100%) !important; }
        .card.text-white.bg-warning { background: linear-gradient(155deg, rgba(220, 160, 20, 0.95) 0%, rgba(180, 120, 0, 0.98) 50%, rgba(140, 90, 0, 0.95) 100%) !important; }
        .card.text-white .card-body .fs-1 {
            filter: drop-shadow(0 0 20px rgba(255,255,255,0.25));
            opacity: 0.95;
        }
        .card.text-white.bg-success .card-body .fs-1 { filter: drop-shadow(0 0 24px var(--theme-blue-glow)); }
        .card.text-white.bg-success:hover,
        .card.text-white.bg-info:hover,
        .card.text-white.bg-warning:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 50px rgba(0, 46, 244, 0.3), 0 0 0 1px rgba(255,255,255,0.06);
        }
        .card.h-100 { transition: transform 0.25s ease, box-shadow 0.25s ease; }
        .card.h-100:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255,255,255,0.04);
        }
        .table {
            color: #e8eaef;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table thead th {
            background: linear-gradient(180deg, rgba(0, 46, 244, 0.18) 0%, rgba(0, 46, 244, 0.08) 100%);
            border-color: var(--border-dark);
            color: #fff;
            font-weight: 600;
            padding: 1rem 1.2rem;
            font-size: 0.9rem;
        }
        .table thead th:first-child { border-radius: 12px 0 0 0; }
        .table thead th:last-child { border-radius: 0 12px 0 0; }
        .table td, .table tbody th {
            border-color: var(--border-dark);
            padding: 0.9rem 1.2rem;
        }
        .table-hover tbody tr {
            transition: background 0.2s;
        }
        .table-hover tbody tr:hover {
            background: rgba(0, 46, 244, 0.08);
        }
        .form-control, .form-select {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border-dark);
            color: #e8eaef;
            border-radius: 14px;
            padding: 0.75rem 1.1rem;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,0.06);
            border-color: var(--theme-blue);
            color: #e8eaef;
            box-shadow: 0 0 0 4px rgba(0, 46, 244, 0.18);
        }
        .form-control::placeholder { color: var(--text-muted-dark); opacity: 0.9; }
        .form-label { color: #e8eaef; font-weight: 500; }
        .border-bottom { border-color: var(--border-dark) !important; }
        .alert-danger {
            background: rgba(220, 53, 69, 0.12);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #f8a8b0;
            border-radius: 14px;
        }
        .alert-success {
            background: rgba(0, 46, 244, 0.12);
            border: 1px solid rgba(0, 46, 244, 0.4);
            color: #b0c0ff;
            border-radius: 14px;
        }
        .alert-warning {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.35);
            color: #ffeaa8;
            border-radius: 14px;
        }
        .alert-info {
            background: rgba(0, 46, 244, 0.1);
            border: 1px solid rgba(0, 46, 244, 0.35);
            color: #b0c0ff;
            border-radius: 14px;
        }
        .badge.bg-success {
            background: linear-gradient(135deg, var(--theme-blue), #001a99) !important;
            font-weight: 600;
            padding: 0.4em 0.7em;
        }
        .text-muted { color: var(--text-muted-dark) !important; }
        a { color: var(--theme-blue); font-weight: 500; transition: color 0.2s; }
        a:hover { color: var(--theme-blue-hover); }
        .card.text-white.bg-success .card-title,
        .card.text-white.bg-info .card-title,
        .card.text-white.bg-warning .card-title { color: #fff !important; }
        .card.border-warning { border-color: rgba(255, 193, 7, 0.5) !important; }
        .card-header.bg-warning {
            background: rgba(255, 193, 7, 0.12) !important;
            color: #e8eaef !important;
            border-bottom-color: var(--border-dark);
        }
        h1, h2, h3, h4, h5, h6 { font-weight: 700; letter-spacing: -0.02em; }
        .page-title-bar { border-bottom: 1px solid var(--border-dark); padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .auth-wrapper {
            min-height: calc(100vh - 64px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
            background:
                radial-gradient(ellipse 100% 80% at 50% -20%, rgba(0, 46, 244, 0.22) 0%, transparent 55%),
                radial-gradient(ellipse 60% 40% at 80% 100%, rgba(0, 46, 244, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse 50% 30% at 20% 80%, rgba(0, 46, 244, 0.06) 0%, transparent 50%);
        }
        .auth-wrapper .card {
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255,255,255,0.05);
        }
        .auth-wrapper .card-header {
            padding: 1.4rem 1.75rem;
            font-size: 1.3rem;
        }
        .auth-wrapper .btn-success { padding: 0.85rem; border-radius: 14px; font-size: 1.05rem; }
        .hero-tagline {
            font-size: 1rem;
            color: var(--text-muted-dark);
            font-weight: 400;
            letter-spacing: 0.02em;
        }
        .welcome-banner {
            background: linear-gradient(135deg, rgba(0, 46, 244, 0.2) 0%, rgba(0, 46, 244, 0.06) 100%);
            border: 1px solid rgba(0, 46, 244, 0.3);
            border-radius: 20px;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.75rem;
            box-shadow: 0 0 40px rgba(0, 46, 244, 0.08);
        }
        .welcome-banner h2 { font-size: 1.5rem; margin-bottom: 0.35rem; }
        .welcome-banner p { color: var(--text-muted-dark); margin: 0; font-size: 1rem; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in { animation: fadeInUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
        .animate-in.delay-1 { animation-delay: 0.08s; opacity: 0; }
        .animate-in.delay-2 { animation-delay: 0.16s; opacity: 0; }
        .animate-in.delay-3 { animation-delay: 0.24s; opacity: 0; }
        .pulse-soft {
            animation: pulseSoft 2.5s ease-in-out infinite;
        }
        @keyframes pulseSoft {
            0%, 100% { box-shadow: 0 4px 20px rgba(0, 46, 244, 0.35), 0 0 0 0 rgba(0, 46, 244, 0.2); }
            50% { box-shadow: 0 8px 32px var(--theme-blue-glow), 0 0 0 10px rgba(0, 46, 244, 0); }
        }
        .cta-highlight { font-weight: 700; }
        .auth-hero {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-hero .display-6 {
            font-weight: 800;
            letter-spacing: -0.04em;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        .auth-hero p {
            color: var(--text-muted-dark);
            font-size: 1.1rem;
            max-width: 280px;
            margin-left: auto;
            margin-right: auto;
        }
        .demo-card {
            background: linear-gradient(145deg, rgba(0, 46, 244, 0.15) 0%, rgba(0, 46, 244, 0.05) 100%);
            border: 1px solid rgba(0, 46, 244, 0.35);
            border-radius: 18px;
            box-shadow: 0 0 30px rgba(0, 46, 244, 0.06);
        }
        .fw-600 { font-weight: 600; }
        .card .table-responsive { border-radius: 14px; overflow: hidden; }

        /* === Premium nav widgets === */
        .nav-search {
            position: relative;
            min-width: 280px;
        }
        .nav-search input {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border-light);
            color: #e8eaef;
            border-radius: 14px;
            padding: 0.55rem 1rem 0.55rem 2.4rem;
            transition: all 0.2s;
            width: 100%;
        }
        .nav-search input::placeholder { color: var(--text-muted-dark); }
        .nav-search input:focus {
            outline: none;
            border-color: var(--theme-blue);
            background: rgba(255,255,255,0.07);
            box-shadow: 0 0 0 4px rgba(0,46,244,0.18);
        }
        .nav-search i {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted-dark);
        }
        .nav-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-light);
            color: #e8eaef;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
            text-decoration: none;
        }
        .nav-pill:hover { background: rgba(255,255,255,0.09); color: #fff; }
        .nav-pill .pill-icon { font-size: 1rem; }
        .nav-pill.xp-pill .pill-icon { color: #ffd54a; filter: drop-shadow(0 0 8px rgba(255,213,74,.55)); }
        .nav-pill.streak-pill .pill-icon { color: #ff7a00; filter: drop-shadow(0 0 8px rgba(255,122,0,.55)); }
        .nav-pill.level-pill {
            background: linear-gradient(135deg, var(--theme-blue), #7c00ff);
            border-color: rgba(255,255,255,.15);
        }
        .nav-notif-btn {
            position: relative;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-light);
            color: #e8eaef;
            width: 40px; height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            cursor: pointer;
        }
        .nav-notif-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .nav-notif-btn .notif-dot {
            position: absolute;
            top: 4px; right: 4px;
            min-width: 18px; height: 18px;
            padding: 0 5px;
            background: #ff3d5e;
            color: #fff;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 0 2px var(--bg-sidebar), 0 0 12px rgba(255,61,94,.6);
        }
        .nav-notif-dropdown {
            position: absolute;
            top: 110%;
            right: 0;
            min-width: 340px;
            max-width: 380px;
            background: var(--bg-card-solid);
            border: 1px solid var(--border-light);
            border-radius: 18px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            z-index: 1050;
            overflow: hidden;
            display: none;
        }
        .nav-notif-dropdown.is-open { display: block; animation: dropdownIn 0.2s ease-out; }
        @keyframes dropdownIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .nav-notif-dropdown .nh {
            padding: 1rem 1.15rem;
            border-bottom: 1px solid var(--border-dark);
            display: flex; justify-content: space-between; align-items: center;
            background: linear-gradient(180deg, rgba(0,46,244,.12), rgba(0,46,244,.02));
        }
        .nav-notif-dropdown .nh h6 { margin: 0; font-weight: 700; }
        .nav-notif-dropdown .nlist { max-height: 380px; overflow-y: auto; }
        .nav-notif-dropdown .ni {
            display: flex; gap: 0.75rem;
            padding: 0.85rem 1.15rem;
            border-bottom: 1px solid var(--border-dark);
            text-decoration: none;
            color: #e8eaef;
            transition: background 0.15s;
        }
        .nav-notif-dropdown .ni:last-child { border-bottom: none; }
        .nav-notif-dropdown .ni:hover { background: rgba(0,46,244,0.08); color: #fff; }
        .nav-notif-dropdown .ni.unread { background: rgba(0,46,244,0.05); }
        .nav-notif-dropdown .ni .ni-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: rgba(0,46,244,.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            color: var(--theme-blue);
            flex-shrink: 0;
        }
        .nav-notif-dropdown .ni .ni-title { font-weight: 600; font-size: 0.9rem; }
        .nav-notif-dropdown .ni .ni-msg { font-size: 0.82rem; color: var(--text-muted-dark); margin-top: 2px; }
        .nav-notif-dropdown .ni .ni-time { font-size: 0.72rem; color: var(--text-muted-dark); margin-top: 4px; }
        .nav-notif-dropdown .nempty { padding: 2rem 1rem; text-align: center; color: var(--text-muted-dark); }
        .nav-notif-dropdown .nfooter {
            padding: 0.65rem 1.15rem;
            border-top: 1px solid var(--border-dark);
            background: rgba(0,0,0,0.2);
            text-align: center;
        }
        .nav-notif-dropdown .nfooter a { font-size: 0.85rem; font-weight: 600; }

        /* === Celebration overlay === */
        .lp-celebrate {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(5,5,10,.7);
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity .35s ease;
            backdrop-filter: blur(6px);
        }
        .lp-celebrate.show { opacity: 1; pointer-events: auto; }
        .lp-celebrate .toast {
            background: linear-gradient(135deg, rgba(0,46,244,.95), rgba(124,0,255,.95));
            color: #fff;
            border-radius: 22px;
            padding: 2.2rem 3rem;
            text-align: center;
            box-shadow: 0 40px 90px rgba(0,46,244,.45), 0 0 0 1px rgba(255,255,255,.1);
            transform: scale(.85);
            transition: transform .4s cubic-bezier(0.22,1.5,0.5,1.05);
        }
        .lp-celebrate.show .toast { transform: scale(1); }
        .lp-celebrate .toast .ico { font-size: 3.5rem; filter: drop-shadow(0 0 24px rgba(255,255,255,.5)); }
        .lp-celebrate .toast h2 { font-size: 2.6rem; margin: .35rem 0 .1rem; font-weight: 800; letter-spacing: -.03em; }
        .lp-celebrate .toast p { margin: 0; opacity: .9; font-size: 1.05rem; }

        /* Auto-fit nav widgets on smaller viewports */
        @media (max-width: 991px) {
            .nav-search { min-width: 0; width: 100%; margin-bottom: .5rem; }
            .nav-pill { font-size: 0.78rem; padding: 0.35rem 0.7rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-mortarboard-fill"></i>
                <span>
                    <?php echo APP_NAME; ?>
                    <span class="hero-tagline d-none d-md-inline-block ms-1" style="font-size: 0.75rem; font-weight: 400; opacity: 0.85;">— Cursussen & abonnementen</span>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isLoggedIn()): ?>
                    <form class="nav-search d-flex mx-lg-3 my-2 my-lg-0" action="search.php" method="GET" role="search">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" placeholder="Zoek cursussen, docenten, gebruikers..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    </form>
                <?php endif; ?>
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isStudent() && $__stats): ?>
                            <li class="nav-item">
                                <a class="nav-pill level-pill" href="profile.php" title="Jouw level">
                                    <i class="bi bi-shield-fill-check pill-icon"></i> Lv <?php echo $__stats['level']; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-pill xp-pill" href="profile.php" title="Jouw XP">
                                    <i class="bi bi-lightning-charge-fill pill-icon"></i> <?php echo $__stats['xp']; ?> XP
                                </a>
                            </li>
                            <?php if ($__stats['streak_days'] > 0): ?>
                                <li class="nav-item">
                                    <a class="nav-pill streak-pill" href="profile.php" title="Login-streak">
                                        <i class="bi bi-fire pill-icon"></i> <?php echo $__stats['streak_days']; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <li class="nav-item" style="position:relative;">
                            <button type="button" class="nav-notif-btn" id="notifToggle" aria-label="Notificaties">
                                <i class="bi bi-bell"></i>
                                <?php if ($__unread > 0): ?>
                                    <span class="notif-dot"><?php echo $__unread > 9 ? '9+' : $__unread; ?></span>
                                <?php endif; ?>
                            </button>
                            <div class="nav-notif-dropdown" id="notifDropdown">
                                <div class="nh">
                                    <h6><i class="bi bi-bell-fill text-primary"></i> Notificaties</h6>
                                    <?php if ($__unread > 0): ?>
                                        <a href="notifications.php?mark_all=1" style="font-size:.8rem;">Alles gelezen</a>
                                    <?php endif; ?>
                                </div>
                                <div class="nlist">
                                    <?php if (empty($__recentNotifs)): ?>
                                        <div class="nempty">
                                            <i class="bi bi-bell-slash" style="font-size:2rem;opacity:.4;"></i>
                                            <p class="mb-0 mt-2">Geen notificaties</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($__recentNotifs as $n): ?>
                                            <a class="ni <?php echo !$n['is_read'] ? 'unread' : ''; ?>" href="<?php echo htmlspecialchars($n['link'] ?: '#'); ?>">
                                                <div class="ni-icon"><i class="bi <?php echo htmlspecialchars($n['icon']); ?>"></i></div>
                                                <div class="flex-grow-1" style="min-width:0;">
                                                    <div class="ni-title"><?php echo htmlspecialchars($n['title']); ?></div>
                                                    <?php if (!empty($n['message'])): ?>
                                                        <div class="ni-msg"><?php echo htmlspecialchars($n['message']); ?></div>
                                                    <?php endif; ?>
                                                    <div class="ni-time"><?php echo date('d-m-Y H:i', strtotime($n['created_at'])); ?></div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="nfooter">
                                    <a href="notifications.php">Alle notificaties bekijken</a>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a href="profile.php" class="d-inline-flex align-items-center gap-2 text-decoration-none" style="color:#fff;">
                                <?php echo avatarHtml($_SESSION['user_name'] ?? $_SESSION['username'], 36); ?>
                                <span class="d-none d-lg-inline" style="font-weight:600;">
                                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username']); ?>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php" title="Uitloggen">
                                <i class="bi bi-box-arrow-right"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Inloggen
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (isLoggedIn()): ?>
    <script>
    (function(){
        const btn = document.getElementById('notifToggle');
        const dd  = document.getElementById('notifDropdown');
        if (!btn || !dd) return;
        btn.addEventListener('click', (e) => { e.stopPropagation(); dd.classList.toggle('is-open'); });
        document.addEventListener('click', (e) => {
            if (!dd.contains(e.target) && !btn.contains(e.target)) dd.classList.remove('is-open');
        });
    })();
    </script>
    <?php endif; ?>

    <?php if (!empty($_SESSION['celebrate'])): $__cel = $_SESSION['celebrate']; unset($_SESSION['celebrate']); ?>
    <div class="lp-celebrate" id="lpCelebrate" role="status">
        <div class="toast">
            <div class="ico"><i class="bi bi-trophy"></i></div>
            <h2><?php echo htmlspecialchars($__cel['title']); ?></h2>
            <p><?php echo htmlspecialchars($__cel['subtitle']); ?></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
    <script>
    (function(){
        const el = document.getElementById('lpCelebrate');
        if (!el) return;
        requestAnimationFrame(() => el.classList.add('show'));
        if (window.confetti) {
            const fire = (opts) => confetti(Object.assign({
                origin: { y: 0.6 },
                colors: ['#002ef4','#7c00ff','#00b7ff','#ffd54a','#ff5cd9']
            }, opts));
            fire({ particleCount: 80, spread: 70, startVelocity: 45 });
            setTimeout(() => fire({ particleCount: 60, spread: 90, startVelocity: 35, angle: 60 }), 200);
            setTimeout(() => fire({ particleCount: 60, spread: 90, startVelocity: 35, angle: 120 }), 350);
        }
        setTimeout(() => el.classList.remove('show'), 2400);
        el.addEventListener('click', () => el.classList.remove('show'));
    })();
    </script>
    <?php endif; ?>

