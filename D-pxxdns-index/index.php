<?php
/**
 * D-pxxdns-index 为PXXDNS定制的企业级首页模板
 */


// 读取 .env 文件
$env_path = __DIR__ . '/../../.env';
if (!file_exists($env_path)) {
    // 尝试在上一级目录查找 (兼容本地开发环境)
    $env_path = __DIR__ . '/../.env';
    if (!file_exists($env_path)) {
        $env_path = __DIR__ . '/../../../.env';
        if (!file_exists($env_path)) {
            die("配置文件不存在 (Config File Not Found)");
        }
    }
}

$env = parse_ini_file($env_path, true);
if (!$env || !isset($env['DATABASE'])) {
    die("配置文件格式错误 (Invalid Config Format)");
}

$db_config = [
    'host' => $env['DATABASE']['HOSTNAME'],
    'port' => $env['DATABASE']['HOSTPORT'],
    'username' => $env['DATABASE']['USERNAME'],
    'password' => $env['DATABASE']['PASSWORD'],
    'dbname' => $env['DATABASE']['DATABASE'],
    'prefix' => $env['DATABASE']['PREFIX']
];

// 数据库连接与初始化
try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("系统维护中 (Database Error)");
}

$prefix = $env['DATABASE']['PREFIX'];

// 1. 获取站点配置
$site_config = [];
try {
    $stmt = $pdo->query("SELECT * FROM `{$prefix}config`");
    while ($row = $stmt->fetch()) {
        $site_config[$row['k']] = $row['v'];
    }
} catch (PDOException $e) {
    // Fallback defaults
    $site_config = [
        'sitename' => 'D-pxxdns-index',
        'description' => '为PXXDNS定制的企业级首页模板',
        'keywords' => '域名解析,二级域名,企业服务',
        'logo' => '',
        'icp' => '',
        'qq' => '',
        'qun' => ''
    ];
}

// 2. 获取域名列表 (随机6个)
$domains = [];
try {
    // 随机获取6个启用状态的域名
    $stmt = $pdo->query("SELECT * FROM `{$prefix}url` WHERE `status` = 1 ORDER BY RAND() LIMIT 6");
    $domains = $stmt->fetchAll();
} catch (PDOException $e) {
    // ignore
}

// 3. 友情链接
$links = [];
if (!empty($site_config['link'])) {
    $raw_links = explode("\n", $site_config['link']);
    foreach ($raw_links as $link_str) {
        $parts = explode('|', trim($link_str));
        if (count($parts) >= 2) {
            $links[] = ['name' => $parts[0], 'url' => $parts[1]];
        }
    }
}

// 默认显示数量
$display_limit = 6;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_config['sitename']); ?> - <?php echo htmlspecialchars($site_config['description']); ?></title>
    <meta name="keywords" content="<?php echo htmlspecialchars($site_config['keywords']); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($site_config['description']); ?>">
    
    <!-- 资源预加载 -->
    <link rel="dns-prefetch" href="//cdn.staticfile.net">
    <link rel="preconnect" href="https://cdn.staticfile.net" crossorigin>
    
    <link rel="shortcut icon" href="./favicon.ico">

    <!-- Bootstrap 5 CSS (七牛云CDN) -->
    <link href="https://cdn.staticfile.net/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (七牛云CDN) -->
    <link href="https://cdn.staticfile.net/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- AOS Animation CSS (七牛云CDN) -->
    <link href="https://cdn.staticfile.net/aos/2.3.1/aos.css" rel="stylesheet">
    
    <style>
        :root {
            /* 旗舰版 Ultra 配色 */
            --primary-color: #2563eb; /* 皇家蓝 */
            --primary-dark: #1e40af;
            --primary-light: #eff6ff;
            --secondary-color: #0f172a; /* 墨蓝 */
            --accent-color: #f59e0b;    /* 琥珀色点缀 */
            --text-main: #334155;
            --text-muted: #64748b;
            --bg-body: #f8fafc;
            --nav-height: 76px;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text-main);
            background-color: var(--bg-body);
            /* 添加微弱的背景纹理 */
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 32px 32px;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* === Navbar === */
        .navbar {
            height: var(--nav-height);
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.4rem;
            color: #000 !important;
            display: flex;
            align-items: center;
            letter-spacing: -0.5px;
        }
        .nav-link {
            font-weight: 600;
            color: var(--text-main) !important;
            margin: 0 8px;
            font-size: 0.95rem;
            padding: 8px 12px !important;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
            background-color: var(--primary-light);
        }
        
        .btn-nav {
            background-color: var(--primary-color);
            color: #fff !important;
            padding: 8px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
            transition: all 0.3s;
            border: none;
        }
        .btn-nav:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3);
        }
        .btn-login {
            color: var(--text-main);
            font-weight: 600;
            text-decoration: none;
            margin-right: 15px;
            transition: color 0.3s;
        }
        .btn-login:hover {
            color: var(--primary-color);
        }

