# Sistem Pengesanan dan Pengesahan Unit Integriti – MBSP

Aplikasi web pengurusan aduan integriti untuk Unit Integriti, Jabatan Khidmat Pengurusan, Majlis Bandaraya Seberang Perai. Dibina berdasarkan BRS versi 1.0 (21 Oktober 2026).

**Teknologi:** Laravel 11 (PHP 8.3), MySQL 8, Blade + Tailwind CSS + Alpine.js, Chart.js, DomPDF, Laravel Excel.

---

## 1. Fungsi Utama (pemetaan BRS)

| Modul | BRS |
|---|---|
| Log masuk dua langkah (kata laluan + OTP e-mel), had sesi 30 minit | 009 |
| Daftar aduan pelbagai saluran, aduan tanpa nama, no. rujukan automatik `UI/TTTT/NNNNN` | 006, 015, 018 |
| Carian pegawai melalui Sistem HR Bersepadu (**mock**, boleh ditukar ke API) | 008 |
| Agihan kes oleh Ketua Unit kepada Pegawai Siasatan (beban kes dipaparkan) | 013 |
| Log aktiviti siasatan (temu bual, lawatan, semakan dokumen…) | 003, 005 |
| Bukti: muat naik fail **disulitkan** + cap **SHA-256**, pengesahan bukti | 006, 023 |
| Cadangan AI berasaskan Pustaka Peraturan (RAG), dengan petikan rujukan [R1]… | 019, 020 |
| Cadangan tindakan berversi → kelulusan **manual** Ketua Unit (lulus / kembalikan) | 013, 021 |
| Rekod tindakan diambil → tutup kes (kes selesai selepas tindakan diambil) | 011 |
| SLA 6 bulan: lencana warna, peringatan 90/30/7 hari, eskalasi mingguan bila lewat | 014, 022 |
| Status "Tiada Bukti / Tidak Dapat Dikesan" sebagai pengecualian | 012 |
| Dashboard ikut peranan, KPI % selesai dalam 6 bulan | 010, 026 |
| Laporan + eksport Excel/PDF + analisis AI (statistik agregat sahaja) | 006, 026 |
| Jejak audit menyeluruh, sejarah status, retensi 7 tahun | 017, 023 |
| Pustaka peraturan/pekeliling: muat naik PDF/TXT → pecahan seksyen automatik | 007, 028 |

## 2. Peranan Pengguna

| Peranan | Akses |
|---|---|
| Pentadbir Sistem | Pengguna, data rujukan, pustaka peraturan, jejak audit. **Tiada akses butiran kes.** |
| Pembantu Tadbir | Daftar & kemas kini aduan, lihat maklumat asas aduan. |
| Pegawai Siasatan | Kes yang diagihkan kepadanya sahaja: siasatan, bukti, AI, cadangan tindakan. |
| Ketua Unit Integriti | Semua kes, agihan, **kelulusan**, penutupan kes, laporan, audit. |
| Pengurusan | Dashboard & laporan agregat sahaja. |

## 3. Pemasangan (Server MBSP)

Keperluan: PHP 8.3 (ext: pdo_mysql, mbstring, openssl, gd, zip, intl, fileinfo), Composer 2, MySQL 8.0+, Nginx/Apache. Node.js **tidak** diperlukan di server kerana aset sudah dibina dalam `public/build`.

```bash
# 1. Salin kod & pasang kebergantungan
composer install --no-dev --optimize-autoloader

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate          # SIMPAN APP_KEY! Ia menyulitkan data pengadu & fail bukti.
# Kemas kini dalam .env: APP_URL, DB_*, MAIL_* (SMTP MBSP), APP_ENV=production, APP_DEBUG=false

# 3. Pangkalan data
mysql -u root -p -e "CREATE DATABASE integriti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --force
php artisan db:seed --force       # data rujukan, pengguna awal, pustaka peraturan (tiada data contoh dalam produksi)

# 4. Optimumkan
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

# 5. Cron (peringatan SLA harian jam 8 pagi)
* * * * * cd /var/www/sistem-integriti && php artisan schedule:run >> /dev/null 2>&1
```

Document root web server mesti menghala ke folder `public/`. Gunakan HTTPS. Tetapkan `upload_max_filesize=25M`, `post_max_size=30M`, `memory_limit=512M` dalam php.ini (untuk fail bukti hingga 20 MB).

> ⚠️ **APP_KEY** mesti disandarkan bersama pangkalan data. Tanpanya, maklumat pengadu dan fail bukti yang disulitkan **tidak boleh dibaca semula**.

### Sandaran (backup)
Sandarkan setiap hari: (1) pangkalan data MySQL (`mysqldump`), (2) folder `storage/app/private` (fail bukti & dokumen peraturan), (3) fail `.env`.

