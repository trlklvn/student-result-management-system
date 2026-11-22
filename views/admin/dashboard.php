<h1>Dashboard</h1>
<div class="stats">
  <?php foreach ($stats as $label => $value): ?>
    <div class="card stat"><span class="num"><?= (int) $value ?></span><span class="muted"><?= e($label) ?></span></div>
  <?php endforeach; ?>
</div>

<h2>Courses and result status</h2>
<div class="table-wrap"><table>
  <thead><tr><th>Code</th><th>Title</th><th>Lecturer</th><th>Year / Sem</th><th>Enrolled</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($courses as $c): ?>
    <tr>
      <td><?= e($c['code']) ?></td><td><?= e($c['title']) ?></td>
      <td><?= e($c['lecturer_name'] ?? 'Not assigned') ?></td>
      <td><?= e($c['academic_year']) ?> / <?= (int) $c['semester'] ?></td>
      <td><?= (int) $c['enrolled'] ?></td>
      <td>
        <?php if ((int) $c['submitted'] > 0): ?><span class="pill submitted">Awaiting approval</span>
        <?php elseif ((int) $c['published'] > 0 && (int) $c['published'] === (int) $c['enrolled']): ?><span class="pill published">Published</span>
        <?php else: ?><span class="pill draft">In progress</span><?php endif; ?>
      </td>
      <td><a href="index.php?r=admin/review&course=<?= (int) $c['id'] ?>">Review</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<h2>Recent activity</h2>
<div class="table-wrap"><table>
  <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th></tr></thead>
  <tbody>
  <?php foreach ($audit as $a): ?>
    <tr><td><?= e($a['created_at']) ?></td><td><?= e($a['full_name'] ?? '—') ?></td><td><?= e($a['action']) ?></td><td><?= e($a['details']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
