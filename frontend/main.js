// ...existing code...
document.addEventListener('DOMContentLoaded', function() {
    // Helper untuk URL API (pindahkan ke atas agar global)
    function getApiUrl(action) {
        // Selalu gunakan path absolut ke backend server
        var base = 'http://localhost:8000/api.php';
        return base + '?action=' + action;
    }
    // Modal loading dan pesan
    var modalLoading = document.getElementById('modal-loading');
    var loadingText = document.getElementById('loading-text');
    var modalMsg = document.getElementById('modal-msg');
    var msgText = document.getElementById('msg-text');
    var closeMsg = document.getElementById('close-msg');
    function showLoading(text) {
        loadingText.textContent = text || 'Memproses...';
        modalLoading.style.display = 'flex';
    }
    function hideLoading() {
        modalLoading.style.display = 'none';
    }
    function showMsg(text) {
        msgText.textContent = text;
        modalMsg.style.display = 'flex';
    }
    closeMsg.onclick = function() { modalMsg.style.display = 'none'; };
    // Navbar tab switching
    var tabs = ['siswa', 'nilai', 'pola', 'laporan'];
    document.querySelectorAll('.nav-btn').forEach(function(btn) {
        btn.onclick = function() {
            tabs.forEach(function(tab) {
                document.getElementById('tab-' + tab).style.display = 'none';
            });
            document.getElementById('tab-' + btn.dataset.tab).style.display = 'block';
            if (btn.dataset.tab === 'nilai') {
                loadNilai();
            }
            // Load hasil clustering & VIKOR saat tab pola dibuka
            if (btn.dataset.tab === 'pola') {
                loadClustering();
                loadVikorHasil();
            }
        };
    });

    // Tampilkan tab siswa saat awal
    tabs.forEach(function(tab) {
        document.getElementById('tab-' + tab).style.display = 'none';
    });
    document.getElementById('tab-siswa').style.display = 'block';
    // Event tombol hapus semua siswa di tab siswa
    var btnDeleteAll = document.getElementById('btn-delete-all');
    if (btnDeleteAll) {
        btnDeleteAll.onclick = function() {
            if (confirm('Yakin ingin menghapus seluruh data siswa?')) {
                hapusSemuaSiswa();
            }
        };
    }
    // Tampilkan data nilai siswa di tab nilai
    function loadNilai() {
        fetch(getApiUrl('siswa'))
        .then(async r => {
            try { return await r.json(); } catch(e) { showMsg('Gagal mengambil data siswa! Pastikan server dan path API benar.'); return {}; }
        })
        .then(d => {
            var html = '<table style="width:100%;border-collapse:collapse;">';
            html += '<thead><tr><th>NISN</th><th>Nama</th><th>Asal Sekolah</th><th>Sem 1</th><th>Sem 2</th><th>Sem 3</th><th>Sem 4</th><th>Sem 5</th><th>Aksi</th></tr></thead><tbody>';
            if (d.success && d.data) {
                d.data.forEach(row => {
                    html += `<tr>
                        <td>${row.nisn || '-'}</td>
                        <td>${row.nama}</td>
                        <td>${row.asal_sekolah}</td>
                        <td>${row.nilai_sem1}</td>
                        <td>${row.nilai_sem2}</td>
                        <td>${row.nilai_sem3}</td>
                        <td>${row.nilai_sem4}</td>
                        <td>${row.nilai_sem5}</td>
                        <td>
                            <button class='btn-edit' data-id='${row.id}' style='color:#fff;background:#f0ad4e;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;margin-right:4px;'>Edit</button>
                            <button class='btn-hapus' data-id='${row.id}' style='color:#fff;background:#d9534f;border:none;padding:4px 10px;border-radius:4px;cursor:pointer;'>Hapus</button>
                        </td>
                    </tr>`;
                });
            }
            html += '</tbody></table>';
            document.getElementById('nilai-table').innerHTML = html;
            // Event tombol edit
            setTimeout(function() {
                document.querySelectorAll('.btn-edit').forEach(function(btn) {
                    btn.onclick = function() {
                        const row = d.data.find(r => r.id == btn.dataset.id);
                        if (!row) return;
                        document.getElementById('edit-id').value = row.id;
                        document.getElementById('edit-nisn').value = row.nisn || '';
                        document.getElementById('edit-nama').value = row.nama || '';
                        document.getElementById('edit-asal').value = row.asal_sekolah || '';
                        document.getElementById('edit-sem1').value = row.nilai_sem1 || '';
                        document.getElementById('edit-sem2').value = row.nilai_sem2 || '';
                        document.getElementById('edit-sem3').value = row.nilai_sem3 || '';
                        document.getElementById('edit-sem4').value = row.nilai_sem4 || '';
                        document.getElementById('edit-sem5').value = row.nilai_sem5 || '';
                        document.getElementById('modal-edit').style.display = 'flex';
                    };
                });
                document.querySelectorAll('.btn-hapus').forEach(function(btn) {
                    btn.onclick = function() {
                        if (confirm('Yakin ingin menghapus siswa ini?')) {
                            hapusSiswa(btn.dataset.id);
                        }
                    };
                });
            }, 100);
        });
    // Fungsi hapus siswa
    function hapusSiswa(id) {
        showLoading('Menghapus data siswa...');
        // Selalu kirim id di query string (PHP lebih kompatibel)
        fetch(getApiUrl('siswa') + `&id=${id}`, { method: 'DELETE' })
        .then(async r => {
            let d;
            try { d = await r.json(); } catch(e) { d = {}; }
            return d;
        })
        .then(d => {
            hideLoading();
            if (d.success) {
                showMsg('Siswa berhasil dihapus!');
                loadNilai();
                loadClustering();
                loadVikorHasil();
                loadHasil();
            } else {
                showMsg('Gagal menghapus siswa!');
            }
        });
    }
// Fungsi hapus semua siswa (harus global agar bisa dipanggil dari tombol di luar loadNilai)
function hapusSemuaSiswa() {
    showLoading('Menghapus seluruh data siswa...');
    // Coba hapus dengan method DELETE ke endpoint siswa&all=1, fallback ke GET jika gagal
    fetch(getApiUrl('siswa') + '&all=1', { method: 'DELETE' })
    .then(async r => {
        let d;
        try { d = await r.json(); } catch(e) { d = {}; }
        if (!d.success) {
            // Fallback: hapus dengan GET
            return fetch(getApiUrl('siswa') + '&all=1', { method: 'GET' })
                .then(async r2 => { try { return await r2.json(); } catch(e2) { return {}; } });
        }
        return d;
    })
    .then(d => {
        hideLoading();
        if (d.success) {
            showMsg('Seluruh data siswa berhasil dihapus!');
            loadNilai();
            loadClustering();
            loadVikorHasil();
            loadHasil();
        } else {
            showMsg('Gagal menghapus semua siswa!');
        }
    });
}
    }
    // Tampilkan data pola penentuan (info proses KMeans/VIKOR)
    function showPolaInfo(msg) {
        document.getElementById('pola-info').innerHTML = `<div style='margin-top:10px;color:#007bff;'>${msg}</div>`;
    }
    // Modal tambah siswa
    var modal = document.getElementById('modal-tambah');
    var btnShow = document.getElementById('btn-show-modal');
    var btnClose = document.getElementById('close-modal');
    if (btnShow && modal) {
        btnShow.onclick = function() { modal.style.display = 'block'; };
    }
    if (btnClose && modal) {
        btnClose.onclick = function() { modal.style.display = 'none'; };
    }
    window.onclick = function(event) {
        if (modal && event.target == modal) modal.style.display = 'none';
    };
    // Upload CSV
    document.getElementById('form-upload').onsubmit = function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        fetch('http://localhost:8000/api.php?action=import', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(d => alert(d.success ? 'Import berhasil!' : 'Import gagal!'));
        loadNilai();
    };

    // Tambah data siswa manual
    document.getElementById('form-tambah').onsubmit = function(e) {
        e.preventDefault();
        var fd = new FormData(this);
        var data = {};
        fd.forEach((v, k) => data[k] = v);
        // Hapus properti akreditasi jika ada
        delete data.akreditasi;
        // Validasi NISN
        if (!data.nisn || !/^\d{10}$/.test(data.nisn)) {
            alert('NISN wajib diisi dan harus 10 digit angka!');
            return;
        }
        fetch(getApiUrl('siswa'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(d => {
            alert(d.success ? 'Siswa berhasil ditambah!' : (d.msg || 'Gagal tambah siswa!'));
            this.reset();
            modal.style.display = 'none';
            loadNilai();
        });
        // ...event tombol edit dipindahkan ke dalam loadNilai agar selalu aktif setelah render tabel...
    };

    // Proses K-Means
    document.getElementById('btn-kmeans').onclick = function() {
        showPolaInfo('Memproses K-Means...');
        fetch(getApiUrl('kmeans'))
        .then(r => r.json())
        .then(d => {
            setTimeout(function() {
                if (d.success && d.konvergen) {
                    showPolaInfo('Clustering selesai! Iterasi: ' + d.iterasi + ' (Konvergen)');
                    loadClustering();
                } else {
                    showPolaInfo('Gagal clustering! Iterasi: ' + (d.iterasi || '-') + ' (Tidak konvergen)');
                }
            }, 500);
        });
    };

    // Proses VIKOR
    document.getElementById('btn-vikor').onclick = function() {
        showPolaInfo('Memproses VIKOR...');
        fetch(getApiUrl('vikor') + '&top=10')
        .then(async r => {
            try {
                return await r.json();
            } catch (e) {
                showMsg('Gagal ranking VIKOR! Pastikan K-Means sudah diproses dan data siswa cukup.');
                return { success: false };
            }
        })
        .then(d => {
            setTimeout(function() {
                showPolaInfo(d.success ? 'Ranking selesai!' : (d.msg || 'Gagal ranking!'));
                if (d.success) {
                    loadHasil();
                    loadVikorHasil();
                }
            }, 500);
        });
        // Load data nilai saat tab nilai dibuka
        document.querySelector('[data-tab="nilai"]').onclick = loadNilai;
    };

    // Cetak PDF
    document.getElementById('btn-pdf').onclick = function() {
        window.open(window.location.origin + '/backend/pdf.php', '_blank');
    };

    // Load hasil seleksi dari hasil VIKOR dan tampilkan status kelulusan
var jumlahLolos = 10;
function loadHasil() {
    // Ambil data siswa dan hasil VIKOR sekaligus
    Promise.all([
        fetch(getApiUrl('siswa')).then(async r => { try { return await r.json(); } catch(e) { return {}; } }),
        fetch(getApiUrl('vikorhasil')).then(async r => { try { return await r.json(); } catch(e) { return {}; } })
    ]).then(([siswa, vikor]) => {
        var tbody = document.querySelector('#tabel-hasil tbody');
        tbody.innerHTML = '';
        var thead = document.querySelector('#tabel-hasil thead');
        if (thead) {
            thead.innerHTML = '<tr><th>Nama</th><th>NISN</th><th>Asal Sekolah</th><th>Nilai Q</th><th>Ranking</th><th>Keterangan</th></tr>';
        }
        if (siswa.success && siswa.data && vikor.success && vikor.data) {
            // Gabungkan data siswa dengan hasil VIKOR berdasarkan id
            // Buat map siswa by id
            var siswaMap = {};
            siswa.data.forEach(row => { siswaMap[row.id] = row; });
            // Gabungkan dan urutkan berdasarkan ranking VIKOR
            var hasilGabung = vikor.data.map((row, idx) => {
                var siswaRow = siswaMap[row.id] || {};
                var status = (row.ranking && row.ranking <= jumlahLolos) ? 'Lulus' : 'Tidak Lulus';
                return {
                    nama: siswaRow.nama || '-',
                    nisn: siswaRow.nisn || '-',
                    asal: siswaRow.asal_sekolah || '-',
                    q: typeof row.q !== 'undefined' ? row.q : '-',
                    ranking: row.ranking || '-',
                    status: status
                };
            });
            // Tambahkan siswa yang tidak ada di hasil VIKOR (jika ada)
            siswa.data.forEach(row => {
                if (!vikor.data.find(v => v.id == row.id)) {
                    hasilGabung.push({
                        nama: row.nama || '-',
                        nisn: row.nisn || '-',
                        asal: row.asal_sekolah || '-',
                        q: '-',
                        ranking: '-',
                        status: 'Tidak Lulus'
                    });
                }
            });
            // Urutkan berdasarkan ranking (angka, lalu '-')
            hasilGabung.sort((a, b) => {
                if (a.ranking === '-' && b.ranking === '-') return a.nama.localeCompare(b.nama);
                if (a.ranking === '-') return 1;
                if (b.ranking === '-') return -1;
                return a.ranking - b.ranking;
            });
            hasilGabung.forEach(row => {
                var tr = document.createElement('tr');
                tr.innerHTML = `<td>${row.nama}</td><td>${row.nisn}</td><td>${row.asal}</td><td>${row.q}</td><td>${row.ranking}</td><td>${row.status}</td>`;
                tbody.appendChild(tr);
            });
        }
    });
}
    // Form jumlah lolos pada pola penentuan
    var formJumlahLolos = document.getElementById('form-jumlah-lolos');
    var inputJumlahLolos = document.getElementById('input-jumlah-lolos');
    if (formJumlahLolos && inputJumlahLolos) {
        formJumlahLolos.onsubmit = function(e) {
            e.preventDefault();
            var val = parseInt(inputJumlahLolos.value);
            if (!isNaN(val) && val > 0) {
                jumlahLolos = val;
                loadHasil();
            }
        };
    }
    loadHasil();
    // Load hasil clustering
    function loadClustering() {
        fetch(getApiUrl('clustering'))
        .then(async r => {
            try { return await r.json(); } catch(e) { showMsg('Gagal mengambil data clustering! Pastikan server dan path API benar.'); return {}; }
        })
        .then(d => {
            var tbody = document.querySelector('#tabel-clustering tbody');
            tbody.innerHTML = '';
            if (d.success && d.data) {
                d.data.forEach(row => {
                    var tr = document.createElement('tr');
                    tr.innerHTML = `<td>${row.nama}</td><td>${row.cluster}</td>`;
                    tbody.appendChild(tr);
                });
            }
        });
    }
    // Load hasil VIKOR
    function loadVikorHasil() {
        fetch(getApiUrl('vikorhasil'))
        .then(async r => {
            try { return await r.json(); } catch(e) { showMsg('Gagal mengambil data VIKOR! Pastikan server dan path API benar.'); return {}; }
        })
        .then(d => {
            var tbody = document.querySelector('#tabel-vikor tbody');
            tbody.innerHTML = '';
            if (d.success && d.data) {
                d.data.forEach(row => {
                    var tr = document.createElement('tr');
                    tr.innerHTML = `<td>${row.nama}</td><td>${row.q}</td><td>${row.ranking}</td>`;
                    tbody.appendChild(tr);
                });
            }
        });
    }
});