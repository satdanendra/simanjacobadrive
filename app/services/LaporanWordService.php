<?php

namespace App\Services;

use App\Models\LaporanHarian;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\Shared\Converter;

class LaporanWordService
{
    public function generateLaporan(LaporanHarian $laporan)
    {
        $phpWord = new PhpWord();

        // Set bahasa Indonesia
        $phpWord->getSettings()->setThemeFontLang(new Language('id-ID'));

        // Buat section
        $section = $phpWord->addSection([
            'marginTop' => Converter::cmToTwip(2),
            'marginBottom' => Converter::cmToTwip(2),
            'marginLeft' => Converter::cmToTwip(2),
            'marginRight' => Converter::cmToTwip(2),
        ]);

        // Header styles
        $headerStyle = ['name' => 'Arial', 'size' => 14, 'bold' => true];
        $normalStyle = ['name' => 'Arial', 'size' => 10];
        $boldStyle = ['name' => 'Arial', 'size' => 10, 'bold' => true];

        // Title
        $section->addText('Laporan Harian Pelaksanaan Pekerjaan', $headerStyle, ['alignment' => 'center']);
        $section->addTextBreak(2);

        // Identitas Pegawai
        $this->addIdentitasPegawai($section, $laporan, $boldStyle, $normalStyle);
        $section->addTextBreak();

        // Rencana Kerja dan Kegiatan
        $this->addRencanaKerja($section, $laporan, $boldStyle, $normalStyle);
        $section->addTextBreak();

        // Dasar Pelaksanaan Kegiatan
        $this->addDasarPelaksanaan($section, $laporan, $boldStyle, $normalStyle);
        $section->addTextBreak();

        // Bukti Pelaksanaan Pekerjaan
        $this->addBuktiPelaksanaan($section, $laporan, $boldStyle, $normalStyle);
        $section->addTextBreak();

        // Kendala dan Solusi
        $this->addKendalaSolusi($section, $laporan, $boldStyle, $normalStyle);
        $section->addTextBreak();

        // Catatan
        $this->addCatatan($section, $laporan, $boldStyle, $normalStyle);
        $section->addTextBreak(2);

        // Pengesahan
        $this->addPengesahan($section, $laporan, $boldStyle, $normalStyle);

        // Save dokumen
        $filename = 'laporan_' . time() . '.docx';
        $tempPath = storage_path('app/temp/' . $filename);

        // Pastikan folder temp ada
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return $tempPath;
    }

