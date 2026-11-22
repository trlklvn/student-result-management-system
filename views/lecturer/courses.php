<h1>My courses</h1>
<?php if (!$courses): ?><p class="muted">No courses are assigned to you yet. The administrator assigns courses.</p><?php endif; ?>
<div class="grid">
<?php foreach ($courses as $c): $s = $c['summary']; ?>
  <a class="card course" href="index.php?r=lecturer/scores&course=<?= (int) $c['id'] ?>">
    <strong><?= e($c['code']) ?></strong>
    <span><?= e($c['title']) ?></span>
    <span class="muted"><?= (int) $c['enrolled'] ?> students · <?= e($c['academic_year']) ?> sem <?= (int) $c['semester'] ?></span>
    <span>
      <?php if ($s['published'] > 0 && $s['published'] == $c['enrolled']): ?><span class="pill published">Published</span>
      <?php elseif ($s['submitted'] > 0): ?><span class="pill submitted">Awaiting approval</span>
      <?php else: ?><span class="pill draft"><?= $s['missing'] ?> score(s) to enter</span><?php endif; ?>
    </span>
  </a>
<?php endforeach; ?>
</div>
