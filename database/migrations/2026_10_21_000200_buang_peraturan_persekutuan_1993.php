<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Peraturan Persekutuan 1993 (P.U. (A) 395/1993) tidak lagi digunakan; sistem menggunakan
 * Peraturan-Peraturan Pegawai Awam MPSP (Kelakuan dan Tatatertib) 1995 sahaja.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Juga buang ringkasan sementara MBSP 1995 yang diganti dengan teks penuh Warta (PGPU-16-1995).
        DB::table('rujukan_peraturan')->whereIn('kod', ['PU-A-395-1993', 'PP-MBSP-KT-1995'])->delete();
    }

    public function down(): void {}
};
