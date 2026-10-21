<?php
/**
 * Admin > download a report as Excel (.xlsx), PDF or CSV.
 *   export.php?report=orders&format=xlsx&status=pending
 * report: sales | orders | inventory | customers | payments  (see REPORT_TYPES in includes/reports.php)
 * The other parameters are the same filters the report's admin page uses.
 */
require_once __DIR__ . '/../includes/init.php';
require_admin();                                        // not logged in as admin/staff -> login page, no file
require_once __DIR__ . '/../includes/reports.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

ini_set('display_errors', '0');                         // a PHP notice inside the file would corrupt it

$type   = $_GET['report'] ?? '';
$format = $_GET['format'] ?? '';
if (!isset(REPORT_TYPES[$type]) || !in_array($format, ['xlsx', 'pdf', 'csv'], true)) {
    http_response_code(404);
    exit('Unknown report or format.');
}

$report = report_build($type, report_filters($_GET));
$report['generated'] = date('Y-m-d H:i');
$report['shop'] = setting('shop_name', 'Kabakal Fitness Equipment');

$file = tempnam(sys_get_temp_dir(), 'rpt');
try {
    $mime = ['xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'pdf' => 'application/pdf', 'csv' => 'text/csv; charset=UTF-8'][$format];
    ['xlsx' => 'write_xlsx', 'pdf' => 'write_pdf', 'csv' => 'write_csv'][$format]($report, $file);

    while (ob_get_level()) ob_end_clean();              // nothing printed so far may end up in the file
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $report['slug'] . '.' . $format . '"');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    readfile($file);
} finally {
    @unlink($file);
}
exit;

// ---------- Writers ----------

/** Money as plain text for the PDF: ₱1,500.00 (an empty balance shows as "-"). */
function cell_text($v, $type) {
    if ($v === null || $v === '') return $type === 'money' || $type === 'date' ? '-' : '';
    return match ($type) {
        'money'    => peso($v),
        'int'      => number_format((int)$v),
        'datetime' => date('Y-m-d H:i', strtotime($v)),
        default    => (string)$v,
    };
}

/**
 * CSV: summary first, then each section. UTF-8 with a BOM so Excel shows ₱ and accents correctly.
 * Text that starts with = + - @ (or tab/CR) gets a leading ' so a spreadsheet never runs it as a formula.
 */
function write_csv($r, $file) {
    $safe = fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false ? "'" . $v : $v;
    $plain = function ($v, $type) use ($safe) {
        if ($v === null) return '';
        if ($type === 'money') return number_format((float)$v, 2, '.', '');
        if ($type === 'datetime') return date('Y-m-d H:i', strtotime($v));
        return $type === 'text' ? $safe((string)$v) : $v;
    };
    $out = fopen($file, 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, [$r['shop'] . ' - ' . $r['title']], ',', '"', '');
    fputcsv($out, ['Generated', $r['generated']], ',', '"', '');
    foreach ($r['filters'] as $k => $v) fputcsv($out, [$k, $safe($v)], ',', '"', '');
    if ($r['summary']) {
        fputcsv($out, [], ',', '"', '');
        fputcsv($out, ['Summary'], ',', '"', '');
        foreach ($r['summary'] as [$label, $v, $type]) fputcsv($out, [$label . ($type === 'money' ? ' (PHP)' : ''), $plain($v, $type)], ',', '"', '');
    }
    foreach ($r['sections'] as $s) {
        fputcsv($out, [], ',', '"', '');
        fputcsv($out, [$s['title']], ',', '"', '');
        fputcsv($out, array_map(fn($c) => $c[0] . ($c[1] === 'money' ? ' (PHP)' : ''), $s['columns']), ',', '"', '');
        foreach ($s['rows'] as $row) {
            fputcsv($out, array_map(fn($v, $c) => $plain($v, $c[1]), $row, $s['columns']), ',', '"', '');
        }
        if (!$s['rows']) fputcsv($out, ['No records.'], ',', '"', '');
        elseif (!empty($s['total'])) fputcsv($out, array_map(fn($v, $c) => $plain($v, $c[1]), $s['total'], $s['columns']), ',', '"', '');
    }
    fclose($out);
}

