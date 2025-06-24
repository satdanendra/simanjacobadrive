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
        $phpWord->getSettings()->setThemeFontLang(new Language(0x0421));
        
        // Buat section
        $section = $phpWord->addSection([
            'marginTop' => Converter::cmToTwip(2),
            'marginBottom' => Converter::cmToTwip(2),
            'marginLeft' => Converter::cmToTwip(2),
            'marginRight' => Converter::cmToTwip(2),
        ]);

        // Header styles
        $headerStyle = ['name' => 'Arial', 'size' => 14, 'bold' => true];
        $normalStyle = ['name' => 'Arial', 'size' => 11];
        $boldStyle = ['name' => 'Arial', 'size' => 11, 'bold' => true];

        // Title
        $section->addText('LAPORAN HARIAN PELAKSANAAN KEGIATAN', $headerStyle, ['alignment' => 'center']);
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
        $table->addCell(2000)->addText('Identitas Pegawai', $boldStyle);
        $table->addCell(null);

        // Nama
        $table->addRow();
        $table->addCell(2000)->addText('Nama Pegawai', $normalStyle);
        $table->addCell(null)->addText($laporan->user->name, $boldStyle);

        // Email
        $table->addRow();
        $table->addCell(2000)->addText('Email', $normalStyle);
        $table->addCell(null)->addText($laporan->user->email, $boldStyle);
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
        $table->addCell(null, ['gridSpan' => 2])->addText('Rencana Kerja dan Kegiatan', $boldStyle);

        // Tim Kegiatan
        $table->addRow();
        $table->addCell(2000)->addText('Tim Kegiatan', $normalStyle);
        $table->addCell(null)->addText($laporan->proyek->tim->nama_tim, $boldStyle);

        // Proyek
        $table->addRow();
        $table->addCell(2000)->addText('Proyek', $normalStyle);
        $table->addCell(null)->addText($laporan->proyek->nama_proyek, $boldStyle);

        // Waktu
        $table->addRow();
        $table->addCell(2000)->addText('Waktu', $normalStyle);
        $table->addCell(null)->addText($laporan->formatted_waktu, $boldStyle);

        // Kegiatan
        $table->addRow();
        $table->addCell(2000)->addText('Kegiatan', $normalStyle);
        $table->addCell(null)->addText($laporan->kegiatan, $boldStyle);

        // Capaian
        $table->addRow();
        $table->addCell(2000)->addText('Capaian', $normalStyle);
        $table->addCell(null)->addText($laporan->capaian, $boldStyle);
    }

    private function addDasarPelaksanaan($section, $laporan, $boldStyle, $normalStyle)
    {
        $section->addText('Dasar Pelaksanaan Kegiatan', $boldStyle);
        $section->addTextBreak();

        if ($laporan->dasarPelaksanaans->count() > 0) {
            foreach ($laporan->dasarPelaksanaans as $index => $dasar) {
                $number = $index + 1;
                $text = $number . '. ' . $dasar->formatted_deskripsi;
                $section->addText($text, $normalStyle);
            }
        } else {
            $section->addText('1. -', $normalStyle);
        }
    }

    private function addBuktiPelaksanaan($section, $laporan, $boldStyle, $normalStyle)
    {
        $section->addText('Bukti Pelaksanaan Pekerjaan (Foto/Dokumentasi/Screenshot/Print Screen, dll)', $boldStyle);
        $section->addTextBreak();

        if ($laporan->buktiDukungs->count() > 0) {
            foreach ($laporan->buktiDukungs as $index => $bukti) {
                $number = $index + 1;
                $text = $number . '. ' . $bukti->nama_file;
                if ($bukti->keterangan) {
                    $text .= ' - ' . $bukti->keterangan;
                }
                $section->addText($text, $normalStyle);
            }
        } else {
            $section->addText('1. (terlampir)', $normalStyle);
        }
    }

    private function addKendalaSolusi($section, $laporan, $boldStyle, $normalStyle)
    {
        $section->addText('Kendala dan Solusi', $boldStyle);
        $section->addTextBreak();

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(1000)->addText('No', $boldStyle, ['alignment' => 'center']);
        $table->addCell(null)->addText('Kendala/Permasalahan', $boldStyle, ['alignment' => 'center']);
        $table->addCell(null)->addText('Solusi', $boldStyle, ['alignment' => 'center']);

        // Content
        $table->addRow();
        $table->addCell(1000)->addText('1.', $normalStyle, ['alignment' => 'center']);
        $table->addCell(null)->addText($laporan->kendala ?: '-', $normalStyle);
        $table->addCell(null)->addText($laporan->solusi ?: '-', $normalStyle);
    }

    private function addCatatan($section, $laporan, $boldStyle, $normalStyle)
    {
        $section->addText('Catatan', $boldStyle);
        $section->addTextBreak();

        if ($laporan->catatan) {
            $section->addText($laporan->catatan, $normalStyle);
        } else {
            $section->addText('-', $normalStyle);
        }
    }

    private function addPengesahan($section, $laporan, $boldStyle, $normalStyle)
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
            'width' => 100 * 50
        ]);

        // Header
        $table->addRow();
        $table->addCell(null, ['gridSpan' => 3])->addText('Pengesahan', $boldStyle, ['alignment' => 'center']);

        // Sub header
        $table->addRow();
        $table->addCell(null)->addText('Pegawai yang Melaporkan,', $normalStyle, ['alignment' => 'center']);
        $table->addCell(null)->addText('', $normalStyle);
        $table->addCell(null)->addText('', $normalStyle);

        // Nama pegawai
        $table->addRow(1000);
        $table->addCell(null)->addText('', $normalStyle);
        $table->addCell(null)->addText('', $normalStyle);
        $table->addCell(null)->addText('', $normalStyle);

        // Tanda tangan
        $table->addRow();
        $table->addCell(null)->addText($laporan->user->name, $boldStyle, ['alignment' => 'center']);
        $table->addCell(null)->addText('', $normalStyle);
        $table->addCell(null)->addText('', $normalStyle);
    }
}