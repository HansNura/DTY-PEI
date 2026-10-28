# Panduan Kontribusi — PMT

Panduan ini ditujukan untuk **Salman** dan **Umar** (serta kontributor berikutnya) agar pekerjaan antar sprint bisa diserahkan dengan aman lewat GitHub.

Baca bagian **Alert Penting** sebelum melakukan commit pertama. Jika ragu, berhenti dan tanya di grup tim — jangan menebak lalu push ke `main`.

---

## 1. Prasyarat

| Perangkat | Versi minimum | Cek dengan |
| --- | --- | --- |
| PHP | 8.2 (disarankan 8.4) | `php -v` |
| Composer | 2.x | `composer --version` |
| Node.js | 20+ (disarankan 22) | `node -v` |
| npm | 10+ | `npm -v` |
| Git | 2.30+ | `git --version` |
| GitHub CLI (opsional) | 2.x | `gh --version` |

Ekstensi PHP wajib: `mbstring`, `openssl`, `pdo_sqlite`, `sqlite3`.

---

## 2. Setup pertama kali

### 2.1 Identitas Git

```bash
git config --global user.name "Nama Lengkap"
git config --global user.email "email@contoh.com"
```

Gunakan email yang sama dengan akun GitHub agar commit terhubung ke profil.

### 2.2 Clone repositori

```bash
git clone <URL-REPO-PMT> pmt
cd pmt
```

Jika memakai SSH:

```bash
git clone git@github.com:<organisasi>/<repo>.git pmt
```

### 2.3 Siapkan aplikasi

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install --include=dev
npm run build
php artisan serve
```

Buka `http://localhost:8000`. Akun demo ada di `README.md`.

> **Penting:** jika `NODE_ENV=production` aktif di shell, npm melewati `devDependencies`. Selalu pakai `npm install --include=dev`. Cek dengan `echo $NODE_ENV`.

### 2.4 Verifikasi awal

```bash
bash .ai/scripts/run-checks.sh
```

Harus lulus sebelum mulai berkontribusi.

---

## 3. Model kolaborasi

```text
main            ← selalu stabil, hanya menerima merge dari Pull Request
 ├── feat/salman/DTY-002-master-data
 ├── feat/umar/DTY-003-qr-engine
 └── fix/salman/login-throttle
```

Aturan:

- **`main` dilindungi.** Tidak boleh push langsung ke `main`.
- **Satu tiket / satu tujuan per branch.** Jangan campur fitur baru dengan refactor besar.
- **Satu PR = satu tiket.** PR kecil lebih cepat direview dan lebih aman di-merge.
- **PIC tiket menulis test.** Reviewer memeriksa perilaku, bukan hanya format.
- **Maksimal 2 hari per branch.** Jika belum selesai, push branch dan buka **Draft PR** agar terlihat.

### Penamaan branch

Format: `<tipe>/<nama>/<tiket>-<ringkas>`

| Contoh | Untuk |
| --- | --- |
| `feat/salman/DTY-002-master-data` | Fitur master data DTY |
| `feat/umar/DTY-003-qr-engine` | Fitur QR code |
| `fix/salman/DTY-007-scan-duplicate` | Perbaikan bug |
| `docs/umar/sprint-3-report` | Dokumentasi saja |

Tipe yang dipakai: `feat`, `fix`, `docs`, `test`, `refactor`, `chore`.

### Pembagian kerja

Sesuaikan dengan kesepakatan sprint. Contoh pembagian yang disarankan:

| Sprint | Fokus | PIC yang disarankan |
| --- | --- | --- |
| 2 | DTY-002 Master Data, DTY-003 QR | Salman |
| 2 | DTY-004 Pelanggan, DTY-005 Armada | Umar |
| 3 | DTY-006 Surat Jalan | Salman |
| 3 | DTY-007 Scanner Muatan | Umar |

Tulis pembagian final di deskripsi PR dan di tracker agar tidak tumpang tindih.

---

## 4. Alur kerja harian

### Langkah 1 — Mulai dari `main` terbaru

```bash
git switch main
git pull --ff-only origin main
```

`--ff-only` mencegah merge commit tak sengaja di `main`.

### Langkah 2 — Buat branch

```bash
git switch -c feat/salman/DTY-002-master-data
```

### Langkah 3 — Kerjakan dan uji

```bash
# setelah menulis kode + test
bash .ai/scripts/run-checks.sh
```

Jangan menunggu akhir sprint untuk menjalankan test. Jalankan minimal sebelum setiap commit besar.

### Langkah 4 — Commit

```bash
git status
git add -p          # pilih perubahan secara sadar, hindari "git add ." buta
git commit -m "feat(DTY-002): tambah CRUD master data DTY"
```

Format commit:

```text
<tipe>(<tiket>): <ringkasan imperatif, huruf kecil, tanpa titik>
```

Contoh:

```text
feat(DTY-002): tambah form input spesifikasi DTY
fix(DTY-007): cegah scan ganda pada rol yang sama
test(DTY-003): tambah test generator QR unik
docs(DTY-013): tulis laporan UAT sprint 6
```

