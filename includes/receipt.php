<?php
/**
 * includes/receipt.php
 * Store-slip receipt (80mm) for fully paid orders, used by receipt.php (customer) and
 * admin/receipt.php (admin). Nothing is stored: the receipt is built from the order and its
 * verified payments every time, so it always matches the records.
 */

/**
 * Everything printed on the receipt, or null when there is no receipt
 * (order not found, not the customer's own, cancelled, or not fully paid yet).
 * $customer_id = the logged-in customer (limits it to their own orders); null for admins.
 */
function receipt_data($order_id, $customer_id = null) {
    global $pdo;
    $where = 'AND o.order_id = ?'; $params = [(int)$order_id];
    if ($customer_id !== null) { $where .= ' AND o.customer_id = ?'; $params[] = (int)$customer_id; }
    $rows = fetch_orders($where, $params);
    if (!$rows) return null;
    $o = $rows[0];
    if ($o['status'] === 'cancelled' || $o['pay_state'] !== 'Paid') return null;

    $st = $pdo->prepare('SELECT full_name, email, phone FROM customer WHERE customer_id = ?');
    $st->execute([$o['customer_id']]);
    $cust = $st->fetch();

    $st = $pdo->prepare('SELECT e.name, oi.quantity, oi.unit_price, oi.subtotal FROM order_item oi
                         JOIN equipment e ON e.equipment_id = oi.equipment_id WHERE oi.order_id = ? ORDER BY oi.order_item_id');
    $st->execute([$o['order_id']]);
    $items = $st->fetchAll();

    $st = $pdo->prepare("SELECT payment_method, reference_number, amount, payment_date, created_at FROM payment
                         WHERE order_id = ? AND payment_status = 'paid' ORDER BY payment_id");
    $st->execute([$o['order_id']]);
    $payments = $st->fetchAll();

    // Issued on the day the last payment was confirmed
    $issued = $o['order_date'];
    foreach ($payments as $p) { $d = $p['payment_date'] ?: $p['created_at']; if ($d > $issued) $issued = $d; }

    return [
        'number'   => sprintf('KBK-%s-%06d', date('Y', strtotime($issued)), $o['order_id']),
        'order_id' => (int)$o['order_id'],
        'issued'   => $issued,
        'method'   => ($o['payment_method'] ?? 'gcash') === 'cod' ? 'Cash on Delivery' : 'GCash',
        'customer' => $cust,
        'address'  => $o['shipping_address'],
        'items'    => $items,
        'payments' => $payments,
        'total'    => (float)$o['total_amount'],
        'paid'     => (float)$o['paid_amount'],
        'balance'  => (float)$o['balance'],
        'shop'     => ['name' => setting('shop_name', 'Kabakal Fitness Equipment'), 'address' => setting('shop_address'),
                       'phone' => setting('shop_phone'), 'mobile' => setting('shop_mobile'), 'email' => setting('shop_email')],
    ];
}

/** Payment method label for one payment line. */
function receipt_method_label($m) {
    return ['gcash' => 'GCash', 'cash' => 'Cash', 'bank_transfer' => 'Bank transfer'][$m] ?? ucfirst((string)$m);
}

/** Styles of the slip. Used by the web page (inside <style>) AND by the PDF, so both look the same. */
function receipt_css() {
    return '
.receipt-slip { width: 80mm; box-sizing: border-box; margin: 0 auto; padding: 5mm 4mm 6mm; background: #fff; color: #111;
  font-family: "DejaVu Sans Mono", Consolas, "Courier New", monospace; font-size: 9pt; line-height: 1.35; }
.receipt-slip * { box-sizing: border-box; }
.receipt-slip .r-center { text-align: center; }
.receipt-slip .r-logo { display: block; width: 46mm; height: auto; margin: 0 auto 2mm; }
.receipt-slip .r-shop { font-weight: bold; font-size: 10pt; text-transform: uppercase; }
.receipt-slip .r-small { font-size: 7.5pt; color: #333; }
.receipt-slip .r-title { font-weight: bold; letter-spacing: 1px; margin-top: 1.5mm; }
.receipt-slip .r-cut { border: 0; border-top: 1px dashed #777; margin: 2.5mm 0; height: 0; }
.receipt-slip table { width: 100%; border-collapse: collapse; }
.receipt-slip td { padding: 0.3mm 0; vertical-align: top; border: 0; background: none; }
.receipt-slip .r-right { text-align: right; white-space: nowrap; }
.receipt-slip .r-item { font-weight: bold; padding-top: 1mm; overflow-wrap: anywhere; }
.receipt-slip .r-sub td { color: #333; }
.receipt-slip .r-total td { font-weight: bold; font-size: 10.5pt; padding-top: 1mm; }
.receipt-slip .r-label { color: #444; width: 26mm; }
.receipt-slip .r-paid { display: inline-block; height: 24px; line-height: 20px; padding: 0 12px; border: 2px solid #1a7f37; color: #1a7f37; font-weight: bold; margin-top: 8px; letter-spacing: 1px; vertical-align: middle; }
';
}

/**
 * The slip itself. $for_pdf = true uses a file path for the logo (the PDF maker reads files
 * from disk and is not allowed to fetch web addresses), and the JPEG copy of the logo:
 * Dompdf needs PHP's GD extension for PNG images, which XAMPP / many hosts have switched off.
 */
function receipt_html(array $r, $for_pdf = false) {
    $logo = $for_pdf ? ROOT_PATH . '/assets/images/logo_receipt.jpg' : url('assets/images/logo.png');
    $s = $r['shop'];
    $h  = '<div class="receipt-slip" id="receipt-slip">';
    $h .= '<div class="r-center"><img class="r-logo" src="' . e($logo) . '" alt="">';
    $h .= '<div class="r-shop">' . e($s['name']) . '</div>';
    if ($s['address'] !== '') $h .= '<div class="r-small">' . e($s['address']) . '</div>';
    $contact = array_filter([$s['phone'], $s['mobile']]);
    if ($contact) $h .= '<div class="r-small">' . e(implode(' / ', $contact)) . '</div>';
    if ($s['email'] !== '') $h .= '<div class="r-small">' . e($s['email']) . '</div>';
    $h .= '<div class="r-title">OFFICIAL RECEIPT</div></div>';

    $h .= '<hr class="r-cut"><table>';
    foreach ([['Receipt No.', $r['number']], ['Order No.', '#' . $r['order_id']],
              ['Date', date('M j, Y', strtotime($r['issued']))], ['Payment', $r['method']]] as [$k, $v]) {
        $h .= '<tr><td class="r-label">' . e($k) . '</td><td class="r-right">' . e($v) . '</td></tr>';
    }
    $h .= '</table>';

    $c = $r['customer'];
    $h .= '<hr class="r-cut"><div><b>BILLED TO</b></div>';
    $h .= '<div>' . e($c['full_name']) . '</div>';
    if (!empty($c['phone'])) $h .= '<div class="r-small">' . e($c['phone']) . '</div>';
    if (!empty($c['email'])) $h .= '<div class="r-small">' . e($c['email']) . '</div>';
    $h .= '<div class="r-small">' . e($r['address']) . '</div>';

    $h .= '<hr class="r-cut"><table>';
    foreach ($r['items'] as $it) {
        $h .= '<tr><td colspan="2" class="r-item">' . e($it['name']) . '</td></tr>';
        $h .= '<tr class="r-sub"><td>' . (int)$it['quantity'] . ' x ' . e(number_format((float)$it['unit_price'], 2)) . '</td>'
            . '<td class="r-right">' . e(number_format((float)$it['subtotal'], 2)) . '</td></tr>';
    }
    $h .= '<tr class="r-total"><td>TOTAL</td><td class="r-right">' . e(peso($r['total'])) . '</td></tr></table>';

    $h .= '<hr class="r-cut"><div><b>PAYMENTS RECEIVED</b></div><table>';
    foreach ($r['payments'] as $p) {
        $d = date('M j', strtotime($p['payment_date'] ?: $p['created_at']));
        $h .= '<tr><td>' . e($d . ' ' . receipt_method_label($p['payment_method'])) . '</td><td class="r-right">' . e(peso($p['amount'])) . '</td></tr>';
        if (!empty($p['reference_number'])) $h .= '<tr class="r-sub"><td colspan="2" class="r-small">Ref: ' . e($p['reference_number']) . '</td></tr>';
    }
    $h .= '<tr><td>Amount paid</td><td class="r-right">' . e(peso($r['paid'])) . '</td></tr>';
    $h .= '<tr><td>Balance</td><td class="r-right">' . e(peso($r['balance'])) . '</td></tr></table>';

    $h .= '<hr class="r-cut"><div class="r-center"><div class="r-paid">PAID</div>';
    $h .= '<div style="margin-top:2mm"><b>Thank you! Salamat po!</b></div>';
    $h .= '<div class="r-small">This serves as your official proof of payment.</div></div>';
    return $h . '</div>';
}

/** File name for downloads, e.g. receipt_KBK-2026-000024 */
function receipt_filename(array $r) { return 'receipt_' . $r['number']; }

/**
 * Send the slip as a PDF download. The page is 80mm wide and exactly as tall as the receipt:
 * it is drawn once on a very tall page to measure it, then again at the measured height.
 */
function receipt_send_pdf(array $r) {
    require_once ROOT_PATH . '/vendor/autoload.php';
    ini_set('display_errors', '0');                       // a PHP notice inside the file would corrupt it
    // Dompdf ignores box-sizing, so the slip fills the 80mm page with width:auto instead of width:80mm
    // (otherwise its padding is added on top and the right edge is cut off), and tables get the
    // slip's font size explicitly.
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>@page { margin: 0; } body { margin: 0; }'
          . receipt_css() . '.receipt-slip { width: auto; } .receipt-slip table, .receipt-slip td { font-size: 9pt; }'
          . ' .receipt-slip .r-total td { font-size: 10.5pt; } .receipt-slip td.r-small { font-size: 7.5pt; }'
          . '</style></head><body>' . receipt_html($r, true) . '</body></html>';
    $width = 80 / 25.4 * 72;                               // 80mm in PDF points

    $render = function ($height, &$measured = null) use ($html, $width) {
        $opt = new Dompdf\Options();
        $opt->set('defaultFont', 'DejaVu Sans Mono');
        $opt->set('isRemoteEnabled', false);              // never fetch outside URLs
        $opt->set('chroot', ROOT_PATH);                   // may only read files inside the project
        $pdf = new Dompdf\Dompdf($opt);
        $pdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$measured) {
            if (strtolower($frame->get_node()->nodeName) === 'body') {
                $box = $frame->get_padding_box();
                $measured = max((float)$measured, $box['y'] + $box['h']);
            }
        }]]);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper([0, 0, $width, $height]);
        $pdf->render();
        return $pdf;
    };
    $measured = 0;
    $render(3000, $measured);                             // pass 1: measure
    $pdf = $render(max(200, ceil($measured) + 6));        // pass 2: exact height

    while (ob_get_level()) ob_end_clean();
    $out = $pdf->output();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . receipt_filename($r) . '.pdf"');
    header('Content-Length: ' . strlen($out));
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo $out;
    exit;
}

/** The on-screen receipt with its Download PDF / Download PNG / Print buttons. $pdf_url = link for the PDF. */
function receipt_page(array $r, $pdf_url) {
    $dl = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
    $pr = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>';
    return '<style>' . receipt_css() . '</style>'
         . '<div class="receipt-actions">'
         . '<a class="btn btn-sm" href="' . e($pdf_url) . '">' . $dl . 'Download PDF</a>'
         . '<button type="button" class="btn btn-sm btn-outline" data-receipt-png="' . e(receipt_filename($r)) . '.png">' . $dl . 'Download PNG</button>'
         . '<button type="button" class="btn btn-sm btn-outline" data-print>' . $pr . 'Print</button>'
         . '</div>'
         . '<div class="receipt-wrap">' . receipt_html($r) . '</div>'
         . '<script src="' . e(url('assets/js/html2canvas.min.js')) . '"></script>';
}
