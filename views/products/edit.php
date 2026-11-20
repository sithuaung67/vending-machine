<?php /** @var array $product */ $title = 'Edit ' . $product['name']; ?>
<div class="page-head">
  <div><h1>Edit <?= e($product['name']) ?></h1><p class="muted">Product #<?= (int) $product['id'] ?></p></div>
</div>
<div class="card form-card">
  <form method="post" action="/products/<?= (int) $product['id'] ?>" class="stack" data-validate novalidate>
    <?= csrf_field() ?><input type="hidden" name="_method" value="PUT">
    <?php require __DIR__ . '/_form.php'; ?>
    <div class="actions"><button class="btn" type="submit">Save changes</button><a class="btn ghost" href="/products">Cancel</a></div>
  </form>
</div>
<?php clear_old(); ?>