/* === Mobile Specific Overrides === */
        @media (max-width: 991px) {
            .navbar { display: none !important; }
            .mobile-top-header {
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                padding: 15px 20px;
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(15px);
                position: fixed;
                top: 0; left: 0; width: 100%;
                z-index: 1000;
                box-shadow: 0 4px 20px rgba(0,0,0,0.03);
                border-bottom: 1px solid rgba(0,0,0,0.05);
            }
            .hero-section {
                padding-top: 120px;
                background-position: center top;
            }
            .hero-title { font-size: 2.2rem !important; }
            .hero-btn-group {
                display: flex;
                flex-direction: column;
                gap: 15px;
                padding: 0 10px;
            }
            .hero-btn-group .btn {
                width: 100%;
                margin: 0 !important;
                padding: 12px 0;
            }
            .section-header { margin-bottom: 40px; }
            .domain-card {
                margin-bottom: 15px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }
        }
        
        .mobile-top-header {
            display: none;
        }

        /* === Hero Section === */
        .hero-section {
            padding: 180px 0 140px;
            /* 使用更小的背景图或纯色渐变兜底 */
            background: radial-gradient(circle at top right, rgba(239, 246, 255, 0.8) 0%, rgba(255, 255, 255, 0.95) 60%), url('https://fogpic-vip.3pw.pw/20260223/0783ddf2e00c4ac5f7a6e8932b4e323f.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            overflow: hidden;
            position: relative;
            background-color: #f8fafc; /* 图片加载失败时的兜底色 */
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.15;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            letter-spacing: -1.5px;
        }
        .hero-highlight {
            background: linear-gradient(120deg, #dbeafe 0%, #dbeafe 100%);
            background-repeat: no-repeat;
            background-size: 100% 35%;
            background-position: 0 85%;
            color: var(--primary-color);
        }
        .hero-desc {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            max-width: 600px;
            font-weight: 400;
        }

        /* === Partners (Scrolling) === */
        .partners-section {
            padding: 50px 0;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            overflow: hidden;
        }
        .partners-header {
            text-align: center;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.8;
        }
        .partners-track {
            display: flex;
            width: fit-content;
            animation: scroll 40s linear infinite;
        }
        .partner-item {
            display: flex;
            align-items: center;
            padding: 0 50px;
            font-weight: 700;
            font-size: 1.3rem;
            color: #cbd5e1;
            white-space: nowrap;
            transition: color 0.3s;
        }
        .partner-item:hover {
            color: var(--primary-color);
        }
        .partner-item i {
            margin-right: 12px;
            font-size: 1.6rem;
        }
        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* === Features Grid === */
        .features-section {
            padding: 100px 0;
            background: #fff;
        }
        .section-header {
            text-align: center;
            margin-bottom: 70px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .section-badge {
            display: inline-block;
            padding: 8px 18px;
            background: #eff6ff;
            color: var(--primary-color);
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--secondary-color);
            margin-bottom: 15px;
            letter-spacing: -1px;
        }
        .section-desc {
            color: var(--text-muted);
            font-size: 1.15rem;
            line-height: 1.7;
        }

        .feature-card {
            padding: 40px;
            border-radius: 20px;
            background: #fff;
            border: 1px solid #f1f5f9;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            height: 100%;
        }
        .feature-card:hover {
            box-shadow: 0 20px 40px -5px rgba(0, 0, 0, 0.1);
            transform: translateY(-8px);
            border-color: transparent;
        }
        .feature-icon {
            width: 70px;
            height: 70px;
            background: var(--primary-light);
            color: var(--primary-color);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 30px;
            transition: all 0.3s;
        }
        .feature-card:hover .feature-icon {
            background: var(--primary-color);
            color: #fff;
            transform: rotate(-5deg);
        }
        .feature-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--secondary-color);
        }
        .feature-text {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.6;
        }

        /* === Solutions Section (New) === */
        .solutions-section {
            padding: 100px 0;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://fogpic-vip.3pw.pw/20260223/e26247200eed0e19f74e8ec42ed0581f.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #fff;
            position: relative;
            background-color: #0f172a; /* 兜底色 */
        }
        .solution-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: -1px;
        }
        .solution-desc {
            color: rgba(255,255,255,0.8);
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* Custom Tabs */
        .solution-tabs-wrapper {
            margin-top: 50px;
        }
        .nav-pills-custom {
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 0;
        }
        .nav-pills-custom .nav-link {
            background: transparent;
            color: rgba(255,255,255,0.6);
            border-radius: 0;
            border-bottom: 3px solid transparent;
            font-size: 1.1rem;
            padding: 15px 30px;
            margin: 0 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .nav-pills-custom .nav-link:hover {
            color: #fff;
        }
        .nav-pills-custom .nav-link.active {
            background: transparent;
            color: #fff;
            border-bottom: 3px solid #fff;
        }
        
        /* Glass Cards */
        .glass-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.1);
            height: 100%;
            transition: transform 0.3s, background 0.3s;
        }
        .glass-card:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-5px);
            border-color: rgba(255,255,255,0.2);
        }
        .glass-card h4 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #fff;
        }
        .glass-card p {
            color: rgba(255,255,255,0.7);
            font-size: 0.95rem;
            margin: 0;
            line-height: 1.6;
        }
        .tab-content-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #fff;
        }
        .tab-content-desc {
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
            margin-bottom: 40px;
        }

        /* === Domain List === */
        .domains-section {
            padding: 80px 0;
            background: var(--bg-body);
        }
        .domain-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            align-items: center;
            padding: 15px;
            position: relative;
        }
        .domain-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            border-color: var(--primary-color);
        }
        .domain-icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            background: #f8fafc;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-right: 15px;
            border: 1px solid #f1f5f9;
        }
        .domain-info {
            flex-grow: 1;
            min-width: 0;
        }
        .domain-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin: 0 0 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .domain-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .domain-action {
            flex-shrink: 0;
            text-align: right;
            margin-left: 15px;
        }
        .price-tag {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--secondary-color);
            display: block;
            margin-bottom: 4px;
        }
        .price-tag span {
            font-size: 0.75rem;
            font-weight: 400;
            color: var(--text-muted);
        }
        .btn-buy-mini {
            display: inline-block;
            padding: 4px 12px;
            background: var(--primary-light);
            color: var(--primary-color);
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .domain-card:hover .btn-buy-mini {
            background: var(--primary-color);
            color: #fff;
        }

        /* === Footer === */
        .footer {
            background: #0f172a url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%231e293b' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            color: #94a3b8;
            padding: 100px 0 0;
            font-size: 0.95rem;
            position: relative;
        }
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            opacity: 0.8;
        }
        .footer-brand {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            display: block;
            text-decoration: none;
        }
        .footer-desc {
            line-height: 1.8;
            margin-bottom: 30px;
            max-width: 300px;
        }
        .footer-heading {
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .footer-links li {
            margin-bottom: 15px;
        }
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.2s;
        }
        .footer-links a:hover {
            color: #fff;
            padding-left: 5px;
        }

        /* Friends Links */
        .friend-links-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .friend-link-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
            overflow: hidden;
        }
        .friend-link-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: translateY(-2px);
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .friend-link-item i {
            margin-right: 8px;
            font-size: 0.75rem;
            opacity: 0.5;
            transition: opacity 0.3s;
        }
        .friend-link-item:hover i {
            opacity: 1;
            color: var(--primary-color);
        }

        .footer-bottom {
            background: #020617;
            padding: 30px 0;
            margin-top: 80px;
            border-top: 1px solid rgba(255,255,255,0.05);
            text-align: center;
            font-size: 0.9rem;
        }
        .social-links a {
            display: inline-flex;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            color: #fff;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-3px);
        }

        /* Back to Top */
        #backToTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 2147483647; /* 浏览器允许的最大 z-index */
            cursor: pointer;
            text-decoration: none;
        }
        /* #backToTop.show { opacity: 1; visibility: visible; } */
        #backToTop:hover { background: var(--primary-dark); transform: translateY(-5px); }

        /* Mobile specific fixes */
        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
            .hero-section { padding: 120px 0 60px; text-align: center; }
            .hero-desc { margin: 0 auto 30px; }
            .section-title { font-size: 2rem; }
            .domain-card { margin-bottom: 20px; }
            .footer { text-align: center; }
            .footer-brand { justify-content: center; }
            .footer-desc { margin: 0 auto 30px; }
            .social-links { justify-content: center; display: flex; margin-bottom: 30px; }
            .friend-links-grid { grid-template-columns: repeat(2, 1fr); }
        }

        /* [紧急修复] 强制显示所有 AOS 动画元素 */
        /* 防止因 JS 加载失败或初始化延迟导致内容不可见 */
        [data-aos] {
            opacity: 1 !important;
            transform: none !important;
            visibility: visible !important;
            pointer-events: auto !important;
            transition: none !important;
        }
    </style>
