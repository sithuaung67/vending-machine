<?php
/** @var array $page @var bool $isAdmin */
$title = 'Products';
$sort = $page['sort']; $dir = $page['dir'];
$link = function (string $col, string $label) use ($sort, $dir) {
    $next = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $sort === $col ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
    return '<a href="/products?sort=' . $col . '&dir=' . $next . '">' . e($label) . $arrow . '</a>';
};
$th = function (string $col, string $extra = '') use ($sort) {
    $cls = trim(($sort === $col ? 'sorted ' : '') . $extra);
    return $cls ? ' class="' . $cls . '"' : '';
};
$sortLabels = ['id' => 'Newest', 'name' => 'Name', 'price' => 'Price', 'quantity_available' => 'Stock'];
$from = $page['total'] ? ($page['page'] - 1) * $page['per_page'] + 1 : 0;
$to = min($page['total'], $page['page'] * $page['per_page']);
$qs = fn (int $p) => '/products?page=' . $p . '&sort=' . urlencode($sort) . '&dir=' . urlencode($dir);
?>
<div class="page-head">
  <div>
    <h1>Products</h1>
    <p class="muted"><?= $isAdmin ? 'Manage the machine’s products and stock.' : 'Pick a drink and buy it.' ?></p>
  </div>
  <?php if ($isAdmin): ?><a class="btn" href="/products/create">+ Add product</a><?php endif; ?>
</div>

<div class="table-card">
  <div class="toolbar">
    <span class="muted"><?= (int) $page['total'] ?> product<?= $page['total'] == 1 ? '' : 's' ?></span>
    <form method="get" action="/products">
      <label class="muted" for="sort">Sort by</label>
      <select id="sort" name="sort" onchange="this.form.submit()">
        <?php foreach ($sortLabels as $k => $label): ?>
          <option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <select name="dir" onchange="this.form.submit()" aria-label="Direction">
        <option value="asc"<?= $dir === 'asc' ? ' selected' : '' ?>>Ascending</option>
        <option value="desc"<?= $dir === 'desc' ? ' selected' : '' ?>>Descending</option>
      </select>
      <noscript><button class="btn sm ghost">Apply</button></noscript>
    </form>
  </div>

  <div class="table-wrap">
  <table class="cards-sm">
    <thead><tr>
      <th<?= $th('id', 'hide-sm') ?>><?= $link('id', '#') ?></th>
      <th<?= $th('name') ?>><?= $link('name', 'Product') ?></th>
      <th<?= $th('price') ?>><?= $link('price', 'Price') ?></th>
      <th<?= $th('quantity_available') ?>><?= $link('quantity_available', 'Stock') ?></th>
      <th class="right">Actions</th>
    </tr></thead>
    <tbody>
    <?php if (!$page['data']): ?>
      <tr><td colspan="5"><div class="empty"><div class="icon">📦</div>No products yet.</div></td></tr>
    <?php endif; ?>
    <?php foreach ($page['data'] as $p): $qty = (int) $p['quantity_available']; ?>
      <tr>
        <td class="muted num hide-sm"><?= (int) $p['id'] ?></td>
        <td>
          <div class="product">
            <span class="thumb"><?= e(strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
            <span class="name"><?= e($p['name']) ?></span>
          </div>
        </td>
        <td class="price">$<?= money($p['price']) ?></td>
        <td>
          <?php if ($qty === 0): ?><span class="pill err">Sold out</span>
          <?php elseif ($qty <= 5): ?><span class="pill warn"><?= $qty ?> left</span>
          <?php else: ?><span class="pill ok"><?= $qty ?> in stock</span><?php endif; ?>
        </td>
        <td class="full">
          <div class="row-actions">
            <?php if ($qty > 0): ?>
              <a class="btn sm" href="/products/<?= (int) $p['id'] ?>/purchase">Buy</a>
            <?php else: ?>
              <button class="btn sm" disabled>Buy</button>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
              <a class="btn sm ghost" href="/products/<?= (int) $p['id'] ?>/edit">Edit</a>
              <form method="post" action="/products/<?= (int) $p['id'] ?>" class="inline" onsubmit="return confirm('Delete <?= e($p['name']) ?>?')">
                <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE">
                <button class="btn sm danger">Delete</button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <div class="pager-bar">
    <span class="muted">Showing <?= $from ?>–<?= $to ?> of <?= (int) $page['total'] ?></span>
    <?php if ($page['pages'] > 1): ?>
    <nav class="pager" aria-label="Pagination">
      <?php if ($page['page'] > 1): ?><a href="<?= e($qs($page['page'] - 1)) ?>" aria-label="Previous">‹</a><?php else: ?><span class="off">‹</span><?php endif; ?>
      <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
        <?php if ($i === $page['page']): ?><span class="on"><?= $i ?></span>
        <?php else: ?><a href="<?= e($qs($i)) ?>"><?= $i ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($page['page'] < $page['pages']): ?><a href="<?= e($qs($page['page'] + 1)) ?>" aria-label="Next">›</a><?php else: ?><span class="off">›</span><?php endif; ?>
    </nav>
    <?php endif; ?>
  </div>
</div>