## 4. Konfigurasi Penting (`.env`)

| Pemboleh ubah | Keterangan |
|---|---|
| `INTEGRITI_OTP_EMEL_TETAP` | Fasa UAT: **semua** OTP dihantar ke e-mel ini (kini `zulikqram@mbsp.gov.my`). **Kosongkan untuk produksi** supaya OTP dihantar ke e-mel setiap pengguna. |
| `INTEGRITI_OTP_PAPAR_DEV` | `true` memaparkan OTP di skrin (hanya berfungsi jika `APP_ENV=local`). |
| `INTEGRITI_SLA_BULAN` / `INTEGRITI_RETENSI_TAHUN` | Lalai 6 bulan / 7 tahun. |
| `AI_PROVIDER` | `mock` (demo, tiada AI sebenar), `claude` (Claude API – data keluar rangkaian MBSP, perlukan kelulusan ICT), `ollama` (model tempatan di server MBSP). |
| `AI_ANONIMKAN` | `true`: nama pihak terlibat diganti "Pegawai A/B…", no. KP/telefon ditapis sebelum dihantar ke AI. Maklumat pengadu **tidak pernah** dihantar. |
| `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL` | Untuk `AI_PROVIDER=claude` (lalai `claude-opus-5-5`). |
| `OLLAMA_URL`, `OLLAMA_MODEL` | Untuk `AI_PROVIDER=ollama`. |
| `HR_DRIVER` | `mock` (jadual `pegawai_hr`) atau `api` (lengkapkan `app/Services/Hr/ApiHrService.php` mengikut spesifikasi API HR). |

## 5. Akaun Awal (seeder)

Kata laluan semua akaun: **`Integriti@2026`** — tukar selepas log masuk pertama (menu Profil).

| E-mel | Peranan |
|---|---|
| zulikqram@mbsp.gov.my | Pentadbir Sistem |
| ketua.integriti@mbsp.gov.my | Ketua Unit Integriti |
| pembantu.tadbir@mbsp.gov.my | Pembantu Tadbir |
| pegawai.siasatan1@mbsp.gov.my (…2, …3) | Pegawai Siasatan |
| pengarah.jkp@mbsp.gov.my | Pengurusan |

## 6. Pustaka Peraturan (sumber rujukan AI)

Kandungan awal ialah **ringkasan umum bertanda "belum disahkan"**, bukan teks rasmi. Sebelum digunakan secara rasmi, Unit Integriti perlu memuat naik teks rasmi (PDF) dari laman web JPA dan MBSP melalui menu **Pustaka Peraturan → Tambah**, terutamanya:
- Peraturan-Peraturan Pegawai Awam Majlis Perbandaran Seberang Perai (Kelakuan dan Tatatertib) 1995
- Pekeliling JPA berkaitan (hadiah, konflik kepentingan, pengisytiharan harta, tatatertib)
- SOP / Prosedur Unit Integriti MBSP

AI hanya memetik rujukan yang ada dalam pustaka ini dan diarahkan untuk tidak mereka-reka peruntukan.

## 7. Pembangunan & Ujian

```bash
composer install && npm install && npm run build
php artisan migrate:fresh --seed     # termasuk 20 kes contoh (bukan produksi)
php artisan serve
php artisan test                     # memerlukan pangkalan data MySQL `integriti_test`
php artisan integriti:semak-sla      # jalankan semakan SLA secara manual
```

## 8. Struktur Kod

```
app/Enums/            Status aduan, peranan, keputusan siasatan
app/Models/           Model Eloquent (aduan, bukti, ai_cadangan, audit_log, …)
app/Policies/         AduanPolicy – kawalan akses kes
app/Services/         AliranKerja (peralihan status), Audit, OtpService, Notifikasi,
                      StatistikAduan, PemprosesDokumen, Ai/*, Hr/*
app/Console/Commands/ SemakSla – peringatan & eskalasi SLA
database/migrations/  Skema MySQL
database/seeders/     Data rujukan, pengguna, HR mock, pustaka peraturan, kes contoh
resources/views/      Antara muka (Bahasa Melayu)
tests/Feature/        Ujian aliran kerja penuh & kawalan akses
```

## 9. Perkara Belum Diputuskan (dari BRS)

- Integrasi sebenar Sistem HR Bersepadu (API) – kini mock.
- Kelulusan penyedia AI (Claude API vs model tempatan) oleh ICT / Keselamatan Maklumat.
- Senarai rasmi kategori aduan dan jenis tindakan – kini mock, boleh dikemas kini oleh Pentadbir.
- Teks rasmi peraturan/pekeliling untuk pustaka AI.
- Sasaran prestasi/SLA sistem selepas ujian volum; ujian penembusan (VAPT) sebelum go-live.
