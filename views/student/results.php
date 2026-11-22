<h1>My results</h1>
<div class="card profile">
  <div><span class="muted">Name</span><strong><?= e($student['full_name']) ?></strong></div>
  <div><span class="muted">Index number</span><strong><?= e($student['index_number']) ?></strong></div>
  <div><span class="muted">Programme</span><strong><?= e($student['programme']) ?></strong></div>
  <div><span class="muted">Level</span><strong><?= (int) $student['level'] ?></strong></div>
  <div><span class="muted">CGPA</span><strong class="big"><?= number_format($transcript['cgpa'], 2) ?></strong></div>
  <div><span class="muted">Class</span><strong><?= e($transcript['class']) ?></strong></div>
</div>

<?php if (!$transcript['semesters']): ?>
  <p class="muted">No results have been published for you yet. Results appear here once the administrator approves them.</p>
<?php endif; ?>

<?php foreach ($transcript['semesters'] as $label => $sem): ?>
  <h2><?= e($label) ?> <span class="muted">· GPA <?= number_format($sem['gpa'], 2) ?> · <?= (int) $sem['credits'] ?> credits</span></h2>
  <div class="table-wrap"><table>
    <thead><tr><th>Code</th><th>Course</th><th>Credits</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Point</th></tr></thead>
    <tbody>
    <?php foreach ($sem['courses'] as $c): ?>
      <tr><td><?= e($c['code']) ?></td><td><?= e($c['title']) ?></td><td><?= (int) $c['credit_hours'] ?></td>
        <td><?= e($c['ca_score']) ?></td><td><?= e($c['exam_score']) ?></td><td><?= e($c['total']) ?></td>
        <td><strong><?= e($c['grade']) ?></strong></td><td><?= number_format((float) $c['grade_point'], 1) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endforeach; ?>
