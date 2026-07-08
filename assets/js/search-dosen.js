// File: /assets/js/search-dosen.js

document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('form-search-dosen');
    const resultContainer = document.getElementById('result-container');
    const resultContent = document.getElementById('result-content');

    // Fungsi utilitas untuk mencegah XSS saat me-render data ke HTML (Wajib penuhi Checklist Keamanan)
    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, function(tag) {
            const charsToReplace = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            };
            return charsToReplace[tag] || tag;
        });
    }

    searchForm.addEventListener('submit', function(e) {
        // MENCEGAH HALAMAN RELOAD! Ini nyawa dari spesifikasi PRD.
        e.preventDefault(); 

        const nimInput = document.getElementById('inputNIM').value.trim();
        const namaInput = document.getElementById('inputNama').value.trim();

        // Tampilkan status "Loading" untuk UX yang baik
        resultContainer.classList.remove('d-none');
        resultContent.innerHTML = '<div class="text-center text-muted spinner-border spinner-border-sm" role="status"></div> <span class="ms-2">Mencari data ke server...</span>';

        // --- SIMULASI FETCH API (MOCK DATA) ---
        // Karena backend belum jadi, kita gunakan setTimeout untuk meniru proses jaringan
        setTimeout(() => {
            
            /* * NANTI DI TAHAP 2, BLOK KODE INI AKAN DIGANTI DENGAN:
             * fetch('/api/advisors/search', { method: 'POST', body: ... })
             * .then(res => res.json())
             */

            // Simulasi: Menguji aturan PRD (Harus Cocok NIM + Nama Persis)
            if (nimInput === "2026001" && namaInput.toLowerCase() === "dimas pratama") {
                // Berhasil
                resultContent.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <tbody>
                                <tr><th style="width: 35%">NIM</th><td>${escapeHTML(nimInput)}</td></tr>
                                <tr><th>Nama Mahasiswa</th><td>${escapeHTML(namaInput)}</td></tr>
                                <tr><th>Dosen Wali</th><td>Dr. Hendra, S.T., M.T.</td></tr>
                                <tr><th>Dosen Pembimbing Magang</th><td>Siti Aminah, M.Kom.</td></tr>
                                <tr><th>Dosen Pembimbing TA</th><td>-</td></tr>
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                // Gagal: Syarat NIM + Nama tidak terpenuhi
                resultContent.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        Data tidak ditemukan. Pastikan <strong>NIM</strong> dan <strong>Nama Lengkap</strong> sesuai dengan data BAAK.
                    </div>
                `;
            }
        }, 1200); // Simulasi delay internet 1,2 detik
    });
});