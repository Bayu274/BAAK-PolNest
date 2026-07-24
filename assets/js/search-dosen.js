/**
 * BAAK-PolNest - Search Engine Logic
 * Branch: feature/advisor-search
 */

document.addEventListener('DOMContentLoaded', function () {
    const searchForm = document.getElementById('form-cari-dosen');
    const resultBox = document.getElementById('container-hasil-pencarian');
    const resultBody = document.getElementById('table-body-hasil');
    const alertBox = document.getElementById('alert-pesan-error');

    if (!searchForm) return;

    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const inputNim = document.getElementById('input-nim');
        const inputNama = document.getElementById('input-nama');
        const submitBtn = document.getElementById('btn-submit-cari');

        alertBox.classList.add('d-none');
        alertBox.textContent = '';
        resultBox.classList.add('d-none');
        resultBody.textContent = '';

        const nimValue = inputNim.value.trim();
        const namaValue = inputNama.value.trim();

        if (nimValue === '' || namaValue === '') {
            tampilkanError('NIM dan Nama Lengkap tidak boleh kosong.');
            return;
        }

        // [2R] Simpan innerHTML (bukan textContent) supaya icon ikut tersimpan
        submitBtn.disabled = true;
        const txtAsli = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Mencari...';

        // [2S] AbortController — timeout 30 detik
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);

        fetch(BASE_URL + 'api/advisors/search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                nim: nimValue,
                student_name: namaValue
            }),
            signal: controller.signal
        })
        .then(async response => {
            clearTimeout(timeoutId);
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Terjadi kesalahan sistem.');
            }
            return data;
        })
        .then(res => {
            // [2T] Cek array kosong sebelum render tabel
            if (res.status === 'success' && Array.isArray(res.data)) {
                if (res.data.length === 0) {
                    tampilkanError('Data tidak ditemukan atau kecocokan tidak valid.');
                } else {
                    renderTabelHasil(res.data);
                }
            } else {
                tampilkanError('Format data yang diterima tidak sesuai.');
            }
        })
        .catch(error => {
            // [2S] Handle AbortError dari timeout
            if (error.name === 'AbortError') {
                tampilkanError('Permintaan timeout. Silakan coba lagi.');
            } else {
                tampilkanError(error.message || 'Gagal terhubung ke server.');
            }
        })
        .finally(() => {
            clearTimeout(timeoutId);
            submitBtn.disabled = false;
            // [2R] Restore innerHTML (icon + teks)
            submitBtn.innerHTML = txtAsli;
        });
    });

    function tampilkanError(pesan) {
        alertBox.textContent = pesan;
        alertBox.classList.remove('d-none');
    }

    function renderTabelHasil(items) {
        items.forEach((item, index) => {
            const row = document.createElement('tr');

            const cellNo = document.createElement('td');
            cellNo.textContent = String(index + 1);
            row.appendChild(cellNo);

            const cellTipe = document.createElement('td');
            const badge = document.createElement('span');
            
            badge.className = 'badge';
            if (item.advisor_type === 'Wali') {
                badge.classList.add('bg-primary');
            } else if (item.advisor_type === 'Magang') {
                badge.classList.add('bg-success');
            } else {
                badge.classList.add('bg-warning', 'text-dark');
            }
            badge.textContent = item.advisor_type;
            cellTipe.appendChild(badge);
            row.appendChild(cellTipe);

            const cellNamaDosen = document.createElement('td');
            cellNamaDosen.textContent = item.advisor_name || '-';
            cellNamaDosen.classList.add('fw-bold');
            row.appendChild(cellNamaDosen);

            resultBody.appendChild(row);
        });

        resultBox.classList.remove('d-none');
    }
});
