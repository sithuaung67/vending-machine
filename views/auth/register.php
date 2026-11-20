<?php $title = 'Register'; ?>
<div class="auth">
  <div class="card">
    <h1>Create account</h1>
    <p class="muted lead">New accounts get the User role.</p>
    <form method="post" action="/register" class="stack" data-validate novalidate>
      <?= csrf_field() ?>
      <div class="field"><label for="name">Name</label><input id="name" name="name" value="<?= old('name') ?>" autocomplete="name" data-rule="required"><div class="field-err"></div></div>
      <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= old('email') ?>" autocomplete="email" data-rule="required|email"><div class="field-err"></div></div>
      <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="new-password" data-rule="required|min6"><div class="field-err"></div><div class="hint">At least 6 characters.</div></div>
      <button class="btn block" type="submit">Create account</button>
    </form>
    <p class="muted" style="text-align:center;margin:16px 0 0">Already have an account? <a href="/login">Log in</a></p>
  </div>
</div>
<?php clear_old(); ?>
