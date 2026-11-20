<?php
/** @var array $page @var array $summary @var bool $isAdmin */
$title = $isAdmin ? 'Transactions' : 'My purchases';
$from = $page['total'] ? ($page['page'] - 1) * $page['per_page'] + 1 : 0;
$to = min($page['total'], $page['page'] * $page['per_page']);
$cols = $isAdmin ? 6 : 5;
?>
<div class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= $isAdmin ? 'Every purchase made on the machine: who bought what, and when.' : 'Everything you have bought from the machine.' ?></p>
  </div>
</div>

<div class="stats">
  <div class="stat"><div class="label">Purchases</div><div class="value"><?= number_format($summary['purchases']) ?></div></div>
  <div class="stat"><div class="label">Items <?= $isAdmin ? 'sold' : 'bought' ?></div><div class="value"><?= number_format($summary['units']) ?></div></div>
  <div class="stat"><div class="label"><?= $isAdmin ? 'Revenue' : 'Total spent' ?></div><div class="value">$<?= money($summary['revenue']) ?></div></div>
</div>

<div class="table-card">
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th class="hide-sm">#</th>
      <th>Date</th>
      <?php if ($isAdmin): ?><th>Customer</th><?php endif; ?>
      <th>Product</th>
      <th class="right">Qty</th>
      <th class="right hide-sm">Unit price</th>
      <th class="right">Total</th>
    </tr></thead>
    <tbody>
    <?php if (!$page['data']): ?>
      <tr><td colspan="<?= $cols + 1 ?>"><div class="empty"><div class="icon">🧾</div>No purchases yet.
        <?php if (!$isAdmin): ?><br><a href="/products">Browse products</a><?php endif; ?></div></td></tr>
    <?php endif; ?>
    <?php foreach ($page['data'] as $t): $ts = strtotime((string) $t['created_at']); ?>
      <tr>
        <td class="muted num hide-sm"><?= (int) $t['id'] ?></td>
        <td class="num nowrap-sm">
          <?= $ts ? e(date('M j, Y', $ts)) : '—' ?>
          <div class="muted"><?= $ts ? e(date('H:i', $ts)) : '' ?></div>
        </td>
        <?php if ($isAdmin): ?>
        <td>
          <?= e($t['user_name'] ?? 'Deleted user') ?>
          <div class="muted"><?= e($t['user_email'] ?? '') ?></div>
        </td>
        <?php endif; ?>
        <td>
          <div class="product">
            <span class="thumb"><?= e(strtoupper(mb_substr((string) ($t['product_name'] ?? '?'), 0, 1))) ?></span>
            <span class="name"><?= e($t['product_name'] ?? 'Deleted product') ?></span>
          </div>
        </td>
        <td class="right num"><?= (int) $t['quantity'] ?></td>
        <td class="right num hide-sm">$<?= money($t['unit_price']) ?></td>
        <td class="right price">$<?= money($t['total_price']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <div class="pager-bar">
    <span class="muted">Showing <?= $from ?>–<?= $to ?> of <?= (int) $page['total'] ?></span>
    <?php if ($page['pages'] > 1): ?>
    <nav class="pager" aria-label="Pagination">
      <?php if ($page['page'] > 1): ?><a href="/transactions?page=<?= $page['page'] - 1 ?>" aria-label="Previous">‹</a><?php else: ?><span class="off">‹</span><?php endif; ?>
      <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
        <?php if ($i === $page['page']): ?><span class="on"><?= $i ?></span>
        <?php else: ?><a href="/transactions?page=<?= $i ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($page['page'] < $page['pages']): ?><a href="/transactions?page=<?= $page['page'] + 1 ?>" aria-label="Next">›</a><?php else: ?><span class="off">›</span><?php endif; ?>
    </nav>
    <?php endif; ?>
  </div>
</div>
