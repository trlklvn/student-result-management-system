<p><a href="index.php?r=admin/dashboard">← Dashboard</a></p>
<h1><?= e($course['code'] . ' · ' . $course['title']) ?></h1>
<p class="muted">
  <?= $summary['submitted'] ?> awaiting approval · <?= $summary['published'] ?> published ·
  <?= $summary['draft'] ?> draft · <?= $summary['missing'] ?> without a score
</p>

<div class="table-wrap"><table>
  <thead><tr><th>Index number</th><th>Name</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['index_number']) ?></td><td><?= e($r['full_name']) ?></td>
      <td><?= e($r['ca_score'] ?? '—') ?></td><td><?= e($r['exam_score'] ?? '—') ?></td><td><?= e($r['total'] ?? '—') ?></td>
      <td><?= e($r['grade'] ?? '—') ?></td><td><span class="pill <?= e($r['status'] ?? 'missing') ?>"><?= e($r['status'] ?? 'no score') ?></span></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<?php if ($summary['submitted'] > 0): ?>
<div class="actions">
  <form method="post" action="index.php?r=admin/review&course=<?= (int) $course['id'] ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="publish">
    <button type="submit">Publish results</button>
  </form>
  <form method="post" action="index.php?r=admin/review&course=<?= (int) $course['id'] ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="return">
    <button type="submit" class="secondary">Return to lecturer</button>
  </form>
</div>
<?php endif; ?>