Tipe: `feat`, `fix`, `test`, `docs`, `refactor`, `chore`, `perf`.

### Langkah 5 — Sinkronkan sebelum push

```bash
git fetch origin
git rebase origin/main
```

Jika ada konflik, selesaikan (lihat bagian 6), lalu:

```bash
bash .ai/scripts/run-checks.sh
git push -u origin HEAD
```

### Langkah 6 — Buka Pull Request

Lewat GitHub CLI:

```bash
gh pr create --fill --base main
```

Atau lewat web GitHub. Isi:

- judul: sama dengan ringkasan commit, sertakan ID tiket;
- deskripsi: apa yang berubah, cara menguji, dampak skema;
- reviewer: satu anggota tim lain (Salman ↔ Umar);
- label bila tersedia (`feature`, `bug`, `docs`).

### Langkah 7 — Review dan merge

Reviewer memeriksa:

- [ ] Perilaku sesuai kriteria penerimaan tiket.
- [ ] Ada test untuk jalur utama dan kasus otorisasi.
- [ ] CI hijau.
- [ ] Tidak ada data sensitif, `.env`, atau database yang ikut ter-commit.
- [ ] Migrasi baru aman dan memiliki `down()`.

Setelah disetujui, gunakan **Squash and merge** dan hapus branch. Jangan merge sendiri tanpa review kecuali keadaan darurat dan sudah diberitahukan.

### Langkah 8 — Bersihkan branch lokal

```bash
git switch main
git pull --ff-only origin main
git branch -d feat/salman/DTY-002-master-data
```

---

## 5. Cara pull, push, dan sinkronisasi yang benar

| Kebutuhan | Perintah | Catatan |
| --- | --- | --- |
| Ambil update tanpa mengubah working tree | `git fetch origin` | Aman, hanya mengunduh |
| Update `main` lokal | `git pull --ff-only origin main` | Gagal jika ada commit lokal; itu disengaja |
| Update branch fitur | `git rebase origin/main` | Riwayat tetap linear |
| Upload branch | `git push -u origin HEAD` | Pertama kali pakai `-u` |
| Cek status | `git status` | Biasakan sebelum commit/push |
| Lihat commit belum terkirim | `git log origin/main..HEAD --oneline` | Pastikan hanya commit sendiri |
| Batalkan staging | `git restore --staged <file>` | Tidak menghapus perubahan |
| Buang perubahan file | `git restore <file>` | **Perubahan hilang permanen** |

### Pull vs rebase

- Di `main`: **selalu** `git pull --ff-only`.
- Di branch fitur: `git fetch` lalu `git rebase origin/main` agar tidak membuat merge commit kotor.
- Jangan `git pull` tanpa opsi di branch fitur jika riwayat ingin bersih.

### Jika push ditolak (`non-fast-forward`)

```bash
git fetch origin
git rebase origin/main
bash .ai/scripts/run-checks.sh
git push
```

Jangan langsung `git push --force`. Jika terpaksa setelah rebase pada **branch sendiri**, gunakan:

```bash
git push --force-with-lease
```

`--force-with-lease` lebih aman karena menolak menimpa commit orang lain. **Dilarang** force push ke `main` atau branch milik orang lain.

---

## 6. Menyelesaikan konflik

```bash
git fetch origin
git rebase origin/main
# Git berhenti di file yang konflik
```

1. Buka file bertanda `<<<<<<<`, `=======`, `>>>>>>>`.
2. Pilih/gabungkan perubahan dengan benar — jangan asal pilih "ours" atau "theirs".
3. Hapus semua penanda konflik.
4. Jalankan test dan build.
5. Lanjutkan:

```bash
git add <file-yang-diselesaikan>
git rebase --continue
```

Jika kacau dan ingin mengulang:

```bash
git rebase --abort
```

Konflik pada migrasi: **jangan menghapus migrasi orang lain**. Buat migrasi baru atau sepakati urutan bersama.

---

## 7. Alert Penting

### DILARANG

- ❌ `git push` langsung ke `main`.
- ❌ `git push --force` / `--force-with-lease` ke `main` atau branch orang lain.
- ❌ Commit `.env`, `database/database.sqlite`, `vendor/`, `node_modules/`, `public/build/`, atau kredensial/API key.
- ❌ `git reset --hard` untuk "membersihkan" perubahan orang lain.
- ❌ Commit kode yang belum dites hanya agar bisa pulang.
- ❌ Mengedit migrasi yang sudah dijalankan di mesin orang lain.
- ❌ Hard-delete data operasional (rol, DO, scan, POD); gunakan status pembatalan.
- ❌ Menonaktifkan middleware, CSRF, atau throttle agar test lulus.
- ❌ Menaruh pengecekan peran di Blade untuk otorisasi.

### WAJIB

- ✅ `git status` dan `git diff` sebelum commit.
- ✅ Jalankan `bash .ai/scripts/run-checks.sh` sebelum push.
- ✅ Satu PR untuk satu tiket, deskripsi jelas, reviewer ditunjuk.
- ✅ Pull/rebase dari `main` sebelum push.
- ✅ Tulis test untuk fitur baru dan kasus akses ditolak.
- ✅ Perbarui dokumentasi bila skema atau alur berubah.
- ✅ Beri tahu tim jika `main` rusak, jangan diam-diam memperbaiki dengan force push.