</head>
<body>

    <!-- Mobile Top Header (Visible only on mobile) -->
    <div class="mobile-top-header justify-content-center">
        <a class="navbar-brand" href="#">
            <?php if (!empty($site_config['logo'])): ?>
                <img src="<?php echo htmlspecialchars($site_config['logo']); ?>" alt="Logo" height="32" class="rounded me-2">
            <?php else: ?>
                <i class="fas fa-cube me-2 text-primary"></i>
            <?php endif; ?>
            <?php echo htmlspecialchars($site_config['sitename']); ?>
        </a>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <?php if (!empty($site_config['logo'])): ?>
                    <img src="<?php echo htmlspecialchars($site_config['logo']); ?>" alt="Logo" height="36" class="rounded me-2">
                <?php else: ?>
                    <i class="fas fa-cube me-2 text-primary"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($site_config['sitename']); ?>
            </a>
            
            <!-- Desktop Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item mobile-visible d-lg-none d-none"><a class="nav-link" href="#">首页</a></li> <!-- Hidden on mobile by CSS tweaks above -->
                    <li class="nav-item d-none d-lg-block"><a class="nav-link" href="#">首页</a></li>
                    <li class="nav-item d-none d-lg-block"><a class="nav-link" href="#features">功能</a></li>
                    <li class="nav-item d-none d-lg-block"><a class="nav-link" href="#domains">域名</a></li>
                    <li class="nav-item ms-lg-3 d-none d-lg-block">
                        <span style="color:#cbd5e1;">|</span>
                    </li>
                </ul>
            </div>

            <!-- Mobile & Desktop Auth Buttons (Always Visible) -->
            <div class="mobile-auth-group ms-auto d-flex align-items-center">
                <a href="/user/#/login" class="btn btn-nav" target="_blank">开始使用</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center" data-aos="fade-up">
                    <div class="section-badge mb-3">全球领先的云解析平台</div>
                    <h1 class="hero-title">
                        <?php echo htmlspecialchars($site_config['sitename']); ?>
                    </h1>
                    <p class="hero-desc mx-auto">
                        <?php echo htmlspecialchars($site_config['description']); ?>
                    </p>
                    <div class="d-flex gap-3 justify-content-center hero-btn-group">
                        <a href="/user/#/login" target="_blank" class="btn btn-nav px-5 py-3 fs-6 shadow-lg">立即开始使用</a>
                        <a href="#domains" class="btn btn-outline-dark px-5 py-3 fs-6 rounded-pill fw-bold border-2">浏览域名市场</a>
                    </div>
                    
                    <div class="mt-5 d-flex align-items-center gap-4 justify-content-center text-muted small">
                        <div><i class="fas fa-check-circle text-success me-1"></i> 99.99% 可用性</div>
                        <div><i class="fas fa-check-circle text-success me-1"></i> 免费 SSL 证书</div>
                        <div><i class="fas fa-check-circle text-success me-1"></i> DDoS 防护</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Partners (Scrolling) -->
    <section id="partners" class="partners-section">
        <div class="container">
            <div class="partners-header">以下厂商正在为我们提供权威DNS解析服务</div>
            <div class="overflow-hidden">
                <div class="partners-track">
                    <!-- 第一次循环 -->
                    <div class="partner-item"><i class="fab fa-cloudflare"></i> Cloudflare</div>
                    <div class="partner-item"><i class="fab fa-aws"></i> AWS Route53</div>
                    <div class="partner-item"><i class="fab fa-google"></i> Google Cloud</div>
                    <div class="partner-item"><i class="fas fa-cloud"></i> Aliyun DNS</div>
                    <div class="partner-item"><i class="fas fa-server"></i> DNSPod</div>
                    <!-- 重复一次以实现无缝滚动 -->
                    <div class="partner-item"><i class="fab fa-cloudflare"></i> Cloudflare</div>
                    <div class="partner-item"><i class="fab fa-aws"></i> AWS Route53</div>
                    <div class="partner-item"><i class="fab fa-google"></i> Google Cloud</div>
                    <div class="partner-item"><i class="fas fa-cloud"></i> Aliyun DNS</div>
                    <div class="partner-item"><i class="fas fa-server"></i> DNSPod</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Solutions Section (Replaces Features) -->
    <section id="features" class="solutions-section">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="solution-title">二级域名分发解决方案</h2>
                <p class="solution-desc">连接腾讯云、阿里云、Cloudflare等国际大厂资源，提供安全、稳定、高效的域名分发服务</p>
            </div>
            
            <!-- Tabs -->
            <div class="solution-tabs-wrapper" data-aos="fade-up" data-aos-delay="100">
                <ul class="nav nav-pills nav-pills-custom justify-content-center mb-5" id="solutionTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-1-btn" data-bs-toggle="pill" data-bs-target="#tab-1" type="button">极速解析</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-2-btn" data-bs-toggle="pill" data-bs-target="#tab-2" type="button">管理与支付</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-3-btn" data-bs-toggle="pill" data-bs-target="#tab-3" type="button">售后保障</button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="solutionTabsContent" data-aos="fade-up" data-aos-delay="200">
                <!-- Tab 1: 极速解析 -->
                <div class="tab-pane fade show active" id="tab-1" role="tabpanel">
                    <div class="text-start mb-5">
                        <h3 class="tab-content-title">高性能解析</h3>
                        <p class="tab-content-desc">极速解析生效，确保用户快速访问</p>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="glass-card">
                                <h4>大厂资源接入</h4>
                                <p>整合腾讯云、阿里云、Cloudflare顶级DNS解析能力，全球节点覆盖，确保解析稳定高效。</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="glass-card">
                                <h4>解析极速生效</h4>
                                <p>采用先进的同步技术，新增或修改解析记录平均5秒内全网生效，业务变更即时响应。</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="glass-card">
                                <h4>多线路优化</h4>
                                <p>支持智能线路细分，自动适配电信、联通、移动、教育网等多运营商网络，提升访问体验。</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 2: 管理与支付 -->
                <div class="tab-pane fade" id="tab-2" role="tabpanel">
                    <div class="text-start mb-5">
                        <h3 class="tab-content-title">便捷管理与支付</h3>
                        <p class="tab-content-desc">全自动化流程，让域名管理变得简单</p>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="glass-card">
                                <h4>自助管理面板</h4>
                                <p>功能强大的用户控制台，支持批量操作、实时监控、API集成，让您轻松掌控所有域名资源。</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="glass-card">
                                <h4>多种支付方式</h4>
                                <p>支持支付宝、微信、USDT等多种主流支付渠道，充值即时到账，灵活满足个人与企业需求。</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="glass-card">
                                <h4>透明计费模式</h4>
                                <p>按月/按年灵活计费，无隐形消费，详细的账单报表，让每一分投入都清晰可见。</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: 售后保障 -->
                <div class="tab-pane fade" id="tab-3" role="tabpanel">
                    <div class="text-start mb-5">
                        <h3 class="tab-content-title">无忧售后保障</h3>
                        <p class="tab-content-desc">专业团队为您保驾护航，解决后顾之忧</p>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="glass-card">
                                <h4>7x24小时支持</h4>
                                <p>专业技术团队全天候在线，工单系统快速响应，无论是技术咨询还是故障处理，随时待命。</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="glass-card">
                                <h4>SLA服务承诺</h4>
                                <p>我们承诺99.9%的服务可用性，若因平台原因导致解析中断，我们将按协议进行赔付。</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="glass-card">
                                <h4>数据安全备份</h4>
                                <p>每日自动异地备份解析数据，采用银行级加密存储，确保您的数据资产安全无虞。</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Domain List -->
    <section id="domains" class="domains-section">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <span class="section-badge">热门资源</span>
                <h2 class="section-title">精选优质域名</h2>
                <p class="section-desc">海量后缀任您选择，满足不同场景的业务需求</p>
            </div>
            
            <div class="row g-3" id="domain-list">
                <?php if (empty($domains)): ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">暂无域名上架</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($domains as $index => $domain): ?>
                        <div class="col-md-6 col-lg-4 domain-item" data-aos="fade-up" data-aos-delay="<?php echo ($index % 3) * 100; ?>">
                            <div class="domain-card">
                                <div class="domain-icon">
                                    <?php if (!empty($domain['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($domain['image_url']); ?>" style="width:100%; height:100%; border-radius:8px; object-fit:cover;">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($domain['url'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="domain-info">
                                    <h5 class="domain-name">.<?php echo htmlspecialchars($domain['url']); ?></h5>
                                    <p class="domain-desc" title="<?php echo htmlspecialchars($domain['data']); ?>">
                                        <?php echo htmlspecialchars($domain['data'] ?: '极速解析，稳定可靠'); ?>
                                    </p>
                                </div>
                                <div class="domain-action">
                                    <div class="price-tag">
                                        <?php echo $domain['price'] > 0 ? '¥' . $domain['price'] : '免费'; ?>
                                        <?php if($domain['price'] > 0): ?><span>/月</span><?php endif; ?>
                                    </div>
                                    <a href="/user/#/login" target="_blank" class="btn-buy-mini">
                                        注册
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="text-center mt-5" data-aos="fade-up">
                <a href="/user/#/login" target="_blank" class="btn btn-white border px-4 py-2 rounded-pill shadow-sm bg-white">
                    查看更多 <i class="fas fa-arrow-right ms-2 small"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row gy-5">
                <div class="col-lg-4">
                    <a href="#" class="footer-brand d-flex align-items-center">
                        <?php if (!empty($site_config['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($site_config['logo']); ?>" alt="Logo" height="30" class="rounded me-2">
                        <?php else: ?>
                            <i class="fas fa-cube me-2 text-primary"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($site_config['sitename']); ?>
                    </a>
                    <p class="footer-desc">
                        <?php echo htmlspecialchars($site_config['description']); ?>
                    </p>
                    <div class="social-links">
                        <?php if (!empty($site_config['qq'])): ?>
                            <a href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo $site_config['qq']; ?>&site=qq&menu=yes" target="_blank"><i class="fab fa-qq"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($site_config['qun'])): ?>
                            <a href="<?php echo htmlspecialchars($site_config['qun']); ?>" target="_blank"><i class="fas fa-users"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-8 col-12">
                    <h5 class="footer-heading">友情链接</h5>
                    <div class="friend-links-grid">
                        <?php if (!empty($links)): ?>
                            <?php foreach ($links as $link): ?>
                                <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="friend-link-item">
                                    <i class="fas fa-link"></i>
                                    <span class="text-truncate"><?php echo htmlspecialchars($link['name']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-muted small">暂无友链</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                        &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_config['sitename']); ?>. All rights reserved.
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <?php if (!empty($site_config['icp'])): ?>
                            <a href="https://beian.miit.gov.cn/" target="_blank" class="text-decoration-none text-secondary small"><?php echo htmlspecialchars($site_config['icp']); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row mt-3 border-top border-secondary border-opacity-10 pt-3">
                    <div class="col-12 text-center small text-muted d-flex flex-wrap justify-content-center align-items-center gap-3">
                        <span class="badge bg-white bg-opacity-10 text-light border border-white border-opacity-10 px-3 py-2 fw-normal">
                            Power BY <strong class="text-white">PXXDNS</strong>
                        </span>
                        <span class="badge bg-white bg-opacity-10 text-light border border-white border-opacity-10 px-3 py-2 fw-normal">
                            Made BY <a href="https://www.pldduck.com" target="_blank" class="text-white text-decoration-none fw-bold hover-primary">PLDDUCK</a>
                        </span>
                        <a href="https://github.com/ououduck/D-pxxdns-index" target="_blank" class="text-decoration-none d-inline-flex align-items-center px-3 py-2 rounded bg-white bg-opacity-10 text-light border border-white border-opacity-10 hover-bg-primary transition-all shadow-sm">
                            <i class="fab fa-github me-2"></i> 此模板已在Github开源
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top -->
    <a href="#" id="backToTop"><i class="fas fa-arrow-up"></i></a>

    <!-- Scripts (七牛云CDN) -->
    <script src="https://cdn.staticfile.net/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.staticfile.net/aos/2.3.1/aos.js"></script>
    
    <script>
        // 初始化 AOS 动画库
        AOS.init({ once: true, offset: 60, duration: 800, easing: 'ease-out-cubic' });

        // Back to top
        const backToTop = document.getElementById('backToTop');
        
        window.addEventListener('scroll', () => {
            
            if (window.scrollY > 300) {
                backToTop.style.opacity = '1';
                backToTop.style.visibility = 'visible';
            } else {
                backToTop.style.opacity = '0';
                backToTop.style.visibility = 'hidden';
            }
        });
        
        backToTop.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });

        
    </script>
</body>
</html>