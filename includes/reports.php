<?php
/**
 * includes/reports.php
 * The data behind the admin report views (Dashboard, Orders, Inventory, Customers, Payments).
 * The admin pages AND admin/export.php both call these functions, so a download always holds
 * exactly what the page shows, with the same filters and the same calculations.
 */

// ---------- Filters: only known values are accepted ----------

const ORDER_STATUSES = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
const ORDER_PAY_FILTERS = ['unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'pending' => 'Pending Verification', 'paid' => 'Paid'];
const INVENTORY_VIEWS = ['available' => 'Available', 'reserved' => 'Reserved', 'sold' => 'Sold', 'damaged' => 'Damaged',
                         'out_of_stock' => 'Out of Stock', 'stock_low_high' => 'Low stock to high stock', 'stock_high_low' => 'High stock to low stock'];
const BALANCE_FILTERS = ['unpaid' => 'Unpaid only', 'partial' => 'Partially paid only'];

/** Clean filter values from $_GET: unknown values become '' (= no filter). */
function report_filters(array $in) {
    $pick = fn($k, $allowed) => in_array($in[$k] ?? '', $allowed, true) ? $in[$k] : '';
    return [
        'q'      => mb_substr(trim((string)($in['q'] ?? '')), 0, 100),
        'status' => $pick('status', ORDER_STATUSES),
        'pay'    => $pick('pay', array_keys(ORDER_PAY_FILTERS)),
        'view'   => $pick('view', array_keys(INVENTORY_VIEWS)),
        'bal'    => $pick('bal', array_keys(BALANCE_FILTERS)),
    ];
}

// ---------- Queries (moved here unchanged from the admin pages) ----------

/** Admin > Orders list: search (customer name or order #), order status, payment state. */
function orders_report_rows($q, $status, $pay) {
    $where = ''; $params = [];
    if (in_array($status, ORDER_STATUSES, true)) { $where .= ' AND o.status = ?'; $params[] = $status; }
    if ($q !== '') { $where .= ' AND (c.full_name LIKE ? OR o.order_id = ?)'; $params[] = "%$q%"; $params[] = (int)$q; }
    $orders = fetch_orders($where, $params);
    // Payment filter is done in PHP because pay_state is calculated
    if (isset(ORDER_PAY_FILTERS[$pay])) {
        $orders = array_filter($orders, fn($o) => $o['pay_state'] === ORDER_PAY_FILTERS[$pay] && $o['status'] !== 'cancelled');
    }
    return array_values($orders);
}

/** Admin > Inventory: stock per product in each bucket. $view filters (HAVING) or sorts the list. */
function inventory_report_rows($q, $view) {
    global $pdo;
    $sql = "SELECT e.equipment_id, e.name, c.name AS category_name, e.price, e.stock_quantity AS available, e.last_restocked_at,
              COALESCE(SUM(CASE WHEN i.status = 'reserved' THEN i.quantity END), 0) AS reserved,
              COALESCE(SUM(CASE WHEN i.status = 'sold'     THEN i.quantity END), 0) AS sold,
              COALESCE(SUM(CASE WHEN i.status = 'damaged'  THEN i.quantity END), 0) AS damaged
            FROM equipment e JOIN category c ON c.category_id = e.category_id
            LEFT JOIN inventory i ON i.equipment_id = e.equipment_id
            WHERE e.status <> 'discontinued'";
    $params = [];
    if ($q !== '') { $sql .= ' AND e.name LIKE ?'; $params[] = "%$q%"; }
    $sql .= ' GROUP BY e.equipment_id, e.name, c.name, e.price, e.stock_quantity, e.last_restocked_at';
    $having = ['available' => 'available > 0', 'reserved' => 'reserved > 0', 'sold' => 'sold > 0', 'damaged' => 'damaged > 0', 'out_of_stock' => 'available = 0'];
    if (isset($having[$view])) $sql .= ' HAVING ' . $having[$view];
    // The two "Sort by stock" choices in the filter dropdown order the list instead of filtering it.
    $stock_order = [
        'stock_low_high' => 'available ASC,  c.name, e.name',   // low stock to high stock
        'stock_high_low' => 'available DESC, c.name, e.name',   // high stock to low stock
    ];
    $st = $pdo->prepare($sql . ' ORDER BY ' . ($stock_order[$view] ?? 'c.name, e.name'));
    $st->execute($params);
    return $st->fetchAll();
}

/** Admin > Customers: search by name, email or phone. */
function customers_report_rows($q) {
    global $pdo;
    $sql = 'SELECT c.*, (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.customer_id) AS order_count FROM customer c';
    $params = [];
    if ($q !== '') { $sql .= ' WHERE c.full_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?'; $params = ["%$q%", "%$q%", "%$q%"]; }
    $st = $pdo->prepare($sql . ' ORDER BY c.customer_id DESC'); $st->execute($params);
    return $st->fetchAll();
}

/** Admin > Payments: GCash proofs waiting for verification. */
function pending_payments_rows() {
    global $pdo;
    return $pdo->query("SELECT p.*, c.full_name FROM payment p JOIN orders o ON o.order_id = p.order_id JOIN customer c ON c.customer_id = o.customer_id
                        WHERE p.payment_status = 'pending' ORDER BY p.payment_id")->fetchAll();
}

/** Admin > Payments: orders that still have a balance (not cancelled). $bal = '' | unpaid | partial. */
function balance_report_rows($bal) {
    return array_values(array_filter(fetch_orders("AND o.status <> 'cancelled'"), fn($o) => $o['balance'] > 0
        && ($bal === '' || ($bal === 'unpaid' && $o['pay_state'] === 'Unpaid') || ($bal === 'partial' && $o['pay_state'] === 'Partially Paid'))));
}

/** Dashboard summary cards. */
function dashboard_summary() {
    global $pdo;
    $count = fn($sql) => (int)$pdo->query($sql)->fetchColumn();
    $stats = [
        'Products'             => $count("SELECT COUNT(*) FROM equipment"),
        'Orders'               => $count("SELECT COUNT(*) FROM orders"),
        'Customers'            => $count("SELECT COUNT(*) FROM customer"),
        'Pending verification' => $count("SELECT COUNT(*) FROM payment WHERE payment_status = 'pending'"),
        'Out of stock items'   => $count("SELECT COUNT(*) FROM equipment WHERE stock_quantity = 0 AND status = 'active'"),
    ];
    $sales_total = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payment WHERE payment_status = 'paid'")->fetchColumn();
    $sales_month = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payment WHERE payment_status = 'paid'
                                       AND YEAR(payment_date) = YEAR(CURDATE()) AND MONTH(payment_date) = MONTH(CURDATE())")->fetchColumn();
    $receivables = 0;                                   // money still owed by customers (not cancelled orders)
    foreach (fetch_orders("AND o.status <> 'cancelled'") as $o) { $receivables += $o['balance']; }
    return [$stats, $sales_total, $sales_month, $receivables];
}

/** Dashboard item performance: units sold per product (cancelled orders excluded), split into thirds. */
function item_performance() {
    global $pdo;
    $perf_rows = $pdo->query("SELECT e.equipment_id, e.name,
                                     COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN oi.quantity END), 0) AS units_sold,
                                     COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN oi.subtotal END), 0) AS revenue
                              FROM equipment e
                              LEFT JOIN order_item oi ON oi.equipment_id = e.equipment_id
                              LEFT JOIN orders o      ON o.order_id = oi.order_id
                              WHERE e.status <> 'discontinued'
                              GROUP BY e.equipment_id, e.name
                              ORDER BY units_sold DESC, revenue DESC, e.name")->fetchAll();

    // Products that actually sold something are split into thirds (best first):
    // top third = High Performing, middle third = Moderate Performing, rest = Low Performing.
    // Products with no sales at all always count as Low Performing.
    $sellers = $no_sales = [];
    foreach ($perf_rows as $row) { if ($row['units_sold'] > 0) $sellers[] = $row; else $no_sales[] = $row; }
    $hi_end  = (int)ceil(count($sellers) / 3);
    $mid_end = (int)ceil(count($sellers) * 2 / 3);
    $perf = ['high' => [], 'moderate' => [], 'low' => []];
    foreach ($sellers as $i => $row) {
        if ($i < $hi_end)      $perf['high'][]     = $row;
        elseif ($i < $mid_end) $perf['moderate'][] = $row;
        else                   $perf['low'][]      = $row;
    }
    $perf['low'] = array_merge($perf['low'], $no_sales);
    return $perf;
}

