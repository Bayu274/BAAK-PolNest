<?php
/**
 * XlsxReader — parser .xlsx ringan TANPA library eksternal
 *
 * Membaca seluruh baris dari worksheet pertama file Excel (.xlsx) menjadi
 * array 2 dimensi: setiap baris = array nilai sel (string), diindeks urut
 * dari kolom pertama. Mendukung inline string, shared string, dan angka.
 *
 * Keterbatasan yang disengaja (di luar kebutuhan import data pembimbing):
 *   - Formula tidak dievaluasi (nilai hasil tidak dibaca dari cache).
 *   - Tanggal serial tidak dikonversi (dirender sebagai angka polos).
 *
 * Dipakai oleh AdvisorController::processImport() untuk menerima file
 * Excel format "1 sheet berisi beberapa tabel" (request klien).
 */

class XlsxReader {

    /**
     * @param string $filePath Path absolut file .xlsx
     * @return array<int, array<int, string>> Baris-baris sel
     * @throws RuntimeException Bila file bukan .xlsx valid / tidak terbaca
     */
    public static function read(string $filePath): array {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi ZipArchive tidak tersedia di server — tidak dapat membaca file Excel (.xlsx).');
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('File tidak dapat dibuka sebagai arsip .xlsx yang valid.');
        }

        try {
            $sharedStrings = self::loadSharedStrings($zip);
            $worksheetXml  = self::loadFirstWorksheet($zip);
        } finally {
            $zip->close();
        }

        if ($worksheetXml === null) {
            throw new RuntimeException('File Excel tidak memiliki worksheet.');
        }

        return self::parseSheet($worksheetXml, $sharedStrings);
    }

    /**
     * Membaca seluruh string bersama (xl/sharedStrings.xml) bila ada.
     *
     * @return string[] Daftar string berindeks (dipetakan oleh sel bertipe "s")
     */
    private static function loadSharedStrings(ZipArchive $zip): array {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $doc = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT);
        if ($doc === false) {
            return [];
        }

        $strings = [];
        foreach ($doc->xpath('//*[local-name()="si"]') as $si) {
            $parts = [];
            foreach ($si->xpath('.//*[local-name()="t"]') as $t) {
                $parts[] = (string) $t;
            }
            $strings[] = implode('', $parts);
        }
        return $strings;
    }

    /**
     * Mengambil XML worksheet pertama (mengikuti urutan workbook.xml).
     *
     * @return string|null Konten XML worksheet, atau null bila tidak ditemukan
     */
    private static function loadFirstWorksheet(ZipArchive $zip): ?string {
        $rId = null;

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml !== false) {
            $doc = @simplexml_load_string($workbookXml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT);
            if ($doc !== false) {
                $sheets = $doc->xpath('//*[local-name()="sheet"]');
                if (!empty($sheets)) {
                    $attrs = $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                    $rId = isset($attrs['id']) ? (string) $attrs['id'] : null;
                    if ($rId === null) {
                        $attrs = $sheets[0]->attributes();
                        $rId = isset($attrs['id']) ? (string) $attrs['id'] : null;
                    }
                }
            }
        }

        // Map rId -> target worksheet lewat workbook.xml.rels
        if ($rId !== null) {
            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($relsXml !== false) {
                $doc = @simplexml_load_string($relsXml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT);
                if ($doc !== false) {
                    foreach ($doc->xpath('//*[local-name()="Relationship"]') as $rel) {
                        $attrs = $rel->attributes();
                        if (isset($attrs['Id'], $attrs['Target']) && (string) $attrs['Id'] === $rId) {
                            $target = (string) $attrs['Target'];
                            if (!str_starts_with($target, '/')) {
                                $target = 'xl/' . ltrim($target, '/');
                            }
                            $target = ltrim($target, '/');
                            $xml = $zip->getFromName($target);
                            return $xml === false ? null : $xml;
                        }
                    }
                }
            }
        }

        // Fallback: coba nama entry standar
        foreach (['xl/worksheets/sheet1.xml', 'xl/worksheets/sheet.xml'] as $entry) {
            $xml = $zip->getFromName($entry);
            if ($xml !== false) {
                return $xml;
            }
        }

        // Fallback terakhir: scan sheet1..sheet32
        for ($i = 1; $i <= 32; $i++) {
            $xml = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
            if ($xml !== false) {
                return $xml;
            }
        }

        return null;
    }

    /**
     * Parse XML worksheet menjadi array baris.
     *
     * @param string $xml            Konten XML worksheet
     * @param string[] $sharedStrings Daftar shared string
     * @return array<int, array<int, string>>
     * @throws RuntimeException Bila XML worksheet rusak
     */
    private static function parseSheet(string $xml, array $sharedStrings): array {
        $doc = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT);
        if ($doc === false) {
            throw new RuntimeException('Worksheet tidak dapat dibaca (XML rusak).');
        }

        $rows = [];
        foreach ($doc->xpath('//*[local-name()="row"]') as $rowEl) {
            $cells = [];
            foreach ($rowEl->xpath('./*[local-name()="c"]') as $cell) {
                $attrs    = $cell->attributes();
                $ref      = isset($attrs['r']) ? (string) $attrs['r'] : '';
                $colIndex = self::columnIndex($ref);
                if ($colIndex < 0) {
                    continue;
                }
                $cells[$colIndex] = self::cellValue($cell, $sharedStrings);
            }
            ksort($cells);
            $rows[] = array_values($cells);
        }

        return $rows;
    }

    /**
     * Mengambil nilai sebuah sel sebagai string, berdasarkan tipenya.
     */
    private static function cellValue(SimpleXMLElement $cell, array $sharedStrings): string {
        $attrs = $cell->attributes();
        $type  = isset($attrs['t']) ? (string) $attrs['t'] : '';

        if ($type === 'inlineStr') {
            $parts = [];
            foreach ($cell->xpath('.//*[local-name()="t"]') as $t) {
                $parts[] = (string) $t;
            }
            return implode('', $parts);
        }

        if ($type === 's') {
            $idx = (int) self::rawValue($cell);
            return $sharedStrings[$idx] ?? '';
        }

        // 'str' (hasil formula) maupun angka biasa → string polos
        return self::rawValue($cell);
    }

    /**
     * Nilai mentah sel dari node <v>.
     */
    private static function rawValue(SimpleXMLElement $cell): string {
        $nodes = $cell->xpath('./*[local-name()="v"]');
        return !empty($nodes) ? (string) $nodes[0] : '';
    }

    /**
     * Konversi referensi kolom Excel ("A", "B", ..., "AA", "AB", ...) menjadi
     * indeks 0-based. Mengembalikan -1 bila referensi tidak valid.
     */
    private static function columnIndex(string $cellRef): int {
        $letters = '';
        foreach (str_split($cellRef) as $ch) {
            if (ctype_alpha($ch)) {
                $letters .= strtoupper($ch);
            } else {
                break;
            }
        }
        if ($letters === '') {
            return -1;
        }

        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }
}
