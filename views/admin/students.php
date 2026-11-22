<h1>Students</h1>
<div class="cols">
  <form method="post" action="index.php?r=admin/students" class="card stack">
    <h2>Add a student</h2>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <label>Index number<input name="index_number" placeholder="UEB0012345" required></label>
    <label>Full name<input name="full_name" required></label>
    <label>Programme<input name="programme" placeholder="BSc Computer Science" required></label>
    <label>Level
      <select name="level"><option>100</option><option>200</option><option>300</option><option>400</option></select>
    </label>
    <label>First password<input type="password" name="password" minlength="6" required></label>
    <button type="submit">Add student</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>Index number</th><th>Name</th><th>Programme</th><th>Level</th></tr></thead>
    <tbody>
    <?php foreach ($students as $s): ?>
      <tr><td><?= e($s['index_number']) ?></td><td><?= e($s['full_name']) ?></td><td><?= e($s['programme']) ?></td><td><?= (int) $s['level'] ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$students): ?><tr><td colspan="4" class="muted">No students yet. Add the first one with the form.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