// ---------- Report definitions for the downloads ----------

const REPORT_TYPES = ['sales' => 'Sales Report', 'orders' => 'Orders Report', 'inventory' => 'Inventory Report',
                      'customers' => 'Customers Report', 'payments' => 'Payments Report'];

/**
 * Build one report as plain data for the CSV / Excel / PDF writers:
 *   title, slug (file name), filters [label => value], summary [[label, value, type]],
 *   sections [[title, columns [[label, type]], rows [[...]], total (optional row)]]
 * Column types: text, int, money, date, datetime.
 */
function report_build($type, array $f) {
    $r = ['title' => REPORT_TYPES[$type], 'slug' => $type . '_report', 'filters' => [], 'summary' => [], 'sections' => []];
    $date = fn($v) => $v ? date('Y-m-d', strtotime($v)) : null;

    if ($type === 'sales') {
        [$stats, $sales_total, $sales_month, $receivables] = dashboard_summary();
        foreach ($stats as $label => $n) $r['summary'][] = [$label, $n, 'int'];
        $r['summary'][] = ['Total sales (verified)', $sales_total, 'money'];
        $r['summary'][] = ['Sales this month (' . date('F Y') . ')', $sales_month, 'money'];
        $r['summary'][] = ['Unpaid balances', $receivables, 'money'];
        $rows = [];
        foreach (item_performance() as $group => $items) {
            foreach ($items as $it) $rows[] = [ucfirst($group) . ' Performing', $it['name'], (int)$it['units_sold'], (float)$it['revenue']];
        }
        $r['sections'][] = ['title' => 'Item performance', 'note' => 'Based on units sold. Cancelled orders are not counted.',
            'columns' => [['Performance', 'text'], ['Product', 'text'], ['Units sold', 'int'], ['Revenue', 'money']], 'rows' => $rows];
        $r['slug'] .= '_' . date('Y-m');
    }

    if ($type === 'orders') {
        if ($f['q'] !== '')      $r['filters']['Search'] = $f['q'];
        if ($f['status'] !== '') $r['filters']['Order status'] = ucfirst($f['status']);
        if ($f['pay'] !== '')    $r['filters']['Payment state'] = ORDER_PAY_FILTERS[$f['pay']];
        $rows = [];
        foreach (orders_report_rows($f['q'], $f['status'], $f['pay']) as $o) {
            $rows[] = [(int)$o['order_id'], $date($o['order_date']), $o['customer_name'], (float)$o['total_amount'], (float)$o['paid_amount'],
                       $o['status'] === 'cancelled' ? null : (float)$o['balance'], ucfirst($o['status']), $o['pay_state'],
                       ($o['payment_method'] ?? 'gcash') === 'cod' ? 'Cash on Delivery' : 'GCash'];
        }
        $r['summary'][] = ['Orders', count($rows), 'int'];
        $r['sections'][] = ['title' => 'Orders', 'columns' => [['Order #', 'int'], ['Date', 'date'], ['Customer', 'text'], ['Total', 'money'],
            ['Paid', 'money'], ['Balance', 'money'], ['Order status', 'text'], ['Payment', 'text'], ['Method', 'text']], 'rows' => $rows];
        $r['slug'] .= ($f['status'] ? '_' . $f['status'] : '') . ($f['pay'] ? '_' . $f['pay'] : '');
    }

    if ($type === 'inventory') {
        if ($f['q'] !== '')    $r['filters']['Search'] = $f['q'];
        if ($f['view'] !== '') $r['filters']['View'] = INVENTORY_VIEWS[$f['view']];
        $rows = [];
        foreach (inventory_report_rows($f['q'], $f['view']) as $i) {
            $rows[] = [$i['name'], $i['category_name'], (float)$i['price'], (int)$i['available'], (int)$i['reserved'], (int)$i['sold'],
                       (int)$i['damaged'], $date($i['last_restocked_at']), $i['available'] > 0 ? 'Available' : 'Out of stock'];
        }
        $r['summary'][] = ['Products', count($rows), 'int'];
        $r['summary'][] = ['Units available', array_sum(array_column($rows, 3)), 'int'];
        $r['summary'][] = ['Out of stock products', count(array_filter($rows, fn($x) => $x[3] === 0)), 'int'];
        $r['sections'][] = ['title' => 'Inventory', 'note' => 'Reserved = ordered but not yet paid. Sold = paid, shipped or delivered orders.',
            'columns' => [['Product', 'text'], ['Category', 'text'], ['Price', 'money'], ['Available', 'int'], ['Reserved', 'int'], ['Sold', 'int'],
                          ['Damaged', 'int'], ['Date stock added', 'date'], ['Status', 'text']], 'rows' => $rows];
        $r['slug'] .= $f['view'] ? '_' . $f['view'] : '';
    }

    if ($type === 'customers') {
        if ($f['q'] !== '') $r['filters']['Search'] = $f['q'];
        $rows = [];
        foreach (customers_report_rows($f['q']) as $c) {
            $rows[] = [$c['full_name'], $c['email'], $c['phone'], (int)$c['order_count'], $date($c['created_at'])];
        }
        $r['summary'][] = ['Customers', count($rows), 'int'];
        $r['sections'][] = ['title' => 'Customers', 'columns' => [['Name', 'text'], ['Email', 'text'], ['Phone', 'text'], ['Orders', 'int'], ['Joined', 'date']],
            'rows' => $rows];
    }

    if ($type === 'payments') {
        if ($f['bal'] !== '') $r['filters']['Balances'] = BALANCE_FILTERS[$f['bal']];
        $pending = [];
        foreach (pending_payments_rows() as $p) {
            $pending[] = [(int)$p['order_id'], $p['full_name'], (float)$p['amount'], $p['reference_number'], $p['created_at']];
        }
        $owing = [];
        foreach (balance_report_rows($f['bal']) as $o) {
            $owing[] = [(int)$o['order_id'], $o['customer_name'], (float)$o['total_amount'], (float)$o['paid_amount'], (float)$o['balance'], $o['pay_state']];
        }
        $owing_total = array_sum(array_column($owing, 4));
        $r['summary'][] = ['Waiting for verification', count($pending), 'int'];
        $r['summary'][] = ['Orders with a balance', count($owing), 'int'];
        $r['summary'][] = ['Outstanding balance', $owing_total, 'money'];
        $r['sections'][] = ['title' => 'Waiting for verification', 'columns' => [['Order #', 'int'], ['Customer', 'text'], ['Amount', 'money'],
            ['GCash ref.', 'text'], ['Sent', 'datetime']], 'rows' => $pending];
        $r['sections'][] = ['title' => 'Orders with a balance', 'columns' => [['Order #', 'int'], ['Customer', 'text'], ['Total', 'money'],
            ['Paid', 'money'], ['Balance', 'money'], ['Payment', 'text']], 'rows' => $owing,
            'total' => ['Total', null, null, null, $owing_total, null]];
        $r['slug'] .= $f['bal'] ? '_' . $f['bal'] : '';
    }

    if ($type !== 'sales') $r['slug'] .= '_' . date('Y-m-d');
    return $r;
}

/** Excel / PDF / CSV download buttons for a report, carrying the page's current filters. */
function export_buttons($type, array $filters = []) {
    $params = array_filter($filters, fn($v) => $v !== '' && $v !== null);
    $icon = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
          . '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
    $html = '<div class="export-bar"><span class="muted">Download:</span>';
    foreach (['xlsx' => 'Excel', 'pdf' => 'PDF', 'csv' => 'CSV'] as $fmt => $label) {
        $href = url('admin/export.php?' . http_build_query(['report' => $type, 'format' => $fmt] + $params));
        $html .= '<a class="btn btn-sm btn-outline" href="' . e($href) . '" title="Download ' . e(REPORT_TYPES[$type]) . ' as ' . $label . '">' . $icon . $label . '</a>';
    }
    return $html . '</div>';
}