### Jika tidak sengaja commit file sensitif

```bash
git rm --cached .env
echo ".env" >> .gitignore
git commit -m "chore: keluarkan .env dari repositori"
```

Jika kredensial sudah terlanjur ter-push, **anggap bocor**: ganti kredensial dan beri tahu admin repositori. Menghapus file di commit berikutnya tidak menghapus riwayat.

### Jika salah branch

```bash
git switch -c fix/nama-branch-baru   # bawa perubahan ke branch baru
git switch main
git branch -D feat/branch-yang-salah  # hanya jika belum di-push
```

Jika sudah di-push, jangan hapus paksa; buat PR koreksi atau tanyakan tim.

---

## 8. Checklist Pull Request

Tempel checklist ini di deskripsi PR (template tersedia di `.github/pull_request_template.md`):

- [ ] Branch dibuat dari `main` terbaru.
- [ ] Hanya berisi perubahan untuk tiket ini.
- [ ] `bash .ai/scripts/run-checks.sh` lulus.
- [ ] Test baru mencakup jalur utama dan otorisasi.
- [ ] Migrasi baru punya `down()` dan tidak mengedit migrasi lama.
- [ ] Dokumentasi/ERD diperbarui bila perlu.
- [ ] Tidak ada `.env`, database, atau kredensial.
- [ ] Reviewer sudah ditunjuk.

---

## 9. Serah terima akhir sprint

Sprint dianggap selesai dan siap dilanjutkan jika **semua** poin ini terpenuhi:

1. Semua PR sprint sudah di-merge ke `main`; tidak ada branch menggantung yang belum dijelaskan.
2. CI di `main` hijau (Pint, test, build).
3. `docs/sprint-<N>.md` diperbarui: kriteria penerimaan, bukti uji, keputusan teknis, dan hal yang belum selesai.
4. Status tiket di `Rencana Kerja Tim PMT.xlsx` diperbarui (minimal di salinan tim).
5. Database lokal bisa dibangun ulang dari nol:

   ```bash
   bash .ai/scripts/db-reset.sh
   bash .ai/scripts/run-checks.sh
   ```

6. Tag rilis sprint (opsional tetapi disarankan):

   ```bash
   git switch main
   git pull --ff-only origin main
   git tag -a sprint-2 -m "Sprint 2: master data dan QR"
   git push origin sprint-2
   ```

7. Catatan serah terima ditulis di PR terakhir atau `docs/sprint-<N>.md`:

   - apa yang selesai;
   - apa yang belum dan alasannya;
   - migrasi yang perlu dijalankan;
   - akun/seed baru;
   - risiko atau bug yang diketahui.

8. Anggota tim lain bisa melanjutkan hanya dengan `git pull` + `composer install`/`npm install` bila dependensi berubah.

### Format laporan sprint

Gunakan `docs/sprint-1.md` sebagai contoh. Ringkas, berbasis bukti (nama test), bukan narasi panjang.

---

## 10. Troubleshooting cepat

| Masalah | Solusi |
| --- | --- |
| `Your branch is behind` | `git pull --ff-only origin main` |
| Push ditolak non-fast-forward | `git fetch && git rebase origin/main`, lalu push |
| Lupa pull sebelum kerja | Commit dulu di branch, lalu `git rebase origin/main` |
| Test gagal setelah rebase | Selesaikan konflik, jalankan ulang `run-checks.sh` |
| Asset tidak muncul | `npm install --include=dev && npm run build` |
| `Vite manifest not found` | Jalankan `npm run build` atau `npm run dev` |
| Database kacau | `bash .ai/scripts/db-reset.sh` (lokal saja) |
| Ingin membatalkan commit terakhir (belum push) | `git reset --soft HEAD~1` |
| Ingin membatalkan commit yang sudah push | Buat commit baru yang membalik perubahan; jangan rewrite `main` |
| CI hijau di lokal tetapi merah di GitHub | Cek versi PHP/Node, jalankan `composer install` dan `npm ci --include=dev` |

---

## 11. Ringkasan perintah

```bash
# mulai kerja
git switch main && git pull --ff-only origin main
git switch -c feat/<nama>/<tiket>-<ringkas>

# selama kerja
git status
git add -p
git commit -m "feat(<tiket>): <ringkasan>"

# sebelum push
git fetch origin && git rebase origin/main
bash .ai/scripts/run-checks.sh
git push -u origin HEAD

# buka PR
gh pr create --fill --base main

# setelah PR di-merge
git switch main && git pull --ff-only origin main
git branch -d feat/<nama>/<tiket>-<ringkas>
```

---

## 12. Komunikasi

- Tanyakan di grup jika ragu; jangan menebak pada hal yang menyangkut data atau otorisasi.
- Laporkan segera jika `main` merah atau ada data yang tampak hilang.
- Saat meninggalkan pekerjaan setengah jadi, buka **Draft PR** dan tulis statusnya.
- Review PR teman dalam waktu yang wajar; PR kecil lebih cepat selesai.
