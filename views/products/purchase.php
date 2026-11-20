<?php /** @var array $product */ $title = 'Buy ' . $product['name']; $qty = (int) $product['quantity_available']; ?>
<div class="page-head">
  <div><a class="muted" href="/products">← Back to products</a><h1 style="margin-top:6px">Buy <?= e($product['name']) ?></h1></div>
</div>

<div class="buy">
  <div class="card hero">
    <span class="big-thumb"><?= e(strtoupper(mb_substr($product['name'], 0, 1))) ?></span>
    <div>
      <h2 style="margin:0"><?= e($product['name']) ?></h2>
      <span class="muted">Product #<?= (int) $product['id'] ?></span>
    </div>
    <dl class="kv">
      <dt>Unit price</dt><dd class="num">$<?= money($product['price']) ?></dd>
      <dt>Availability</dt>
      <dd><?php if ($qty === 0): ?><span class="pill err">Sold out</span>
          <?php elseif ($qty <= 5): ?><span class="pill warn"><?= $qty ?> left</span>
          <?php else: ?><span class="pill ok"><?= $qty ?> in stock</span><?php endif; ?></dd>
    </dl>
  </div>

  <div class="card">
    <?php if ($qty === 0): ?>
      <div class="empty"><div class="icon">🚫</div>This product is sold out.</div>
    <?php else: ?>
    <form method="post" action="/products/<?= (int) $product['id'] ?>/purchase" class="stack"
          data-validate data-purchase data-price="<?= e($product['price']) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="quantity">Quantity</label>
        <div class="stepper">
          <button type="button" data-step="-1" aria-label="Decrease">−</button>
          <input id="quantity" name="quantity" type="number" min="1" max="<?= $qty ?>" step="1" value="1" data-rule="required|int|positive">
          <button type="button" data-step="1" aria-label="Increase">+</button>
        </div>
        <div class="field-err"></div>
        <div class="hint">Between 1 and <?= $qty ?>.</div>
      </div>
      <div class="total"><span class="muted">Total</span><strong data-total>$<?= money($product['price']) ?></strong></div>
      <button class="btn block" type="submit">Confirm purchase</button>
    </form>
    <?php endif; ?>
  </div>
</div>
