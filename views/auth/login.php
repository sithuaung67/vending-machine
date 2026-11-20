<?php $title = 'Log in'; ?>
<div class="auth">
  <div class="card">
    <h1>Welcome back</h1>
    <p class="muted lead">Log in to buy drinks or manage the machine.</p>
    <form method="post" action="/login" class="stack" data-validate novalidate>
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= old('email') ?>" autocomplete="email" data-rule="required|email">
        <div class="field-err"></div>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="current-password" data-rule="required">
        <div class="field-err"></div>
      </div>
      <button class="btn block" type="submit">Log in</button>
    </form>
    <p class="muted" style="text-align:center;margin:16px 0 0">No account? <a href="/register">Create one</a></p>
    <div class="demo">
      <strong>Demo accounts</strong><br>
      Admin: <code>admin@vending.test</code> / <code>admin123</code><br>
      User: <code>user@vending.test</code> / <code>user123</code>
    </div>
  </div>
</div>
<?php clear_old(); ?>
