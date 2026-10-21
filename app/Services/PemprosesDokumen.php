<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser;

/**
 * Ekstrak teks daripada PDF/TXT dan pecahkan kepada seksyen untuk pustaka rujukan AI.
 */
class PemprosesDokumen
{
    private const SAIZ_CEBISAN = 3000;

    /** Corak tajuk seksyen yang lazim dalam peraturan / akta / pekeliling. */
    private const CORAK_TAJUK = '/^\s*((?:Peraturan|Seksyen|Perenggan|Bahagian|Regulation|Section)\s+\d+[A-Z]?\b.*|\d+[A-Z]?\.\s+\S.*)$/mu';

    public function ekstrak(UploadedFile|string $fail): string
    {
        $laluan = $fail instanceof UploadedFile ? $fail->getRealPath() : $fail;
        $ext = strtolower($fail instanceof UploadedFile ? $fail->getClientOriginalExtension() : pathinfo($laluan, PATHINFO_EXTENSION));

        $teks = $ext === 'pdf'
            ? (new Parser)->parseFile($laluan)->getText()
            : (string) file_get_contents($laluan);

        if (! mb_check_encoding($teks, 'UTF-8')) {
            $teks = mb_convert_encoding($teks, 'UTF-8', 'Windows-1252');
        }

        $teks = str_replace(["\r\n", "\r", "\t", "\u{00A0}"], ["\n", "\n", ' ', ' '], $teks);
        $teks = preg_replace('/[ ]{2,}/', ' ', $teks);
        $teks = preg_replace("/\n{3,}/", "\n\n", $teks);

        return trim($teks);
    }

    /**
     * @return array<int, array{no_seksyen: ?string, tajuk: ?string, kandungan: string}>
     */
    public function pecah(string $teks): array
    {
        if ($teks === '') {
            return [];
        }

        $seksyen = $this->pecahIkutTajuk($teks);

        // Jika tajuk tidak dapat dikesan dengan baik, guna cebisan saiz tetap.
        if (count($seksyen) < 2) {
            $seksyen = $this->pecahIkutSaiz($teks);
        }

        // Seksyen yang terlalu panjang dipecah lagi supaya sesuai untuk carian.
        $hasil = [];
        foreach ($seksyen as $s) {
            if (mb_strlen($s['kandungan']) <= self::SAIZ_CEBISAN * 1.5) {
                $hasil[] = $s;

                continue;
            }
            foreach ($this->pecahIkutSaiz($s['kandungan']) as $i => $c) {
                $hasil[] = [
                    'no_seksyen' => $s['no_seksyen'],
                    'tajuk' => trim(($s['tajuk'] ?? '').' (bhg. '.($i + 1).')'),
                    'kandungan' => $c['kandungan'],
                ];
            }
        }

        return $hasil;
    }

    private function pecahIkutTajuk(string $teks): array
    {
        preg_match_all(self::CORAK_TAJUK, $teks, $m, PREG_OFFSET_CAPTURE);
        $padanan = $m[1] ?? [];
        if (count($padanan) < 2) {
            return [];
        }

        $hasil = [];
        $mukadimah = trim(substr($teks, 0, $padanan[0][1]));
        if (mb_strlen($mukadimah) > 50) {
            $hasil[] = ['no_seksyen' => null, 'tajuk' => 'Mukadimah', 'kandungan' => $mukadimah];
        }

        foreach ($padanan as $i => [$baris, $ofset]) {
            $akhir = $padanan[$i + 1][1] ?? strlen($teks);
            $kandungan = trim(substr($teks, $ofset, $akhir - $ofset));
            if (mb_strlen($kandungan) < 20) {
                continue;
            }
            [$no, $tajuk] = $this->asingNo(trim($baris));
            $hasil[] = ['no_seksyen' => $no, 'tajuk' => $tajuk, 'kandungan' => $kandungan];
        }

        return $hasil;
    }

    private function pecahIkutSaiz(string $teks): array
    {
        $perenggan = preg_split("/\n\s*\n/", $teks);
        $hasil = [];
        $semasa = '';

        foreach ($perenggan as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            // Perenggan tunggal yang sangat panjang dipotong mengikut ayat.
            while (mb_strlen($p) > self::SAIZ_CEBISAN) {
                $potong = mb_strrpos(mb_substr($p, 0, self::SAIZ_CEBISAN), '. ') ?: self::SAIZ_CEBISAN;
                $bahagian = trim(mb_substr($p, 0, $potong + 1));
                $p = trim(mb_substr($p, $potong + 1));
                if ($semasa !== '') {
                    $hasil[] = $semasa;
                    $semasa = '';
                }
                $hasil[] = $bahagian;
            }
            if ($semasa !== '' && mb_strlen($semasa) + mb_strlen($p) > self::SAIZ_CEBISAN) {
                $hasil[] = $semasa;
                $semasa = '';
            }
            $semasa = $semasa === '' ? $p : $semasa."\n\n".$p;
        }
        if ($semasa !== '') {
            $hasil[] = $semasa;
        }

        return array_map(fn ($k, $i) => [
            'no_seksyen' => null,
            'tajuk' => 'Bahagian '.($i + 1),
            'kandungan' => $k,
        ], $hasil, array_keys($hasil));
    }

    /** "Peraturan 4. Tatakelakuan" → ['Peraturan 4', 'Tatakelakuan'] */
    private function asingNo(string $baris): array
    {
        if (preg_match('/^((?:Peraturan|Seksyen|Perenggan|Bahagian|Regulation|Section)\s+\d+[A-Z]?)[\s.:\-–]*(.*)$/u', $baris, $m)
            || preg_match('/^(\d+[A-Z]?)\.\s*(.*)$/u', $baris, $m)) {
            return [$m[1], mb_substr(trim($m[2]), 0, 200) ?: null];
        }

        return [null, mb_substr($baris, 0, 200)];
    }
}
