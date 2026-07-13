<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAAK - Politeknik Nest</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        .navbar-baak {
            background-color: #003366; /* Warna biru gelap ala institusi pendidikan */
        }
        .hero-section {
            background-color: #f8f9fa;
            padding: 3rem 0;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-baak shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
            <i class="bi bi-mortarboard-fill me-2 fs-3"></i>
            <div>
                <span class="d-block lh-1">BAAK</span>
                <small class="fw-normal" style="font-size: 0.75rem;">Politeknik Nest</small>
            </div>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="target="#publicNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="publicNavbar">
            <ul class="navbar-nav ms-auto fw-medium">
                <li class="nav-item">
                    <a class="nav-link text-white" href="/">Beranda</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" id="layananDropdown" role="button" data-bs-toggle="dropdown">
                        Layanan Akademik
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/page/sop-pendaftaran">SOP Pendaftaran</a></li>
                        <li><a class="dropdown-item" href="/page/aturan-cuti">Aturan Cuti Akademik</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-warning" href="/admin/news"><i class="bi bi-box-arrow-in-right"></i> Login Admin</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="main-wrapper min-vh-100">