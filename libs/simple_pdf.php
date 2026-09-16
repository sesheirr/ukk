<?php
/**
 * simple_pdf.php
 * ---------------------------------------------------------
 * Generator PDF minimal & mandiri (tanpa library pihak ketiga
 * seperti TCPDF/mPDF/dompdf/FPDF), karena environment ini tidak
 * bisa akses internet untuk composer install. Hasilnya tetap
 * file .pdf ASLI (bukan halaman HTML) yang bisa dibuka langsung
 * oleh WhatsApp, Gmail, atau aplikasi PDF viewer apa pun.
 *
 * Cara pakai:
 *   $pdfBytes = buildSlipPdf($slip); // $slip = baris dari riwayat_gaji
 *   header('Content-Type: application/pdf');
 *   echo $pdfBytes;
 * ---------------------------------------------------------
 */

/**
 * Escape teks supaya aman dimasukkan ke dalam string PDF: (...)
 */
function pdfEscapeText(string $text): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

/**
 * Menyusun beberapa baris teks menjadi satu file PDF (1 halaman, A4)
 * yang valid secara struktur (header, objects, xref, trailer).
 *
 * $lines: array of [ 'text' => string, 'x' => int, 'y' => int, 'size' => int, 'bold' => bool ]
 */
function buildPdfFromLines(array $lines): string
{
    $content = '';
    foreach ($lines as $l) {
        $font = !empty($l['bold']) ? '/F2' : '/F1';
        $size = $l['size'] ?? 11;
        $text = pdfEscapeText($l['text']);
        $content .= "BT {$font} {$size} Tf {$l['x']} {$l['y']} Td ({$text}) Tj ET\n";
    }

    $objects   = [];
    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] "
                . "/Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>";
    $objects[4] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream";
    $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

    $pdf     = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $num => $body) {
        $offsets[$num] = strlen($pdf);
        $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
    }

    $xrefStart = strlen($pdf);
    $count     = count($objects) + 1;

    $pdf .= "xref\n0 {$count}\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

    return $pdf;
}

/**
 * Membuat isi PDF slip gaji dari satu baris data riwayat_gaji,
 * lalu mengembalikannya sebagai teks biner PDF siap dikirim
 * (header Content-Type: application/pdf).
 */
function buildSlipPdf(array $slip): string
{
    $periode = formatTanggalIndo($slip['periode_awal']) . ' - ' . formatTanggalIndo($slip['periode_akhir']);

    $y = 790;
    $lines = [];

    $lines[] = ['text' => 'SLIP GAJI KARYAWAN', 'x' => 50, 'y' => $y, 'size' => 18, 'bold' => true]; $y -= 22;
    $lines[] = ['text' => 'Periode: ' . $periode, 'x' => 50, 'y' => $y, 'size' => 11]; $y -= 34;

    $lines[] = ['text' => 'Nama    : ' . $slip['nama'], 'x' => 50, 'y' => $y, 'size' => 12]; $y -= 18;
    $lines[] = ['text' => 'NIK     : ' . $slip['nik'], 'x' => 50, 'y' => $y, 'size' => 12]; $y -= 18;
    $lines[] = ['text' => 'Jabatan : ' . $slip['jabatan'], 'x' => 50, 'y' => $y, 'size' => 12]; $y -= 36;

    $lines[] = ['text' => 'PENGHASILAN', 'x' => 50, 'y' => $y, 'size' => 12, 'bold' => true]; $y -= 18;
    $lines[] = ['text' => 'Gaji Pokok', 'x' => 60, 'y' => $y, 'size' => 11];
    $lines[] = ['text' => rupiah((float) $slip['gaji_pokok']), 'x' => 400, 'y' => $y, 'size' => 11]; $y -= 16;
    $lines[] = ['text' => 'Lembur', 'x' => 60, 'y' => $y, 'size' => 11];
    $lines[] = ['text' => rupiah((float) $slip['lembur']), 'x' => 400, 'y' => $y, 'size' => 11]; $y -= 16;
    $lines[] = ['text' => 'Total Penghasilan', 'x' => 60, 'y' => $y, 'size' => 11, 'bold' => true];
    $lines[] = ['text' => rupiah((float) $slip['total_penghasilan']), 'x' => 400, 'y' => $y, 'size' => 11, 'bold' => true]; $y -= 32;

    $lines[] = ['text' => 'POTONGAN', 'x' => 50, 'y' => $y, 'size' => 12, 'bold' => true]; $y -= 18;
    $lines[] = ['text' => 'Pinjaman Karyawan', 'x' => 60, 'y' => $y, 'size' => 11];
    $lines[] = ['text' => rupiah((float) $slip['pinjaman_karyawan']), 'x' => 400, 'y' => $y, 'size' => 11]; $y -= 16;
    $lines[] = ['text' => 'Total Potongan', 'x' => 60, 'y' => $y, 'size' => 11, 'bold' => true];
    $lines[] = ['text' => rupiah((float) $slip['total_potongan']), 'x' => 400, 'y' => $y, 'size' => 11, 'bold' => true]; $y -= 40;

    $lines[] = ['text' => 'GAJI BERSIH', 'x' => 50, 'y' => $y, 'size' => 14, 'bold' => true];
    $lines[] = ['text' => rupiah((float) $slip['gaji_bersih']), 'x' => 400, 'y' => $y, 'size' => 14, 'bold' => true]; $y -= 50;

    $lines[] = ['text' => 'Dicetak otomatis oleh sistem Slip Gaji pada ' . date('d/m/Y H:i'), 'x' => 50, 'y' => $y, 'size' => 9];

    return buildPdfFromLines($lines);
}