    private function addIdentitasPegawai($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 2, 'bgColor' => '7CD440'])
            ->addText('Identitas Pegawai', $boldStyle);

        // Nama
        $table->addRow();
        $table->addCell(3000, ['width' => 3000])->addText('Nama Pegawai', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->user->name, $boldStyle);

        // Email
        $table->addRow();
        $table->addCell(3000, ['width' => 3000])->addText('Email', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->user->email, $boldStyle);
    }

    private function addRencanaKerja($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 2, 'bgColor' => '7CD440'])
            ->addText('Rencana Kerja dan Kegiatan', $boldStyle);

        // Tim Kegiatan
        $table->addRow();
        $table->addCell(3000, ['width' => 3000])->addText('Tim Kegiatan', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->proyek->tim->nama_tim, $boldStyle);

        // Proyek
        $table->addRow();
        $table->addCell(3000, ['width' => 3000])->addText('Proyek', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->proyek->nama_proyek, $boldStyle);

        // Waktu
        $table->addRow();
        $table->addCell(3000, ['width' => 3000])->addText('Waktu', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->formatted_waktu, $boldStyle);

        // Kegiatan
        $table->addRow();
        $table->addCell(3000, ['width' => 3000, 'valign' => 'top'])->addText('Kegiatan', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->kegiatan, $boldStyle);

        // Capaian
        $table->addRow();
        $table->addCell(3000, ['width' => 3000, 'valign' => 'top'])->addText('Capaian', $normalStyle);
        $table->addCell(7000, ['width' => 7000])->addText($laporan->capaian, $boldStyle);
    }

    private function addDasarPelaksanaan($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 2, 'bgColor' => '7CD440'])
            ->addText('Dasar Pelaksanaan Kegiatan', $boldStyle);

        // Isi
        if ($laporan->dasarPelaksanaans->count() > 0) {
            foreach ($laporan->dasarPelaksanaans as $index => $dasar) {
                $number = $index + 1;
                $text = $number . '. ' . $dasar->formatted_deskripsi;
                $table->addRow();
                $table->addCell(1000)->addText((string)$number . '.', $normalStyle);
                $table->addCell(9000)->addText($dasar->formatted_deskripsi, $normalStyle);
            }
        } else {
            $table->addRow();
            $table->addCell(null)->addText('1. -', $normalStyle);
        }
    }

    private function addBuktiPelaksanaan($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(10000, ['bgColor' => '7CD440'])->addText(
            'Bukti Pelaksanaan Pekerjaan (Foto/Dokumentasi/Screenshot/Print Screen, dll)',
            $boldStyle
        );

        // Isi
        if ($laporan->buktiDukungs->count() > 0) {
            foreach ($laporan->buktiDukungs as $index => $bukti) {
                $number = $index + 1;
                $text = $number . '. ' . $bukti->nama_file;
                if ($bukti->keterangan) {
                    $text .= ' - ' . $bukti->keterangan;
                }
                $table->addRow();
                $table->addCell(null)->addText($text, $normalStyle);
            }
        } else {
            $table->addRow();
            $table->addCell(null)->addText('1. (terlampir)', $normalStyle);
        }
    }

    private function addKendalaSolusi($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 3, 'bgColor' => '7CD440'])->addText(
            'Kendala dan Solusi',
            $boldStyle
        );
        $table->addRow();
        $table->addCell(1000, ['bgColor' => 'C2EA92'])->addText('No', $boldStyle, ['alignment' => 'center']);
        $table->addCell(4500, ['bgColor' => 'C2EA92'])->addText('Kendala/Permasalahan', $boldStyle, ['alignment' => 'center']);
        $table->addCell(4500, ['bgColor' => 'C2EA92'])->addText('Solusi', $boldStyle, ['alignment' => 'center']);

        // Content
        $table->addRow();
        $table->addCell(1000)->addText('1.', $normalStyle, ['alignment' => 'center']);
        $table->addCell(4500)->addText($laporan->kendala ?: '-', $normalStyle);
        $table->addCell(4500)->addText($laporan->solusi ?: '-', $normalStyle);
    }

    private function addCatatan($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(10000, ['bgColor' => '7CD440'])->addText(
            'Catatan',
            $boldStyle
        );

        // Isi
        if ($laporan->catatan) {
                $table->addRow();
                $table->addCell(null)->addText($laporan->catatan, $normalStyle);
        } else {
            $table->addRow();
            $table->addCell(null)->addText('-', $normalStyle);
        }
    }

    private function addPengesahan($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 3, 'bgColor' => '7CD440'])->addText('Pengesahan', $boldStyle, ['alignment' => 'center']);

        // Sub header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 3])->addText('Mengetahui,', $normalStyle, ['alignment' => 'center']);
        $table->addRow();
        $table->addCell(3333)->addText('Pegawai yang Melaporkan,', $normalStyle, ['alignment' => 'center']);
        $table->addCell(3333)->addText('Penanggung Jawab Kegiatan,', $normalStyle, ['alignment' => 'center']);
        $table->addCell(3333)->addText('Atasan Langsung Pegawai', $normalStyle, ['alignment' => 'center']);

        // Nama pegawai
        $table->addRow(1000);
        $table->addCell(3333)->addText('', $normalStyle);
        $table->addCell(3333)->addText('', $normalStyle);
        $table->addCell(3333)->addText('', $normalStyle);

        // Tanda tangan
        $table->addRow();
        $table->addCell(3333)->addText($laporan->user->name, $boldStyle, ['alignment' => 'center']);
        $table->addCell(3333)->addText('BENI NURROFIK', $boldStyle, ['alignment' => 'center']);
        $table->addCell(3333)->addText('ALUISIUS ABRIANTA', $boldStyle, ['alignment' => 'center']);
    }
}
