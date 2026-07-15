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

        // Ambil elemen input secara langsung
        const inputNim = document.getElementById('input-nim');
        const inputNama = document.getElementById('input-nama');
        const submitBtn = document.getElementById('btn-submit-cari');

        // Sembunyikan state lama
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
        resultBox.classList.add('d-none');
        resultBody.textContent = ''; // Aman mengosongkan tabel dengan cara ini

        const nimValue = inputNim.value.trim();
        const namaValue = inputNama.value.trim();

        if (nimValue === '' || namaValue === '') {
            tampilkanError('NIM dan Nama Lengkap tidak boleh kosong.');
            return;
        }

        // Tampilkan state loading pada tombol
        submitBtn.disabled = true;
        const txtAsli = submitBtn.textContent;
        submitBtn.textContent = 'Mencari...';

        // Lakukan pemanggilan AJAX Fetch API
        fetch(BASE_URL + 'api/advisors/search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                nim: nimValue,
                student_name: namaValue
            })
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Terjadi kesalahan sistem.');
            }
            return data;
        })
        .then(res => {
            if (res.status === 'success' && Array.isArray(res.data)) {
                renderTabelHasil(res.data);
            } else {
                tampilkanError('Format data yang diterima tidak sesuai.');
            }
        })
        .catch(error => {
            tampilkanError(error.message);
        })
        .finally(() => {
            // Kembalikan state tombol
            submitBtn.disabled = false;
            submitBtn.textContent = txtAsli;
        });
    });

    function tampilkanError(pesan) {
        alertBox.textContent = pesan; // Menggunakan textContent, kebal XSS
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
            
            // Map badge secara manual di client, dilarang ambil class bootstrap langsung dari string server
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
            cellNamaDosen.textContent = item.advisor_name || '-'; // textContent menjamin karakter tag HTML dirender sebagai teks biasa
            cellNamaDosen.classList.add('fw-bold');
            row.appendChild(cellNamaDosen);

            resultBody.appendChild(row);
        });

        resultBox.classList.remove('d-none');
    }
});