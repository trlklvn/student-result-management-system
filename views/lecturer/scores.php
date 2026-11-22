<p><a href="index.php?r=lecturer/courses">← My courses</a></p>
<h1><?= e($course['code'] . ' · ' . $course['title']) ?></h1>
<p class="muted">CA is out of <?= (int) $maxCa ?> and the exam is out of <?= (int) $maxExam ?>. Grades are worked out when you save.
Submitted and published results are locked.</p>

<form method="post" action="index.php?r=lecturer/scores&course=<?= (int) $course['id'] ?>">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <div class="table-wrap"><table>
    <thead><tr><th>Index number</th><th>Name</th><th>CA /<?= (int) $maxCa ?></th><th>Exam /<?= (int) $maxExam ?></th><th>Total</th><th>Grade</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $locked = in_array($r['status'], ['submitted', 'published'], true); $id = (int) $r['enrollment_id']; ?>
      <tr>
        <td><?= e($r['index_number']) ?></td><td><?= e($r['full_name']) ?></td>
        <td><input class="score" type="number" step="0.5" min="0" max="<?= (int) $maxCa ?>" name="scores[<?= $id ?>][ca]" value="<?= e($r['ca_score'] ?? '') ?>" <?= $locked ? 'disabled' : '' ?>></td>
        <td><input class="score" type="number" step="0.5" min="0" max="<?= (int) $maxExam ?>" name="scores[<?= $id ?>][exam]" value="<?= e($r['exam_score'] ?? '') ?>" <?= $locked ? 'disabled' : '' ?>></td>
        <td><?= e($r['total'] ?? '—') ?></td><td><?= e($r['grade'] ?? '—') ?></td>
        <td><span class="pill <?= e($r['status'] ?? 'missing') ?>"><?= e($r['status'] ?? 'no score') ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if ($summary['draft'] + $summary['missing'] > 0): ?>
  <div class="actions">
    <button type="submit" name="action" value="save">Save as draft</button>
    <button type="submit" name="action" value="submit" class="secondary">Submit for approval</button>
  </div>
  <?php endif; ?>
</form>
