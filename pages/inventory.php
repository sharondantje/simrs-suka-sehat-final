<?php
/*
 * MODUL 14 — INVENTORY & GUDANG (database)
 * inventory   : stok barang + batch per lokasi
 * receiving   : penerimaan barang (Informasi Penerimaan → Verifikasi Fisik → Detail Barang)
 * mutation    : permintaan mutasi depo/unit, pengeluaran FEFO (First Expired First Out)
 * stockopname : penghitungan fisik → submit approval (Kepala Gudang) → penyesuaian stok
 * approval    : persetujuan stock opname (Kepala Gudang)
 * audit       : jejak aktivitas gudang (Kepala Gudang)
 */
const INV_MOD = 'Inventory';
$key  = $PAGE['key'];
$me   = (int)$user['id'];
$head = $user['role'] === 'kepala_gudang';
$id   = (int)($_GET['id'] ?? 0);
$back = ui_url($key, $id ? ['id' => $id] : []);

/** Stok gudang barang = jumlah batch di lokasi gudang barang tsb; expiry = batch terdekat. */
function inv_recalc(int $item): void
{
    q('UPDATE inventory_items i SET stock=(SELECT COALESCE(SUM(qty),0) FROM inventory_batches b WHERE b.item_id=i.id AND b.location=i.location),
         expiry_date=(SELECT MIN(expiry_date) FROM inventory_batches b WHERE b.item_id=i.id AND b.location=i.location AND b.qty>0) WHERE i.id=?', [$item]);
}
function inv_batch_add(int $item, string $batch, ?string $exp, string $loc, int $qty): int
{
    q('INSERT INTO inventory_batches (item_id, batch_no, expiry_date, location, qty) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE qty=qty+VALUES(qty), expiry_date=COALESCE(VALUES(expiry_date), expiry_date)',
      [$item, $batch, $exp ?: null, $loc, $qty]);
    return (int)q_val('SELECT id FROM inventory_batches WHERE item_id=? AND batch_no=? AND location=?', [$item, $batch, $loc]);
}
/** Batch FEFO di gudang: urut kedaluwarsa terdekat. */
function inv_fefo(int $item): array
{
    return q_all('SELECT b.* FROM inventory_batches b JOIN inventory_items i ON i.id=b.item_id AND i.location=b.location WHERE b.item_id=? AND b.qty>0
                  ORDER BY b.expiry_date IS NULL, b.expiry_date, b.id', [$item]);
}

/* ------------------------------ AKSI ------------------------------ */
if ($act = ui_post_action()) {
    $f = fn($k) => trim((string)($_POST[$k] ?? '')) ?: null;
    if ($act === 'receive') {
        if (!$f('delivery_note') || !$f('po_number') || !$f('supplier') || !$f('received_date')) { flash('danger', 'Lengkapi informasi penerimaan.'); redirect(ui_url('receiving', ['new' => 1])); }
        if (count((array)($_POST['check'] ?? [])) < 4) { flash('danger', 'Seluruh verifikasi fisik wajib terpenuhi. Barang tidak sesuai harus dikembalikan ke supplier.'); redirect(ui_url('receiving', ['new' => 1])); }
        $rows = [];
        foreach ((array)($_POST['item'] ?? []) as $i => $it) {
            $qty = (int)($_POST['qty'][$i] ?? 0); $batch = trim((string)($_POST['batch'][$i] ?? ''));
            if ((int)$it && $qty > 0 && $batch !== '') $rows[] = [(int)$it, $batch, ($_POST['expiry'][$i] ?? '') ?: null, $qty];
        }
        if (!$rows) { flash('danger', 'Minimal satu barang dengan batch dan jumlah diterima.'); redirect(ui_url('receiving', ['new' => 1])); }
        $no = ui_tx(function () use ($rows, $f, $me) {
            $no = ui_next_no('goods_receipts', 'receipt_no', 'GR-' . date('Y') . '-');
            q('INSERT INTO goods_receipts (receipt_no, delivery_note, po_number, supplier, received_date, checklist, created_by) VALUES (?,?,?,?,?,?,?)',
              [$no, $f('delivery_note'), $f('po_number'), $f('supplier'), $f('received_date'), implode(', ', (array)$_POST['check']), $me]);
            $gr = (int)db()->lastInsertId();
            foreach ($rows as [$item, $batch, $exp, $qty]) {
                $loc = (string)q_val('SELECT location FROM inventory_items WHERE id=?', [$item]);
                q('INSERT INTO goods_receipt_items (receipt_id, item_id, batch_no, expiry_date, qty) VALUES (?,?,?,?,?)', [$gr, $item, $batch, $exp, $qty]);
                inv_batch_add($item, $batch, $exp, $loc, $qty);
                q("INSERT INTO stock_movements (item_id, movement_type, qty, to_location, reference, created_by) VALUES (?, 'masuk', ?, ?, ?, ?)", [$item, $qty, $loc, $no, $me]);
                inv_recalc($item);
            }
            return $no;
        });
        audit('CREATE', INV_MOD, "Penerimaan barang $no (" . $f('po_number') . ')');
        flash('success', "Penerimaan $no berhasil disimpan, stok bertambah.");
        redirect(ui_url('receiving'));
    }
    if ($act === 'request') {
        $rows = [];
        foreach ((array)($_POST['item'] ?? []) as $i => $it) if ((int)$it && (int)($_POST['qty'][$i] ?? 0) > 0) $rows[] = [(int)$it, (int)$_POST['qty'][$i]];
        if (!$f('destination') || !$rows) { flash('danger', 'Isi depo/unit tujuan dan minimal satu barang.'); redirect(ui_url('mutation')); }
        $no = ui_next_no('stock_requisitions', 'req_no', 'REQ-' . date('Y') . '-');
        q("INSERT INTO stock_requisitions (req_no, destination, request_date, status, notes, requested_by) VALUES (?,?,CURDATE(),'menunggu',?,?)", [$no, $f('destination'), $f('notes'), $me]);
        $rid = (int)db()->lastInsertId();
        foreach ($rows as [$it, $qty]) q('INSERT INTO stock_requisition_items (req_id, item_id, qty) VALUES (?,?,?)', [$rid, $it, $qty]);
        audit('CREATE', INV_MOD, "Permintaan mutasi $no");
        flash('success', "Permintaan $no dibuat.");
        redirect(ui_url('mutation', ['id' => $rid]));
    }
    if ($act === 'issue') {
        $req = q_one("SELECT * FROM stock_requisitions WHERE id=? AND status='menunggu'", [$id]);
        if (!$req) redirect(ui_url('mutation'));
        $items = q_all('SELECT ri.*, i.name, i.stock FROM stock_requisition_items ri JOIN inventory_items i ON i.id=ri.item_id WHERE ri.req_id=?', [$id]);
        foreach ($items as $it) if ($it['stock'] < $it['qty']) { flash('danger', "Stok {$it['name']} tidak cukup ({$it['stock']} tersedia)."); redirect($back); }
        ui_tx(function () use ($items, $req, $me) {
            foreach ($items as $it) {
                $need = (int)$it['qty']; $first = null;
                foreach (inv_fefo((int)$it['item_id']) as $b) {
                    if ($need <= 0) break;
                    $take = min($need, (int)$b['qty']); $need -= $take; $first = $first ?? (int)$b['id'];
                    q('UPDATE inventory_batches SET qty=qty-? WHERE id=?', [$take, $b['id']]);
                    inv_batch_add((int)$it['item_id'], $b['batch_no'], $b['expiry_date'], $req['destination'], $take);
                    q("INSERT INTO stock_movements (item_id, movement_type, qty, from_location, to_location, reference, created_by) VALUES (?, 'mutasi', ?, ?, ?, ?, ?)",
                      [$it['item_id'], $take, $b['location'], $req['destination'], $req['req_no'] . ' / ' . $b['batch_no'], $me]);
                }
                q('UPDATE stock_requisition_items SET batch_id=?, issued_qty=? WHERE id=?', [$first, $it['qty'], $it['id']]);
                inv_recalc((int)$it['item_id']);
            }
            q("UPDATE stock_requisitions SET status='dikirim', processed_by=?, processed_at=NOW() WHERE id=?", [$me, $req['id']]);
        });
        audit('UPDATE', INV_MOD, 'Pengeluaran FEFO ' . $req['req_no'] . ' ke ' . $req['destination']);
        flash('success', 'Pengeluaran dikonfirmasi dengan metode FEFO.');
        redirect($back);
    }
    if ($act === 'req_reject') {
        q("UPDATE stock_requisitions SET status='ditolak', processed_by=?, processed_at=NOW(), notes=CONCAT_WS(' | Ditolak: ', notes, ?) WHERE id=? AND status='menunggu'", [$me, $f('reason') ?? '-', $id]);
        audit('REJECT', INV_MOD, "Tolak permintaan mutasi #$id");
        flash('warning', 'Permintaan ditolak.');
        redirect(ui_url('mutation'));
    }
    if ($act === 'opname_new') {
        $loc = $f('location');
        if (!$loc) redirect(ui_url('stockopname'));
        $no = ui_next_no('stock_opnames', 'opname_no', 'SO-' . date('Y') . '-', 3);
        q("INSERT INTO stock_opnames (opname_no, location, period, count_date, status, created_by) VALUES (?,?,?,?, 'draft', ?)", [$no, $loc, date('Y-m'), $f('count_date') ?? date('Y-m-d'), $me]);
        $oid = (int)db()->lastInsertId();
        foreach (q_all('SELECT * FROM inventory_batches WHERE location=? ORDER BY item_id, expiry_date', [$loc]) as $b)
            q('INSERT INTO stock_opname_items (opname_id, item_id, batch_id, system_qty, physical_qty) VALUES (?,?,?,?,?)', [$oid, $b['item_id'], $b['id'], $b['qty'], $b['qty']]);
        audit('CREATE', INV_MOD, "Mulai stock opname $no di $loc");
        redirect(ui_url('stockopname', ['id' => $oid]));
    }
    if ($act === 'opname_save') {
        $op = q_one("SELECT * FROM stock_opnames WHERE id=? AND status IN ('draft','ditolak')", [$id]);
        if (!$op) redirect(ui_url('stockopname'));
        $diff = 0;
        foreach ((array)($_POST['physical'] ?? []) as $sid => $val) {
            $val = max(0, (int)$val);
            q('UPDATE stock_opname_items SET physical_qty=? WHERE id=? AND opname_id=?', [$val, (int)$sid, $id]);
        }
        $diff = (int)q_val('SELECT COUNT(*) FROM stock_opname_items WHERE opname_id=? AND physical_qty<>system_qty', [$id]);
        $submit = ($_POST['mode'] ?? '') === 'submit';
        if ($submit && $diff && !$f('reason')) { flash('danger', 'Ada selisih stok: alasan selisih wajib diisi sebelum submit.'); redirect($back); }
        q('UPDATE stock_opnames SET reason=?, notes=?, status=? WHERE id=?', [$f('reason'), $f('notes'), $submit ? 'menunggu_approval' : 'draft', $id]);
        audit($submit ? 'SUBMIT' : 'UPDATE', INV_MOD, ($submit ? 'Submit approval ' : 'Simpan draft ') . $op['opname_no']);
        flash('success', $submit ? 'Stock opname dikirim ke Kepala Gudang untuk approval.' : 'Draft stock opname tersimpan.');
        redirect($back);
    }
    if (($act === 'approve' || $act === 'reject') && $head) {
        $op = q_one("SELECT * FROM stock_opnames WHERE id=? AND status='menunggu_approval'", [$id]);
        if ($op) {
            if ($act === 'reject' && !$f('reason')) { flash('danger', 'Alasan penolakan wajib diisi.'); redirect($back); }
            ui_tx(function () use ($op, $act, $me, $f) {
                if ($act === 'approve') {
                    foreach (q_all('SELECT * FROM stock_opname_items WHERE opname_id=? AND physical_qty<>system_qty', [$op['id']]) as $it) {
                        $d = $it['physical_qty'] - $it['system_qty'];
                        if ($it['batch_id']) q('UPDATE inventory_batches SET qty=? WHERE id=?', [$it['physical_qty'], $it['batch_id']]);
                        q("INSERT INTO stock_movements (item_id, movement_type, qty, from_location, reference, created_by) VALUES (?, 'penyesuaian', ?, ?, ?, ?)", [$it['item_id'], $d, $op['location'], $op['opname_no'], $me]);
                        inv_recalc((int)$it['item_id']);
                    }
                    q("UPDATE stock_opnames SET status='disetujui', approved_by=?, approved_at=NOW() WHERE id=?", [$me, $op['id']]);
                } else q("UPDATE stock_opnames SET status='ditolak', approved_by=?, approved_at=NOW(), notes=CONCAT_WS(' | Ditolak: ', notes, ?) WHERE id=?", [$me, $f('reason'), $op['id']]);
            });
            audit($act === 'approve' ? 'APPROVE' : 'REJECT', INV_MOD, ($act === 'approve' ? 'Setujui ' : 'Tolak ') . 'stock opname ' . $op['opname_no']);
            flash($act === 'approve' ? 'success' : 'warning', $act === 'approve' ? 'Stock opname disetujui, stok disesuaikan.' : 'Stock opname ditolak dan dikembalikan ke petugas.');
        }
        redirect(ui_url('approval'));
    }
    redirect($back);
}

/* ------------------------------ DATA ------------------------------ */
$items = q_all('SELECT * FROM inventory_items ORDER BY name');
$locations = array_column(q_all('SELECT DISTINCT location FROM inventory_batches UNION SELECT DISTINCT location FROM inventory_items ORDER BY 1'), 'location');
$titles = ['inventory' => ['Inventory', 'Stok barang medis & non-medis beserta batch dan lokasi.'], 'receiving' => ['Penerimaan Barang', 'Pencatatan barang masuk dari supplier berdasarkan PO & surat jalan.'],
           'mutation' => ['Mutasi Barang', 'Permintaan depo/unit dan pengeluaran barang dengan metode FEFO.'], 'stockopname' => ['Stock Opname', 'Penghitungan fisik stok per lokasi dan pengajuan penyesuaian.'],
           'approval' => ['Approval Stock Opname', 'Persetujuan penyesuaian stok hasil opname.'], 'audit' => ['Audit Log Gudang', 'Jejak aktivitas dan pergerakan stok gudang.']];
$soon = date('Y-m-d', strtotime('+90 days'));
$itemOpts = ui_options($items, 'id', fn($i) => $i['code'] . ' — ' . $i['name'] . ' (' . $i['unit'] . ')', null, '— pilih barang —');

layout_start($PAGE['title'], $key, $PAGE['section']);
?>
<div class="content">
  <div class="page-head"><div><h2><?= e($titles[$key][0]) ?></h2><p><?= e($titles[$key][1]) ?></p></div>
    <div class="page-actions"><?php if ($id || isset($_GET['new'])): ?><a class="btn" href="<?= e(ui_url($key)) ?>">← Kembali</a><?php endif; ?>
      <?php if ($key === 'receiving' && !isset($_GET['new'])): ?><a class="btn btn-primary" href="<?= e(ui_url('receiving', ['new' => 1])) ?>"><?= icon('plus', 16) ?> Penerimaan</a><?php endif; ?>
      <?php if ($key === 'mutation' && !$id): ?><button class="btn btn-primary" data-modal="mReq"><?= icon('plus', 16) ?> Permintaan Mutasi</button><?php endif; ?>
      <?php if ($key === 'stockopname' && !$id): ?><button class="btn btn-primary" data-modal="mSO"><?= icon('plus', 16) ?> Stock Opname Baru</button><?php endif; ?></div></div>
  <?php flashes(); ?>

<?php if ($key === 'inventory'):
  if ($id && ($it = q_one('SELECT * FROM inventory_items WHERE id=?', [$id]))):
    $batches = q_all('SELECT * FROM inventory_batches WHERE item_id=? ORDER BY location, expiry_date', [$id]);
    $moves = q_all('SELECT m.*, u.full_name FROM stock_movements m LEFT JOIN users u ON u.id=m.created_by WHERE m.item_id=? ORDER BY m.created_at DESC LIMIT 15', [$id]); ?>
  <section class="grid grid-2">
    <div class="card"><div class="card-head"><div><h3><?= e($it['name']) ?></h3><p class="mono"><?= e($it['code']) ?></p></div></div>
      <?= ui_details(['Kategori' => e(ui_label($it['category'])), 'Satuan' => e($it['unit']), 'Stok Gudang' => '<b class="mono">' . angka($it['stock']) . '</b>', 'Stok Minimum' => '<span class="mono">' . angka($it['min_stock']) . '</span>', 'Lokasi Gudang' => e($it['location']), 'Harga' => rupiah($it['price'])]) ?>
      <div class="table-wrap"><table class="table"><thead><tr><th>Batch</th><th>Lokasi</th><th>Expiry</th><th>Qty</th></tr></thead><tbody>
      <?php foreach ($batches as $b): ?><tr><td class="mono"><?= e($b['batch_no']) ?></td><td><?= e($b['location']) ?></td><td class="mono" style="<?= $b['expiry_date'] && $b['expiry_date'] <= $soon ? 'color:var(--danger)' : '' ?>"><?= tgl($b['expiry_date']) ?></td><td class="mono strong"><?= angka($b['qty']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    <div class="card"><div class="card-head"><h3>Pergerakan Stok</h3></div><div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Jenis</th><th>Qty</th><th>Dari → Ke</th><th>Ref</th></tr></thead><tbody>
      <?php if (!$moves): ?><tr><td colspan="5" class="empty">Belum ada pergerakan.</td></tr><?php endif; foreach ($moves as $m): ?><tr><td class="mono"><?= tgl($m['created_at'], true) ?></td><td><?= status_badge($m['movement_type']) ?></td><td class="mono"><?= (int)$m['qty'] ?></td><td><?= e(($m['from_location'] ?? '-') . ' → ' . ($m['to_location'] ?? '-')) ?></td><td class="mono"><?= e($m['reference']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
  </section>
  <?php else:
    $fc = $_GET['cat'] ?? ''; $fs = $_GET['f'] ?? ''; $q = trim((string)($_GET['q'] ?? ''));
    $view = array_filter($items, fn($i) => ($fc === '' || $i['category'] === $fc) && ($q === '' || stripos($i['name'] . $i['code'], $q) !== false)
        && ($fs === '' || ($fs === 'low' && $i['stock'] <= $i['min_stock']) || ($fs === 'ed' && $i['expiry_date'] && $i['expiry_date'] <= $soon))); ?>
  <section class="kpi-grid">
    <?= ui_kpi('Jenis Barang', count($items), 'box') ?>
    <?= ui_kpi('Stok Menipis', count(array_filter($items, fn($i) => $i['stock'] <= $i['min_stock'])), 'alert', 'warning', '≤ stok minimum') ?>
    <?= ui_kpi('Expiry ≤ 90 Hari', count(array_filter($items, fn($i) => $i['expiry_date'] && $i['expiry_date'] <= $soon)), 'clock', 'danger') ?>
    <?= ui_kpi('Nilai Persediaan', '<span style="font-size:20px">' . rupiah(array_sum(array_map(fn($i) => $i['stock'] * $i['price'], $items))) . '</span>', 'wallet', 'info') ?>
  </section>
  <div class="card"><form class="toolbar" method="get"><input type="hidden" name="page" value="inventory"><b style="color:var(--heading)">Stok Terbaru</b><span class="grow"></span>
      <select class="input" name="cat" onchange="this.form.submit()"><option value="">Semua kategori</option><?= ui_opt_map(['medis' => 'Medis', 'alkes' => 'Alkes', 'obat' => 'Obat', 'nonmedis' => 'Non-medis'], $fc) ?></select>
      <select class="input" name="f" onchange="this.form.submit()"><?= ui_opt_map(['' => 'Semua status', 'low' => 'Stok menipis', 'ed' => 'Segera kedaluwarsa'], $fs) ?></select>
      <input class="input" name="q" value="<?= e($q) ?>" placeholder="Cari barang"><button class="btn"><?= icon('search', 15) ?></button></form>
    <div class="table-wrap"><table class="table"><thead><tr><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Lokasi</th><th>Stok</th><th>Min.</th><th>Expiry Terdekat</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($view as $i): $st = $i['stock'] <= 0 ? ['danger', 'Habis'] : ($i['stock'] <= $i['min_stock'] ? ['warning', 'Menipis'] : ($i['expiry_date'] && $i['expiry_date'] <= $soon ? ['warning', 'Segera ED'] : ['success', 'Aman'])); ?>
      <tr><td class="mono"><?= e($i['code']) ?></td><td class="strong"><?= e($i['name']) ?></td><td><?= e(ui_label($i['category'])) ?></td><td><?= e($i['location']) ?></td>
        <td class="mono strong"><?= angka($i['stock']) ?> <small class="muted"><?= e($i['unit']) ?></small></td><td class="mono"><?= angka($i['min_stock']) ?></td><td class="mono"><?= tgl($i['expiry_date']) ?></td>
        <td><span class="badge <?= $st[0] ?>"><?= $st[1] ?></span></td><td><a class="btn btn-sm" href="<?= e(ui_url('inventory', ['id' => $i['id']])) ?>">Detail</a></td></tr><?php endforeach; ?></tbody></table></div></div>
  <?php endif; ?>

<?php elseif ($key === 'receiving'):
  if (isset($_GET['new'])): ?>
  <form method="post" class="card" data-wizard><?= csrf_field() ?><input type="hidden" name="act" value="receive">
    <div class="stepper-card wz-steps" style="margin:0"><?= ui_stepper(['Informasi Penerimaan', 'Verifikasi Fisik', 'Detail Barang'], 0) ?></div>
    <div class="card-body wz-panel"><div class="form-grid">
      <div class="field"><label>Nomor Surat Jalan / Delivery Note</label><input name="delivery_note" required></div>
      <div class="field"><label>Purchase Order (PO)</label><input name="po_number" required placeholder="PO-<?= date('Y') ?>-xxx"></div>
      <div class="field"><label>Supplier</label><input name="supplier" required list="supL"><datalist id="supL"><?php foreach (q_all('SELECT DISTINCT supplier FROM goods_receipts') as $s): ?><option value="<?= e($s['supplier']) ?>"><?php endforeach; ?></datalist></div>
      <div class="field"><label>Tanggal Penerimaan</label><input type="date" name="received_date" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div></div></div>
    <div class="card-body wz-panel"><p class="muted" style="margin-top:0">Cocokkan barang fisik dengan surat jalan & PO. Semua poin wajib sesuai.</p>
      <div class="checks"><?php foreach (['Kuantitas sesuai', 'Kemasan baik', 'Batch number sesuai', 'Expiry date sesuai'] as $c): ?><label class="check"><input type="checkbox" name="check[]" value="<?= e($c) ?>" required> <?= e($c) ?></label><?php endforeach; ?></div></div>
    <div class="card-body wz-panel"><div class="table-wrap"><table class="table"><thead><tr><th>Barang</th><th>Batch Number</th><th>Expiry Date</th><th>Jumlah Diterima</th></tr></thead><tbody>
      <?php for ($r = 0; $r < 4; $r++): ?><tr><td><select class="input" name="item[]" style="min-width:220px" <?= $r === 0 ? 'required' : '' ?>><?= $itemOpts ?></select></td><td><input class="input" name="batch[]" <?= $r === 0 ? 'required' : '' ?> style="min-width:110px"></td>
        <td><input class="input" type="date" name="expiry[]" min="<?= date('Y-m-d') ?>"></td><td><input class="input" type="number" name="qty[]" min="1" <?= $r === 0 ? 'required' : '' ?> style="min-width:90px"></td></tr><?php endfor; ?></tbody></table></div></div>
    <div class="card-foot"><button type="button" class="btn wz-prev">← Sebelumnya</button><button type="button" class="btn btn-primary wz-next">Lanjut →</button><button class="btn btn-primary wz-submit"><?= icon('check', 16) ?> Simpan Penerimaan</button></div></form>
  <?php else:
    $grs = q_all('SELECT g.*, u.full_name, (SELECT COUNT(*) FROM goods_receipt_items WHERE receipt_id=g.id) n, (SELECT SUM(qty) FROM goods_receipt_items WHERE receipt_id=g.id) q FROM goods_receipts g LEFT JOIN users u ON u.id=g.created_by ORDER BY g.received_date DESC, g.id DESC'); ?>
  <div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>No. Penerimaan</th><th>Tanggal</th><th>Surat Jalan</th><th>PO</th><th>Supplier</th><th>Item</th><th>Total Qty</th><th>Petugas</th></tr></thead><tbody>
    <?php if (!$grs): ?><tr><td colspan="8" class="empty">Belum ada penerimaan.</td></tr><?php endif; foreach ($grs as $g): ?><tr><td class="mono strong"><?= e($g['receipt_no']) ?></td><td class="mono"><?= tgl($g['received_date']) ?></td><td class="mono"><?= e($g['delivery_note']) ?></td><td class="mono"><?= e($g['po_number']) ?></td>
      <td><?= e($g['supplier']) ?></td><td class="mono"><?= (int)$g['n'] ?></td><td class="mono"><?= angka($g['q']) ?></td><td><?= e($g['full_name'] ?? '-') ?></td></tr><?php endforeach; ?></tbody></table></div></div>
  <?php endif; ?>

<?php elseif ($key === 'mutation'):
  if ($id && ($req = q_one('SELECT r.*, u.full_name rq, p.full_name pr FROM stock_requisitions r LEFT JOIN users u ON u.id=r.requested_by LEFT JOIN users p ON p.id=r.processed_by WHERE r.id=?', [$id]))):
    $ri = q_all('SELECT ri.*, i.name, i.code, i.unit, i.stock, b.batch_no FROM stock_requisition_items ri JOIN inventory_items i ON i.id=ri.item_id LEFT JOIN inventory_batches b ON b.id=ri.batch_id WHERE ri.req_id=?', [$id]); ?>
  <div class="card stepper-card"><?= ui_stepper(['Permintaan Diterima', 'Rekomendasi FEFO', 'Konfirmasi Pengeluaran', 'Terkirim'], $req['status'] === 'dikirim' ? 4 : ($req['status'] === 'ditolak' ? 0 : 1)) ?></div>
  <section class="grid grid-2">
    <div class="card"><div class="card-head"><div><h3>Permintaan <span class="mono"><?= e($req['req_no']) ?></span></h3><p><?= e($req['destination']) ?> · <?= tgl($req['request_date']) ?></p></div><?= status_badge($req['status']) ?></div>
      <?= ui_details(['Depo / Unit Tujuan' => e($req['destination']), 'Diajukan Oleh' => e($req['rq'] ?? 'Unit pelayanan'), 'Catatan' => e($req['notes'] ?? '-'), 'Diproses' => e($req['pr'] ?? '-') . ' ' . tgl($req['processed_at'], true)]) ?>
      <div class="table-wrap"><table class="table"><thead><tr><th>Barang</th><th>Qty Diminta</th><th>Stok Gudang</th><th>Dikeluarkan</th></tr></thead><tbody>
      <?php foreach ($ri as $r): ?><tr><td><b><?= e($r['name']) ?></b><br><small class="mono muted"><?= e($r['code']) ?></small></td><td class="mono"><?= (int)$r['qty'] ?> <?= e($r['unit']) ?></td>
        <td class="mono" style="<?= $r['stock'] < $r['qty'] ? 'color:var(--danger)' : '' ?>"><?= angka($r['stock']) ?></td><td class="mono"><?= $r['issued_qty'] ? (int)$r['issued_qty'] . ' · ' . e($r['batch_no']) : '-' ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    <?php if ($req['status'] === 'menunggu'): ?>
    <div class="card"><div class="card-head"><div><h3>⏳ Rekomendasi FEFO</h3><p>Batch dengan kedaluwarsa terdekat dikeluarkan lebih dulu.</p></div></div>
      <div class="table-wrap"><table class="table"><thead><tr><th>Barang</th><th>Batch Direkomendasikan</th><th>Expiry</th><th>Ambil</th></tr></thead><tbody>
      <?php foreach ($ri as $r): $need = (int)$r['qty']; foreach (inv_fefo((int)$r['item_id']) as $b): if ($need <= 0) break; $take = min($need, (int)$b['qty']); $need -= $take; ?>
        <tr><td><?= e($r['name']) ?></td><td class="mono"><?= e($b['batch_no']) ?></td><td class="mono"><?= tgl($b['expiry_date']) ?></td><td class="mono strong"><?= $take ?></td></tr><?php endforeach; endforeach; ?></tbody></table></div>
      <form method="post" class="card-foot"><?= csrf_field() ?><input type="hidden" name="act" value="issue"><button class="btn btn-primary"><?= icon('truck', 16) ?> Konfirmasi Pengeluaran</button></form>
      <form method="post" class="card-foot" style="justify-content:flex-start" data-confirm="Permintaan mutasi akan ditolak." data-confirm-title="Tolak Permintaan" data-confirm-ok="Tolak"><?= csrf_field() ?><input type="hidden" name="act" value="req_reject"><input class="input" name="reason" required placeholder="Alasan penolakan" style="flex:1;height:38px"><button class="btn btn-danger">Tolak</button></form></div>
    <?php endif; ?>
  </section>
  <?php else:
    $reqs = q_all('SELECT r.*, (SELECT COUNT(*) FROM stock_requisition_items WHERE req_id=r.id) n FROM stock_requisitions r ORDER BY (r.status="menunggu") DESC, r.request_date DESC'); ?>
  <div class="card"><div class="card-head"><div><h3>🔄 Permintaan Mutasi</h3><p>Permintaan barang dari depo farmasi, bangsal, dan unit pelayanan.</p></div></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Nomor Requisition</th><th>Tanggal Permintaan</th><th>Depo / Unit</th><th>Item</th><th>Catatan</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($reqs as $r): ?><tr><td class="mono strong"><?= e($r['req_no']) ?></td><td class="mono"><?= tgl($r['request_date']) ?></td><td><?= e($r['destination']) ?></td><td class="mono"><?= (int)$r['n'] ?></td><td><?= e($r['notes'] ?? '-') ?></td><td><?= status_badge($r['status']) ?></td>
      <td><a class="btn btn-sm <?= $r['status'] === 'menunggu' ? 'btn-primary' : '' ?>" href="<?= e(ui_url('mutation', ['id' => $r['id']])) ?>"><?= $r['status'] === 'menunggu' ? 'Proses' : 'Lihat' ?></a></td></tr><?php endforeach; ?></tbody></table></div></div>
  <div class="modal" id="mReq"><div class="modal-box"><div class="card-head"><h3>Permintaan Mutasi</h3><button class="icon-btn" data-close type="button">✕</button></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="request">
      <div class="field"><label>Depo / Unit Tujuan</label><input name="destination" required list="locL"><datalist id="locL"><?php foreach (array_merge($locations, ['IGD', 'Bangsal Melati', 'Bangsal Mawar', 'Depo Farmasi Rawat Inap', 'ICU', 'Poli Rawat Jalan']) as $l): ?><option value="<?= e($l) ?>"><?php endforeach; ?></datalist></div>
      <?php for ($r = 0; $r < 3; $r++): ?><div class="form-grid"><div class="field"><label>Barang <?= $r + 1 ?></label><select name="item[]" <?= $r === 0 ? 'required' : '' ?>><?= $itemOpts ?></select></div><div class="field"><label>Jumlah</label><input type="number" name="qty[]" min="1" <?= $r === 0 ? 'required' : '' ?>></div></div><?php endfor; ?>
      <div class="field"><label>Catatan</label><input name="notes"></div>
      <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Ajukan</button></div></form></div></div>
  <?php endif; ?>

<?php elseif ($key === 'stockopname' || $key === 'approval'):
  if ($id && ($op = q_one('SELECT o.*, u.full_name creator, a.full_name approver FROM stock_opnames o LEFT JOIN users u ON u.id=o.created_by LEFT JOIN users a ON a.id=o.approved_by WHERE o.id=?', [$id]))):
    $lines = q_all('SELECT s.*, i.code, i.name, i.unit, i.price, b.batch_no, b.expiry_date FROM stock_opname_items s JOIN inventory_items i ON i.id=s.item_id LEFT JOIN inventory_batches b ON b.id=s.batch_id WHERE s.opname_id=? ORDER BY i.name', [$id]);
    $editable = $key === 'stockopname' && in_array($op['status'], ['draft', 'ditolak'], true);
    $stage = ['draft' => 0, 'ditolak' => 0, 'menunggu_approval' => 2, 'disetujui' => 4][$op['status']]; ?>
  <div class="card stepper-card"><?= ui_stepper(['Penghitungan Fisik', 'Submit', 'Approval Kepala Gudang', 'Stok Disesuaikan'], $stage) ?></div>
  <?php if ($op['status'] === 'ditolak'): ?><div class="alert danger"><?= icon('x-circle', 18) ?><div>Ditolak Kepala Gudang: <?= e($op['notes'] ?? '-') ?>. Perbaiki lalu submit ulang.</div></div><?php endif; ?>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="<?= $editable ? 'opname_save' : 'approve' ?>">
    <div class="card-head"><div><h3>📋 Stok Opname <span class="mono"><?= e($op['opname_no']) ?></span></h3><p><?= e($op['location']) ?> · Periode <?= e($op['period']) ?> · dihitung <?= tgl($op['count_date']) ?> oleh <?= e($op['creator'] ?? '-') ?></p></div><?= status_badge($op['status']) ?></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Barang</th><th>Batch</th><th>Expiry</th><th>Stok Sistem</th><th>Stok Fisik</th><th>Selisih</th><th>Nilai Selisih</th></tr></thead><tbody>
    <?php $tv = 0; foreach ($lines as $l): $d = $l['physical_qty'] - $l['system_qty']; $tv += $d * $l['price']; ?>
      <tr><td><b><?= e($l['name']) ?></b><br><small class="mono muted"><?= e($l['code']) ?></small></td><td class="mono"><?= e($l['batch_no'] ?? '-') ?></td><td class="mono"><?= tgl($l['expiry_date']) ?></td><td class="mono"><?= angka($l['system_qty']) ?></td>
        <td><?= $editable ? '<input class="input" type="number" min="0" name="physical[' . $l['id'] . ']" value="' . (int)$l['physical_qty'] . '" style="width:100px;height:34px">' : '<span class="mono">' . angka($l['physical_qty']) . '</span>' ?></td>
        <td class="mono strong" style="color:<?= $d < 0 ? 'var(--danger)' : ($d > 0 ? 'var(--success)' : 'inherit') ?>"><?= $d > 0 ? '+' : '' ?><?= $d ?></td><td class="mono"><?= rupiah($d * $l['price']) ?></td></tr><?php endforeach; ?></tbody>
      <tfoot><tr><td colspan="6" style="text-align:right"><b>Total nilai selisih</b></td><td class="mono strong"><?= rupiah($tv) ?></td></tr></tfoot></table></div>
    <div class="card-body"><div class="form-grid">
      <div class="field"><label>Alasan Selisih</label><?php if ($editable): ?><select name="reason"><option value="">— tidak ada selisih —</option><?= ui_opt_map(array_combine($k = ['Barang rusak / pecah', 'Barang kedaluwarsa', 'Salah input transaksi', 'Barang hilang', 'Lainnya'], $k), $op['reason']) ?></select><?php else: ?><div><?= e($op['reason'] ?? '-') ?></div><?php endif; ?></div>
      <div class="field"><label>Catatan</label><?php if ($editable): ?><input name="notes" value="<?= e($op['notes']) ?>"><?php else: ?><div><?= e($op['notes'] ?? '-') ?></div><?php endif; ?></div>
      <?php if ($key === 'approval' && $op['status'] === 'menunggu_approval'): ?><div class="field full"><label>Catatan / Alasan Penolakan</label><input name="reason" placeholder="Wajib diisi bila ditolak"></div><?php endif; ?></div></div>
    <?php if ($editable): ?><div class="card-foot"><button class="btn btn-outline" name="mode" value="draft">Simpan Draft</button><button class="btn btn-primary" name="mode" value="submit">Submit untuk Approval</button></div>
    <?php elseif ($key === 'approval' && $op['status'] === 'menunggu_approval' && $head): ?><div class="card-foot"><button class="btn btn-danger" name="act" value="reject">Tolak</button><button class="btn btn-primary" name="act" value="approve"><?= icon('check', 16) ?> Approve & Sesuaikan Stok</button></div>
    <?php elseif ($op['status'] === 'disetujui'): ?><div class="card-foot"><span class="muted">Disetujui <?= e($op['approver'] ?? '-') ?> · <?= tgl($op['approved_at'], true) ?></span></div><?php endif; ?>
  </form>
  <?php else:
    $ops = q_all('SELECT o.*, u.full_name, (SELECT COUNT(*) FROM stock_opname_items WHERE opname_id=o.id AND physical_qty<>system_qty) diff FROM stock_opnames o LEFT JOIN users u ON u.id=o.created_by '
                 . ($key === 'approval' ? "ORDER BY (o.status='menunggu_approval') DESC, o.created_at DESC" : 'ORDER BY o.created_at DESC'));
    $pending = count(array_filter($ops, fn($o) => $o['status'] === 'menunggu_approval')); ?>
  <?php if ($key === 'approval'): ?><div class="alert <?= $pending ? 'warning' : 'success' ?>"><?= icon('clock', 18) ?><div>⏳ Menunggu Persetujuan (<?= $pending ?>)</div></div><?php endif; ?>
  <div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>No. Opname</th><th>Lokasi</th><th>Periode</th><th>Tanggal Hitung</th><th>Item Selisih</th><th>Alasan</th><th>Diajukan Oleh</th><th>Status</th><th></th></tr></thead><tbody>
    <?php if (!$ops): ?><tr><td colspan="9" class="empty">Belum ada stock opname.</td></tr><?php endif; foreach ($ops as $o): ?><tr><td class="mono strong"><?= e($o['opname_no']) ?></td><td><?= e($o['location']) ?></td><td class="mono"><?= e($o['period']) ?></td><td class="mono"><?= tgl($o['count_date']) ?></td>
      <td class="mono"><?= (int)$o['diff'] ?></td><td><?= e($o['reason'] ?? '-') ?></td><td><?= e($o['full_name'] ?? '-') ?></td><td><?= status_badge($o['status']) ?></td>
      <td><a class="btn btn-sm <?= ($key === 'approval' && $o['status'] === 'menunggu_approval') || ($key === 'stockopname' && in_array($o['status'], ['draft', 'ditolak'], true)) ? 'btn-primary' : '' ?>" href="<?= e(ui_url($key, ['id' => $o['id']])) ?>"><?= $key === 'approval' && $o['status'] === 'menunggu_approval' ? 'Tinjau' : 'Buka' ?></a></td></tr><?php endforeach; ?></tbody></table></div></div>
  <?php if ($key === 'stockopname'): ?><div class="modal" id="mSO"><div class="modal-box"><div class="card-head"><h3>Stock Opname Baru</h3><button class="icon-btn" data-close type="button">✕</button></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="opname_new"><div class="form-grid">
      <div class="field"><label>Lokasi</label><select name="location" required><?= ui_opt_map(array_combine($locations, $locations)) ?></select></div>
      <div class="field"><label>Tanggal Penghitungan</label><input type="date" name="count_date" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div></div>
      <p class="field-hint">Seluruh batch di lokasi tersebut dimuat dengan stok sistem saat ini.</p>
      <div class="modal-foot"><button type="button" class="btn" data-close>Batal</button><button class="btn btn-primary">Mulai Penghitungan</button></div></form></div></div><?php endif; ?>
  <?php endif; ?>

<?php elseif ($key === 'audit'):
  $logs = q_all("SELECT * FROM audit_logs WHERE module=? ORDER BY created_at DESC LIMIT 60", [INV_MOD]);
  $moves = q_all('SELECT m.*, i.name, u.full_name FROM stock_movements m JOIN inventory_items i ON i.id=m.item_id LEFT JOIN users u ON u.id=m.created_by ORDER BY m.created_at DESC LIMIT 30'); ?>
  <section class="grid grid-2e">
    <div class="card"><div class="card-head"><div><h3>Aktivitas Pengguna</h3><p>Audit trail modul Inventory</p></div></div><div class="table-wrap"><table class="table"><thead><tr><th>Timestamp</th><th>User</th><th>Aktivitas</th><th>Keterangan</th></tr></thead><tbody>
      <?php if (!$logs): ?><tr><td colspan="4" class="empty">Belum ada aktivitas.</td></tr><?php endif; foreach ($logs as $l): ?><tr><td class="mono"><?= tgl($l['created_at'], true) ?></td><td><?= e($l['username'] ?? '-') ?></td><td><span class="badge"><?= e($l['action']) ?></span></td><td><?= e($l['description']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    <div class="card"><div class="card-head"><div><h3>Pergerakan Stok</h3><p>30 transaksi terakhir</p></div></div><div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Barang</th><th>Jenis</th><th>Qty</th><th>Ref</th><th>User</th></tr></thead><tbody>
      <?php foreach ($moves as $m): ?><tr><td class="mono"><?= tgl($m['created_at'], true) ?></td><td><?= e($m['name']) ?></td><td><?= status_badge($m['movement_type']) ?></td><td class="mono"><?= (int)$m['qty'] ?></td><td class="mono"><?= e($m['reference']) ?></td><td><?= e($m['full_name'] ?? '-') ?></td></tr><?php endforeach; ?></tbody></table></div></div>
  </section>
<?php endif; ?>
</div>
<?php layout_end();
