<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------- Data rujukan ----------
        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 20)->unique();
            $table->string('nama');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('kategori_aduan', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 20)->unique();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('saluran_aduan', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 20)->unique();
            $table->string('nama');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('jenis_tindakan', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 30)->unique();
            $table->string('nama');
            // tatatertib | pentadbiran | rujukan_luar | penambahbaikan | tiada
            $table->string('kumpulan', 30);
            $table->text('keterangan')->nullable();
            $table->string('rujukan_peraturan')->nullable();
            $table->unsignedSmallInteger('susunan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Cermin data Sistem HR Bersepadu MBSP (mock buat masa ini)
        Schema::create('pegawai_hr', function (Blueprint $table) {
            $table->id();
            $table->string('no_pekerja', 30)->unique();
            $table->string('nama');
            $table->string('no_kp', 20)->nullable();
            $table->string('jawatan')->nullable();
            $table->string('gred', 20)->nullable();
            $table->foreignId('jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $table->string('emel')->nullable();
            $table->string('telefon', 30)->nullable();
            $table->date('tarikh_lantikan')->nullable();
            $table->string('status_perkhidmatan', 30)->default('aktif');
            $table->timestamp('disegerak_pada')->nullable();
            $table->timestamps();
            $table->index('nama');
        });

        // ---------- Aduan ----------
        Schema::create('aduan', function (Blueprint $table) {
            $table->id();
            $table->string('no_rujukan', 30)->unique();
            $table->foreignId('saluran_id')->constrained('saluran_aduan');
            $table->foreignId('kategori_id')->constrained('kategori_aduan');
            $table->string('no_rujukan_saluran')->nullable();
            $table->date('tarikh_terima');
            $table->date('tarikh_kejadian')->nullable();

            // Maklumat pengadu (boleh tanpa nama). Medan peribadi disulitkan.
            $table->boolean('tanpa_nama')->default(false);
            $table->text('nama_pengadu')->nullable();
            $table->text('no_kp_pengadu')->nullable();
            $table->text('telefon_pengadu')->nullable();
            $table->text('emel_pengadu')->nullable();
            $table->text('alamat_pengadu')->nullable();

            $table->string('tajuk');
            $table->longText('keterangan');
            $table->string('lokasi');
            $table->foreignId('jabatan_terlibat_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $table->string('keutamaan', 20)->default('sederhana');

            $table->string('status', 30)->default('baru')->index();
            $table->foreignId('pegawai_siasatan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diagih_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tarikh_agihan')->nullable();
            $table->date('tarikh_akhir_sla')->index();
            $table->timestamp('tarikh_selesai')->nullable();
            $table->string('keputusan_siasatan', 30)->nullable();
            $table->text('sebab_tidak_diteruskan')->nullable();
            $table->date('retensi_hingga')->nullable();

            $table->foreignId('dicipta_oleh')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tarikh_terima']);
        });

        Schema::create('aduan_pihak_terlibat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aduan_id')->constrained('aduan')->cascadeOnDelete();
            $table->foreignId('pegawai_hr_id')->nullable()->constrained('pegawai_hr')->nullOnDelete();
            $table->string('nama');
            $table->string('no_pekerja', 30)->nullable();
            $table->string('jawatan')->nullable();
            $table->string('jabatan')->nullable();
            // didakwa | saksi | lain
            $table->string('peranan', 20)->default('didakwa');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('siasatan_aktiviti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aduan_id')->constrained('aduan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            // temubual | lawatan | semakan_dokumen | surat | catatan | lain
            $table->string('jenis', 30);
            $table->dateTime('tarikh_aktiviti');
            $table->string('tajuk');
            $table->longText('butiran');
            $table->timestamps();
        });

        Schema::create('bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aduan_id')->constrained('aduan')->cascadeOnDelete();
            $table->string('no_bukti', 20);
            // dokumen | gambar | audio | video | kenyataan | lain
            $table->string('jenis', 20);
            $table->string('tajuk');
            $table->text('keterangan')->nullable();
            $table->string('sumber')->nullable();
            $table->date('tarikh_diperoleh')->nullable();
            $table->string('nama_fail_asal')->nullable();
            $table->string('laluan_fail')->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('saiz')->nullable();
            $table->char('sha256', 64)->nullable();
            // belum_disahkan | disahkan | ditolak
            $table->string('status_pengesahan', 20)->default('belum_disahkan');
            $table->text('catatan_pengesahan')->nullable();
            $table->foreignId('disahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tarikh_disahkan')->nullable();
            $table->foreignId('dimuatnaik_oleh')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['aduan_id', 'no_bukti']);
        });

        // ---------- Pustaka peraturan (sumber rujukan AI) ----------
        Schema::create('rujukan_peraturan', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 50)->unique();
            $table->string('tajuk');
            // peraturan | pekeliling | akta | sop | garis_panduan
            $table->string('jenis', 30);
            $table->string('penerbit')->nullable();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->string('sumber_url')->nullable();
            $table->text('ringkasan')->nullable();
            $table->string('nama_fail')->nullable();
            $table->string('laluan_fail')->nullable();
            $table->boolean('teks_rasmi')->default(false);
            $table->boolean('aktif')->default(true);
            $table->foreignId('dikemaskini_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rujukan_seksyen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rujukan_id')->constrained('rujukan_peraturan')->cascadeOnDelete();
            $table->string('no_seksyen', 50)->nullable();
            $table->string('tajuk')->nullable();
            $table->longText('kandungan');
            $table->unsignedInteger('susunan')->default(0);
            $table->timestamps();
        });

        // ---------- AI ----------
        Schema::create('ai_cadangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aduan_id')->nullable()->constrained('aduan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            // cadangan_kes | analisis_laporan
            $table->string('jenis', 30);
            $table->string('penyedia', 20);
            $table->string('model', 80)->nullable();
            $table->longText('prompt');
            $table->longText('jawapan')->nullable();
            $table->json('rujukan_dipetik')->nullable();
            $table->json('metadata')->nullable();
            // belum_disemak | diterima | diterima_sebahagian | ditolak
            $table->string('status_semakan', 30)->default('belum_disemak');
            $table->text('ulasan_pegawai')->nullable();
            $table->foreignId('disemak_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disemak_pada')->nullable();
            $table->text('ralat')->nullable();
            $table->unsignedInteger('tempoh_ms')->nullable();
            $table->timestamps();
        });

        // ---------- Cadangan tindakan & kelulusan ----------
        Schema::create('cadangan_tindakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aduan_id')->constrained('aduan')->cascadeOnDelete();
            $table->unsignedSmallInteger('versi')->default(1);
            $table->foreignId('disediakan_oleh')->constrained('users');
            $table->string('keputusan_siasatan', 30);
            $table->longText('dapatan');
            $table->longText('justifikasi');
            $table->string('peruntukan_peraturan')->nullable();
            $table->foreignId('ai_cadangan_id')->nullable()->constrained('ai_cadangan')->nullOnDelete();
            // draf | dihantar | diluluskan | dikembalikan
            $table->string('status', 20)->default('draf');
            $table->timestamp('dihantar_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('cadangan_tindakan_jenis', function (Blueprint $table) {
            $table->foreignId('cadangan_tindakan_id')->constrained('cadangan_tindakan')->cascadeOnDelete();
            $table->foreignId('jenis_tindakan_id')->constrained('jenis_tindakan');
            $table->primary(['cadangan_tindakan_id', 'jenis_tindakan_id']);
        });

        Schema::create('kelulusan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cadangan_tindakan_id')->constrained('cadangan_tindakan')->cascadeOnDelete();
            $table->foreignId('aduan_id')->constrained('aduan')->cascadeOnDelete();
            $table->foreignId('pelulus_id')->constrained('users');
            // lulus | kembalikan
            $table->string('keputusan', 20);
            $table->text('ulasan')->nullable();
            $table->timestamps();
        });

        Schema::create('tindakan_diambil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aduan_id')->constrained('aduan')->cascadeOnDelete();
            $table->foreignId('jenis_tindakan_id')->constrained('jenis_tindakan');
            $table->date('tarikh_tindakan');
            $table->string('no_rujukan_surat')->nullable();
            $table->text('butiran');
            $table->foreignId('direkod_oleh')->constrained('users');
            $table->timestamps();
        });

        // ---------- Jejak ----------
        Schema::create('status_sejarah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aduan_id')->constrained('aduan')->cascadeOnDelete();
            $table->string('status_dari', 30)->nullable();
            $table->string('status_ke', 30);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sla_peringatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aduan_id')->constrained('aduan')->cascadeOnDelete();
            $table->string('jenis', 30);
            $table->timestamp('dihantar_pada')->useCurrent();
            $table->index(['aduan_id', 'jenis']);
        });

        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tindakan', 60)->index();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->foreignId('aduan_id')->nullable()->constrained('aduan')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->json('nilai_lama')->nullable();
            $table->json('nilai_baru')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['model_type', 'model_id']);
        });

        Schema::create('otp_kod', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kod_hash');
            $table->timestamp('tamat_pada');
            $table->unsignedTinyInteger('cubaan')->default(0);
            $table->timestamp('digunakan_pada')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE rujukan_seksyen ADD FULLTEXT ft_seksyen (tajuk, kandungan)');
            DB::statement('ALTER TABLE aduan ADD FULLTEXT ft_aduan (tajuk, keterangan, lokasi)');
        }
    }

    public function down(): void
    {
        foreach ([
            'notifications', 'otp_kod', 'audit_log', 'sla_peringatan', 'status_sejarah',
            'tindakan_diambil', 'kelulusan', 'cadangan_tindakan_jenis', 'cadangan_tindakan',
            'ai_cadangan', 'rujukan_seksyen', 'rujukan_peraturan', 'bukti', 'siasatan_aktiviti',
            'aduan_pihak_terlibat', 'aduan', 'pegawai_hr', 'jenis_tindakan', 'saluran_aduan',
            'kategori_aduan', 'jabatan',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
