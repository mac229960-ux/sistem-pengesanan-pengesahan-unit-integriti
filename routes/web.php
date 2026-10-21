<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AduanController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\Auth\LogMasukController;
use App\Http\Controllers\BuktiController;
use App\Http\Controllers\CadanganTindakanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KelulusanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PegawaiHrController;
use App\Http\Controllers\PihakTerlibatController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RujukanController;
use App\Http\Controllers\SiasatanAktivitiController;
use App\Http\Controllers\TindakanController;
use Illuminate\Support\Facades\Route;

// ---------- Pengesahan (log masuk + OTP e-mel) ----------
Route::middleware('guest')->group(function () {
    Route::get('/log-masuk', [LogMasukController::class, 'borang'])->name('login');
    Route::post('/log-masuk', [LogMasukController::class, 'logMasuk'])->middleware('throttle:10,1')->name('login.post');
    Route::get('/otp', [LogMasukController::class, 'borangOtp'])->name('otp');
    Route::post('/otp', [LogMasukController::class, 'sahkanOtp'])->middleware('throttle:10,1')->name('otp.sahkan');
    Route::post('/otp/hantar-semula', [LogMasukController::class, 'hantarSemula'])->middleware('throttle:3,1')->name('otp.semula');
});

Route::middleware('auth')->group(function () {
    Route::post('/log-keluar', [LogMasukController::class, 'logKeluar'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil');
    Route::put('/profil/kata-laluan', [ProfilController::class, 'kataLaluan'])->name('profil.kata-laluan');

    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/{id}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');

    // ---------- Aduan ----------
    Route::get('/aduan', [AduanController::class, 'index'])->name('aduan.index');
    Route::get('/aduan/baru', [AduanController::class, 'create'])->name('aduan.create');
    Route::post('/aduan', [AduanController::class, 'store'])->name('aduan.store');
    Route::get('/aduan/{aduan}', [AduanController::class, 'show'])->name('aduan.show');
    Route::get('/aduan/{aduan}/kemaskini', [AduanController::class, 'edit'])->name('aduan.edit');
    Route::put('/aduan/{aduan}', [AduanController::class, 'update'])->name('aduan.update');
    Route::get('/aduan/{aduan}/cetak', [AduanController::class, 'cetak'])->name('aduan.cetak');
    Route::post('/aduan/{aduan}/agih', [AduanController::class, 'agih'])->name('aduan.agih');
    Route::post('/aduan/{aduan}/tidak-diteruskan', [AduanController::class, 'tidakDiteruskan'])->name('aduan.tidak-diteruskan');
    Route::post('/aduan/{aduan}/mula-siasatan', [AduanController::class, 'mulaSiasatan'])->name('aduan.mula-siasatan');

    // Pihak terlibat (carian Sistem HR)
    Route::get('/hr/cari', [PegawaiHrController::class, 'cari'])->name('hr.cari');
    Route::post('/aduan/{aduan}/pihak', [PihakTerlibatController::class, 'store'])->name('pihak.store');
    Route::delete('/aduan/{aduan}/pihak/{pihak}', [PihakTerlibatController::class, 'destroy'])->name('pihak.destroy');

    // Siasatan
    Route::post('/aduan/{aduan}/aktiviti', [SiasatanAktivitiController::class, 'store'])->name('aktiviti.store');
    Route::delete('/aduan/{aduan}/aktiviti/{aktiviti}', [SiasatanAktivitiController::class, 'destroy'])->name('aktiviti.destroy');

    // Bukti
    Route::post('/aduan/{aduan}/bukti', [BuktiController::class, 'store'])->name('bukti.store');
    Route::get('/aduan/{aduan}/bukti/{bukti}/muat-turun', [BuktiController::class, 'muatTurun'])->name('bukti.muat-turun');
    Route::post('/aduan/{aduan}/bukti/{bukti}/sahkan', [BuktiController::class, 'sahkan'])->name('bukti.sahkan');
    Route::delete('/aduan/{aduan}/bukti/{bukti}', [BuktiController::class, 'destroy'])->name('bukti.destroy');

    // AI
    Route::post('/aduan/{aduan}/ai', [AiController::class, 'jana'])->middleware('throttle:10,1')->name('ai.jana');
    Route::post('/ai/{ai}/semak', [AiController::class, 'semak'])->name('ai.semak');

    // Cadangan tindakan & kelulusan
    Route::post('/aduan/{aduan}/cadangan', [CadanganTindakanController::class, 'simpan'])->name('cadangan.simpan');
    Route::post('/aduan/{aduan}/cadangan/hantar', [CadanganTindakanController::class, 'hantar'])->name('cadangan.hantar');
    Route::get('/kelulusan', [KelulusanController::class, 'index'])->name('kelulusan.index');
    Route::post('/aduan/{aduan}/kelulusan', [KelulusanController::class, 'store'])->name('kelulusan.store');

    // Tindakan diambil & penutupan kes
    Route::post('/aduan/{aduan}/tindakan', [TindakanController::class, 'store'])->name('tindakan.store');
    Route::post('/aduan/{aduan}/tutup', [TindakanController::class, 'tutup'])->name('aduan.tutup');

    // ---------- Pustaka peraturan / pekeliling ----------
    Route::middleware('peranan:ketua_unit,pegawai_siasatan,pentadbir')->group(function () {
        Route::get('/rujukan', [RujukanController::class, 'index'])->name('rujukan.index');
        Route::get('/rujukan/{rujukan}', [RujukanController::class, 'show'])->whereNumber('rujukan')->name('rujukan.show');
    });
    Route::middleware('peranan:ketua_unit,pentadbir')->group(function () {
        Route::get('/rujukan/baru', [RujukanController::class, 'create'])->name('rujukan.create');
        Route::post('/rujukan', [RujukanController::class, 'store'])->name('rujukan.store');
        Route::get('/rujukan/{rujukan}/kemaskini', [RujukanController::class, 'edit'])->name('rujukan.edit');
        Route::put('/rujukan/{rujukan}', [RujukanController::class, 'update'])->name('rujukan.update');
        Route::delete('/rujukan/{rujukan}', [RujukanController::class, 'destroy'])->name('rujukan.destroy');
        Route::post('/rujukan/{rujukan}/seksyen', [RujukanController::class, 'simpanSeksyen'])->name('rujukan.seksyen.store');
        Route::put('/rujukan/{rujukan}/seksyen/{seksyen}', [RujukanController::class, 'kemaskiniSeksyen'])->name('rujukan.seksyen.update');
        Route::delete('/rujukan/{rujukan}/seksyen/{seksyen}', [RujukanController::class, 'hapusSeksyen'])->name('rujukan.seksyen.destroy');
    });

    // ---------- Laporan ----------
    Route::middleware('peranan:ketua_unit,pengurusan,pegawai_siasatan')->group(function () {
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/excel', [LaporanController::class, 'excel'])->name('laporan.excel');
        Route::get('/laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');
        Route::post('/laporan/analisis-ai', [LaporanController::class, 'analisisAi'])->middleware('throttle:5,1')->name('laporan.analisis-ai');
    });

    // ---------- Pentadbiran ----------
    Route::middleware('peranan:pentadbir')->prefix('pentadbir')->name('admin.')->group(function () {
        Route::resource('pengguna', Admin\PenggunaController::class)->except(['show', 'destroy'])->parameters(['pengguna' => 'pengguna']);
        Route::post('pengguna/{pengguna}/reset-kata-laluan', [Admin\PenggunaController::class, 'resetKataLaluan'])->name('pengguna.reset');
        Route::get('data/{jenis}', [Admin\DataRujukanController::class, 'index'])->name('data.index');
        Route::post('data/{jenis}', [Admin\DataRujukanController::class, 'store'])->name('data.store');
        Route::put('data/{jenis}/{id}', [Admin\DataRujukanController::class, 'update'])->name('data.update');
        Route::get('pegawai-hr', [Admin\PegawaiHrController::class, 'index'])->name('pegawai-hr.index');
    });
    Route::middleware('peranan:pentadbir,ketua_unit')->group(function () {
        Route::get('/pentadbir/audit', [Admin\AuditLogController::class, 'index'])->name('admin.audit.index');
    });
});
