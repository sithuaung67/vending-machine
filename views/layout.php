<?php /** @var string $content */ $u = \App\Core\Auth::user(); $isAdmin = ($u['role'] ?? '') === 'admin'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($title) ? e($title) . ' · ' : '' ?>Vending Machine</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <div class="inner">
      <a class="brand" href="/products"><span class="logo">🥤</span><span class="text">Vending Machine</span></a>
      <nav class="menu">
        <?php if ($u): ?>
          <a href="/products"<?= nav_active('/products') ?>>Products</a>
          <a href="/transactions"<?= nav_active('/transactions') ?>><?= $isAdmin ? 'Transactions' : 'My purchases' ?></a>
        <?php endif; ?>
      </nav>
      <div class="account">
        <?php if ($u): ?>
          <span class="avatar"><?= e(strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
          <span class="who"><strong><?= e($u['name']) ?></strong><small><?= e($u['role']) ?></small></span>
          <form method="post" action="/logout" class="inline"><?= csrf_field() ?><button class="btn sm ghost">Log out</button></form>
        <?php else: ?>
          <a class="btn sm ghost" href="/login">Log in</a>
          <a class="btn sm" href="/register">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="container">
    <?php if ($m = flash('success')): ?><div class="alert ok" role="status">✓ <span><?= e($m) ?></span></div><?php endif; ?>
    <?php if ($m = flash('error')): ?><div class="alert err" role="alert">! <span><?= e($m) ?></span></div><?php endif; ?>
    <?= $content ?>
  </main>

  <footer class="foot"><div class="inner">Vending Machine System · PHP 8 MVC</div></footer>
  <script src="/assets/js/validate.js" defer></script>
</body>
</html>
