<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-decoration-none text-muted">Beranda</a></li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Pencarian Dosen</li>
                </ol>
            </nav>

            <div class="mb-4">
                <h1 class="h3 fw-bold text-dark mb-1">Pencarian Dosen Pembimbing</h1>
                <p class="text-muted mb-0">Masukkan data diri Anda untuk menemukan informasi dosen pembimbing.</p>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">

                    <div id="alert-pesan-error" class="alert alert-danger d-none" role="alert"></div>

                    <form id="form-cari-dosen">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nomor Induk Mahasiswa (NIM)</label>
                            <input type="text" class="form-control form-control-lg" id="input-nim" placeholder="Masukkan NIM Anda" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" class="form-control form-control-lg" id="input-nama" placeholder="Masukkan Nama Lengkap Anda" required>
                        </div>
                        <button type="submit" id="btn-submit-cari" class="btn btn-primary btn-lg w-100 py-3 mt-2">
                            <i class="bi bi-search me-2"></i>Cari Data
                        </button>
                    </form>
                </div>
            </div>

            <div id="container-hasil-pencarian" class="mt-4 d-none">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-success text-white py-3 px-4 d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                        <h5 class="mb-0 fw-semibold">Hasil Pencarian</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%">No</th>
                                    <th width="25%">Jenis</th>
                                    <th>Nama Dosen Pembimbing</th>
                                </tr>
                            </thead>
                            <tbody id="table-body-hasil"></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>assets/js/search-dosen.js"></script>