/** Excel: a Summary sheet, then one sheet per section with a bold header row, filters and ₱ number formats. */
function write_xlsx($r, $file) {
    $book = new Spreadsheet();
    $book->getProperties()->setCreator($r['shop'])->setTitle($r['title']);
    $book->removeSheetByIndex(0);
    $peso = '"₱"#,##0.00';
    $head = ['font' => ['bold' => true, 'color' => ['rgb' => '000000']],
             'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFA600']]];

    // Strings are always written as text, so a value like "=SUM(..)" from the database is never a formula
    $put = function ($sheet, $col, $row, $v, $type) use ($peso) {
        $cell = $sheet->getCell([$col, $row]);
        if ($v === null || $v === '') return;
        switch ($type) {
            case 'money': $cell->setValueExplicit((float)$v, DataType::TYPE_NUMERIC); $cell->getStyle()->getNumberFormat()->setFormatCode($peso); break;
            case 'int':   $cell->setValueExplicit((int)$v, DataType::TYPE_NUMERIC); break;
            case 'date':
            case 'datetime':
                $cell->setValueExplicit(ExcelDate::PHPToExcel(new DateTime($v)), DataType::TYPE_NUMERIC);
                $cell->getStyle()->getNumberFormat()->setFormatCode($type === 'date' ? 'yyyy-mm-dd' : 'yyyy-mm-dd hh:mm');
                break;
            default: $cell->setValueExplicit((string)$v, DataType::TYPE_STRING);
        }
    };
    $sheetName = fn($t) => mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', '', $t), 0, 31);

    // ---- Summary sheet ----
    $sh = $book->createSheet();
    $sh->setTitle('Summary');
    $put($sh, 1, 1, $r['shop'] . ' - ' . $r['title'], 'text');
    $sh->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $put($sh, 1, 2, 'Generated', 'text'); $put($sh, 2, 2, $r['generated'], 'text');
    $row = 3;
    foreach ($r['filters'] as $k => $v) { $put($sh, 1, $row, $k, 'text'); $put($sh, 2, $row, $v, 'text'); $row++; }
    $row++;
    foreach ($r['summary'] as [$label, $v, $type]) { $put($sh, 1, $row, $label, 'text'); $put($sh, 2, $row, $v, $type); $row++; }
    $sh->getColumnDimension('A')->setAutoSize(true);
    $sh->getColumnDimension('B')->setAutoSize(true);

    // ---- One sheet per section ----
    foreach ($r['sections'] as $s) {
        $sh = $book->createSheet();
        $sh->setTitle($sheetName($s['title']));
        $last = count($s['columns']);
        foreach ($s['columns'] as $i => [$label]) $put($sh, $i + 1, 1, $label, 'text');
        $sh->getStyle([1, 1, $last, 1])->applyFromArray($head);
        $row = 2;
        foreach ($s['rows'] as $data) {
            foreach ($s['columns'] as $i => [, $type]) $put($sh, $i + 1, $row, $data[$i], $type);
            $row++;
        }
        if (!$s['rows']) { $put($sh, 1, 2, 'No records.', 'text'); $row = 3; }
        elseif (!empty($s['total'])) {
            foreach ($s['columns'] as $i => [, $type]) $put($sh, $i + 1, $row, $s['total'][$i], $i === 0 ? 'text' : $type);
            $sh->getStyle([1, $row, $last, $row])->getFont()->setBold(true);
        }
        if ($s['rows']) $sh->setAutoFilter([1, 1, $last, count($s['rows']) + 1]);
        $sh->freezePane('A2');
        for ($i = 1; $i <= $last; $i++) $sh->getColumnDimensionByColumn($i)->setAutoSize(true);
    }
    $book->setActiveSheetIndex(count($r['sections']) ? 1 : 0);
    (new Xlsx($book))->save($file);
}

/** PDF: printable A4 landscape with the report header, summary and tables. */
function write_pdf($r, $file) {
    $h = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        @page { margin: 18mm 12mm 16mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #222; }
        h1 { font-size: 16pt; margin: 0; color: #d95a00; } h2 { font-size: 11pt; margin: 16px 0 6px; }
        .meta { color: #666; margin: 4px 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #ffa600; color: #000; text-align: left; padding: 5px 6px; font-size: 8.5pt; }
        td { padding: 4px 6px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f6f6f6; }
        .num { text-align: right; white-space: nowrap; }
        .summary { width: auto; margin-bottom: 6px; } .summary td { border: 0; padding: 2px 16px 2px 0; background: none !important; }
        .total td { font-weight: bold; border-top: 2px solid #999; }
        .note, .empty { color: #666; font-style: italic; }
        thead { display: table-header-group; } tr { page-break-inside: avoid; }
    </style></head><body>';
    $h .= '<h1>' . e($r['shop']) . '</h1><h2 style="margin-top:2px">' . e($r['title']) . '</h2>';
    $h .= '<div class="meta">Generated ' . e($r['generated']);
    foreach ($r['filters'] as $k => $v) $h .= ' &middot; ' . e($k) . ': ' . e($v);
    $h .= '</div>';
    if ($r['summary']) {
        $h .= '<table class="summary">';
        foreach ($r['summary'] as [$label, $v, $type]) $h .= '<tr><td>' . e($label) . '</td><td class="num"><b>' . e(cell_text($v, $type)) . '</b></td></tr>';
        $h .= '</table>';
    }
    foreach ($r['sections'] as $s) {
        $h .= '<h2>' . e($s['title']) . '</h2>';
        if (!empty($s['note'])) $h .= '<p class="note">' . e($s['note']) . '</p>';
        if (!$s['rows']) { $h .= '<p class="empty">No records.</p>'; continue; }
        $num = fn($t) => in_array($t, ['money', 'int'], true) ? ' class="num"' : '';
        $h .= '<table><thead><tr>';
        foreach ($s['columns'] as [$label, $type]) $h .= '<th' . $num($type) . '>' . e($label) . '</th>';
        $h .= '</tr></thead><tbody>';
        foreach ($s['rows'] as $row) {
            $h .= '<tr>';
            foreach ($s['columns'] as $i => [, $type]) $h .= '<td' . $num($type) . '>' . e(cell_text($row[$i], $type)) . '</td>';
            $h .= '</tr>';
        }
        if (!empty($s['total'])) {
            $h .= '<tr class="total">';
            foreach ($s['columns'] as $i => [, $type]) $h .= '<td' . $num($type) . '>' . e($s['total'][$i] === null ? '' : cell_text($s['total'][$i], $i ? $type : 'text')) . '</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
    }
    $h .= '</body></html>';

    $opt = new Options();
    $opt->set('defaultFont', 'DejaVu Sans');
    $opt->set('isRemoteEnabled', false);                // never fetch outside URLs while rendering
    $pdf = new Dompdf($opt);
    $pdf->loadHtml($h, 'UTF-8');
    $pdf->setPaper('A4', 'landscape');
    $pdf->render();
    $pdf->getCanvas()->page_text(760, 570, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 8, [0.4, 0.4, 0.4]);
    file_put_contents($file, $pdf->output());
}
