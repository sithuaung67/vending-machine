<?php $title = 'Add Product'; ?>
<div class="page-head">
  <div><h1>Add product</h1><p class="muted">Add a new item to the vending machine.</p></div>
</div>
<div class="card form-card">
  <form method="post" action="/products" class="stack" data-validate novalidate>
    <?= csrf_field() ?>
    <?php require __DIR__ . '/_form.php'; ?>
    <div class="actions"><button class="btn" type="submit">Create product</button><a class="btn ghost" href="/products">Cancel</a></div>
  </form>
</div>
<?php clear_old(); ?>
