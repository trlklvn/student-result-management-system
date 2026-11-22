<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'SRMS') ?> · SRMS</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="bar">
  <a class="brand" href="index.php">SRMS</a>
  <?php if ($user): ?>
    <nav>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="index.php?r=admin/dashboard">Dashboard</a>
        <a href="index.php?r=admin/students">Students</a>
        <a href="index.php?r=admin/courses">Courses</a>
      <?php elseif ($user['role'] === 'lecturer'): ?>
        <a href="index.php?r=lecturer/courses">My courses</a>
      <?php else: ?>
        <a href="index.php?r=student/results">My results</a>
      <?php endif; ?>
    </nav>
    <div class="who"><?= e($user['full_name']) ?> <span class="role"><?= e($user['role']) ?></span>
      <a href="index.php?r=auth/logout">Sign out</a></div>
  <?php endif; ?>
</header>
<main>
  <?php if ($flash): ?>
    <div class="flash <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
  <?php endif; ?>
  <?= $content ?>
</main>
</body>
</html>
