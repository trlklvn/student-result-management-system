<h1>Courses</h1>
<div class="table-wrap"><table>
  <thead><tr><th>Code</th><th>Title</th><th>Credits</th><th>Year / Sem</th><th>Lecturer</th><th>Enrolled</th></tr></thead>
  <tbody>
  <?php foreach ($courses as $c): ?>
    <tr><td><?= e($c['code']) ?></td><td><?= e($c['title']) ?></td><td><?= (int) $c['credit_hours'] ?></td>
      <td><?= e($c['academic_year']) ?> / <?= (int) $c['semester'] ?></td><td><?= e($c['lecturer_name'] ?? 'Not assigned') ?></td><td><?= (int) $c['enrolled'] ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>

<div class="cols three">
  <form method="post" action="index.php?r=admin/courses" class="card stack">
    <h2>Add a course</h2>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="add_course">
    <label>Code<input name="code" placeholder="CSC 201" required></label>
    <label>Title<input name="title_text" required></label>
    <label>Credit hours<input type="number" name="credit_hours" min="1" max="6" value="3" required></label>
    <label>Semester<select name="semester"><option>1</option><option>2</option></select></label>
    <label>Academic year<input name="academic_year" placeholder="2025/2026" required></label>
    <label>Lecturer<select name="lecturer_id"><option value="">Not assigned</option>
      <?php foreach ($lecturers as $l): ?><option value="<?= (int) $l['id'] ?>"><?= e($l['full_name']) ?></option><?php endforeach; ?>
    </select></label>
    <button type="submit">Add course</button>
  </form>

  <form method="post" action="index.php?r=admin/courses" class="card stack">
    <h2>Enrol students</h2>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="enroll">
    <label>Course<select name="course_id">
      <?php foreach ($courses as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['code'] . ' ' . $c['title']) ?></option><?php endforeach; ?>
    </select></label>
    <fieldset class="checks"><legend>Students</legend>
      <?php foreach ($students as $s): ?>
        <label class="check"><input type="checkbox" name="student_ids[]" value="<?= (int) $s['id'] ?>"> <?= e($s['index_number'] . ' · ' . $s['full_name']) ?></label>
      <?php endforeach; ?>
    </fieldset>
    <button type="submit">Enrol selected</button>
  </form>

  <form method="post" action="index.php?r=admin/courses" class="card stack">
    <h2>Add a lecturer</h2>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="add_lecturer">
    <label>Username<input name="username" placeholder="k.mensah" required></label>
    <label>Full name<input name="full_name" required></label>
    <label>First password<input type="password" name="password" minlength="6" required></label>
    <button type="submit">Add lecturer</button>
  </form>
</div>
