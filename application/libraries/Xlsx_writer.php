<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penulis file .xlsx sederhana (tabel asli Excel, bukan CSV).
 * Tanpa Composer / PhpSpreadsheet - cukup ekstensi zip bawaan PHP.
 *
 * Pakai:
 *   $this->load->library('xlsx_writer');
 *   $this->xlsx_writer->unduh('data-karyawan.xlsx', $header, $baris, 'Karyawan');
 */
class Xlsx_writer
{
    private function esc($v)
    {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function kolom($i)
    {
        $s = '';
        $i++;
        while ($i > 0) { $m = ($i - 1) % 26; $s = chr(65 + $m) . $s; $i = (int)(($i - $m) / 26); }
        return $s;
    }

    public function buat($header, $baris, $nama_sheet = 'Sheet1')
    {
        $rows = '';
        $r = 1;

        $rows .= '<row r="1" s="1">';
        foreach (array_values($header) as $c => $h) {
            $rows .= '<c r="' . $this->kolom($c) . '1" t="inlineStr" s="1"><is><t>' . $this->esc($h) . '</t></is></c>';
        }
        $rows .= '</row>';
        $r = 2;

        foreach ($baris as $b) {
            $rows .= '<row r="' . $r . '">';
            $c = 0;
            foreach (array_values($b) as $v) {
                $ref = $this->kolom($c) . $r;
                if ($v !== null && $v !== '' && is_numeric($v) && strlen((string)$v) < 15 && substr((string)$v, 0, 1) !== '0') {
                    $rows .= '<c r="' . $ref . '"><v>' . $v . '</v></c>';
                } else {
                    $rows .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $this->esc($v) . '</t></is></c>';
                }
                $c++;
            }
            $rows .= '</row>';
            $r++;
        }

        $kolom_lebar = '<cols>';
        foreach (array_values($header) as $i => $h) {
            $w = max(12, min(45, strlen($h) + 6));
            $kolom_lebar .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $kolom_lebar .= '</cols>';

        $akhir = $this->kolom(count($header) - 1) . max(1, count($baris) + 1);

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetPr><outlinePr summaryBelow="1" summaryRight="1"/></sheetPr>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . $kolom_lebar
            . '<sheetData>' . $rows . '</sheetData>'
            . '<autoFilter ref="A1:' . $akhir . '"/>'
            . '</worksheet>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->esc(substr($nama_sheet, 0, 30)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F6F5C"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf></cellXfs>'
            . '</styleSheet>';

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $wbrels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbrels);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $isi = file_get_contents($tmp);
        @unlink($tmp);
        return $isi;
    }

    public function unduh($nama_file, $header, $baris, $nama_sheet = 'Sheet1')
    {
        $isi = $this->buat($header, $baris, $nama_sheet);
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nama_file . '"');
        header('Content-Length: ' . strlen($isi));
        header('Cache-Control: max-age=0');
        echo $isi;
        exit;
    }
}
