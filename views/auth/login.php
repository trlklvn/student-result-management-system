<section class="login">
  <h1>Student Result Management System</h1>
  <p class="muted">Sign in with your username. Students use their index number.</p>
  <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="index.php?r=auth/login" class="card stack">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <label>Username<input name="username" value="<?= e($username) ?>" required autofocus></label>
    <label>Password<input type="password" name="password" required></label>
    <button type="submit">Sign in</button>
  </form>
</section>
