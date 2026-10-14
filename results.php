<?php
require __DIR__ . '/includes/auth.php';
requireAuth(['admin']);

require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();
$message = '';
$csrfToken = generateCsrfToken();
$editResult = null;

$editResultId = filter_input(INPUT_GET, 'edit_result', FILTER_VALIDATE_INT);
if ($editResultId) {
    $stmt = $pdo->prepare('SELECT * FROM results WHERE id = ? LIMIT 1');
    $stmt->execute([$editResultId]);
    $editResult = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Token de sécurité invalide.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_result') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $courseCode = strtoupper(sanitizeText($_POST['course_code'] ?? ''));
            $grade = (float) ($_POST['grade'] ?? 0);
            $status = sanitizeText($_POST['status'] ?? '');

            if ($userId <= 0 || $courseCode === '' || $grade < 0 || $grade > 100 || $status === '') {
                $message = 'Veuillez remplir correctement les informations de résultat.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO results (user_id, course_code, grade, status) VALUES (?, ?, ?, ?)');
                $stmt->execute([$userId, $courseCode, $grade, $status]);
                $message = 'Résultat enregistré.';
            }
        }

        if ($action === 'update_result') {
            $id = (int) ($_POST['result_id'] ?? 0);
            $userId = (int) ($_POST['user_id'] ?? 0);
            $courseCode = strtoupper(sanitizeText($_POST['course_code'] ?? ''));
            $grade = (float) ($_POST['grade'] ?? 0);
            $status = sanitizeText($_POST['status'] ?? '');

            if ($id <= 0 || $userId <= 0 || $courseCode === '' || $grade < 0 || $grade > 100 || $status === '') {
                $message = 'Les informations du résultat sont incomplètes.';
            } else {
                $stmt = $pdo->prepare('UPDATE results SET user_id = ?, course_code = ?, grade = ?, status = ? WHERE id = ?');
                $stmt->execute([$userId, $courseCode, $grade, $status, $id]);
                $message = 'Résultat mis à jour avec succès.';
            }
        }

        if ($action === 'delete_result') {
            $id = (int) ($_POST['result_id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare('DELETE FROM results WHERE id = ?')->execute([$id]);
                $message = 'Résultat supprimé avec succès.';
            }
        }
    }
}

$students = $pdo->query("SELECT id, name, matricule FROM users WHERE profile = 'student' ORDER BY id DESC")->fetchAll();
$courses = $pdo->query('SELECT code, name FROM courses ORDER BY id')->fetchAll();
$results = $pdo->query('SELECT r.id, u.name AS student_name, u.matricule, r.course_code, r.grade, r.status FROM results r JOIN users u ON u.id = r.user_id ORDER BY r.id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Résultats</title>
  <link rel="stylesheet" href="assets/vendors/typicons/typicons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    :root { --unitech-blue: #5d8cf4; --unitech-blue-dark: #1d3968; --unitech-yellow: #f4dc61; --unitech-navy: #112a50; --unitech-bg: #f4f7ff; --unitech-card:#fff; --unitech-text:#1d2b3a; --unitech-muted:#66758c; }
    body { background: var(--unitech-bg); color: var(--unitech-text); font-family: "Poppins", sans-serif; }
    .page-body-wrapper { padding-top: 72px; }
    .content-wrapper { padding: 1.5rem; }
    .page-title { font-size: 2rem; font-weight: 800; color: var(--unitech-blue-dark); margin-bottom: 1rem; }
    .card { border:none; border-radius:18px; box-shadow:0 10px 24px rgba(17,42,80,0.06); }
    .btn-primary { background: linear-gradient(135deg, var(--unitech-blue), var(--unitech-blue-dark)); border:none; border-radius:12px; }
    .table-actions { display:flex; gap:0.5rem; }
  </style>
</head>
<body>
  <div class="container-scroller">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="navbar-brand-wrapper d-flex justify-content-center">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">
          <a class="navbar-brand brand-logo" href="/index.php"><img src="assets/images/logo.jpg" alt="logo" /></a>
          <a class="navbar-brand brand-logo-mini" href="/index.php"><img src="assets/images/logo.jpg" alt="logo" /></a>
        </div>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item nav-date dropdown"><a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;"><h6 class="date mb-0">Gestion des résultats</h6></a></li>
        </ul>
      </div>
    </nav>

    <div class="page-body-wrapper">
      <div class="main-panel w-100">
        <div class="content-wrapper">
          <div class="page-title">Gestion des résultats</div>

          <?php if ($message !== ''): ?>
            <div class="alert alert-info mb-4"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>

          <div class="row g-4">
            <div class="col-xl-4">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">
                  <?= $editResult ? 'Modifier un résultat' : 'Ajouter un résultat' ?>
                </h4>
                <form method="post">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="action" value="<?= $editResult ? 'update_result' : 'create_result' ?>">
                  <?php if ($editResult): ?>
                    <input type="hidden" name="result_id" value="<?= (int) $editResult['id'] ?>">
                  <?php endif; ?>
                  <div class="mb-3">
                    <label class="form-label">Étudiant</label>
                    <select name="user_id" class="form-select" required>
                      <?php foreach ($students as $student): ?>
                        <option value="<?= (int) $student['id'] ?>" <?= (($editResult['user_id'] ?? '') == $student['id']) ? 'selected' : '' ?>><?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['matricule']) ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Cours</label>
                    <select name="course_code" class="form-select" required>
                      <?php foreach ($courses as $course): ?>
                        <option value="<?= htmlspecialchars($course['code']) ?>" <?= (($editResult['course_code'] ?? '') === $course['code']) ? 'selected' : '' ?>><?= htmlspecialchars($course['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Note</label>
                    <input type="number" min="0" max="100" step="0.1" name="grade" class="form-control" value="<?= htmlspecialchars((string) ($editResult['grade'] ?? '')) ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select" required>
                      <?php $selectedStatus = $editResult['status'] ?? 'Validé'; ?>
                      <option value="Validé" <?= $selectedStatus === 'Validé' ? 'selected' : '' ?>>Validé</option>
                      <option value="Réussi" <?= $selectedStatus === 'Réussi' ? 'selected' : '' ?>>Réussi</option>
                      <option value="En attente" <?= $selectedStatus === 'En attente' ? 'selected' : '' ?>>En attente</option>
                      <option value="Échoué" <?= $selectedStatus === 'Échoué' ? 'selected' : '' ?>>Échoué</option>
                    </select>
                  </div>
                  <button type="submit" class="btn btn-primary w-100"><?= $editResult ? 'Enregistrer les modifications' : 'Enregistrer' ?></button>
                </form>
              </div>
            </div>

            <div class="col-xl-8">
              <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="mb-0" style="font-weight:800; color: var(--unitech-blue-dark);">Résultats</h4>
                  <a href="/admin-dashboard.php" class="btn btn-light">Retour</a>
                </div>
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr><th>Étudiant</th><th>Matricule</th><th>Cours</th><th>Note</th><th>Statut</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                      <?php foreach ($results as $row): ?>
                        <tr>
                          <td><?= htmlspecialchars($row['student_name']) ?></td>
                          <td><?= htmlspecialchars($row['matricule']) ?></td>
                          <td><?= htmlspecialchars($row['course_code']) ?></td>
                          <td><?= htmlspecialchars((string) $row['grade']) ?></td>
                          <td><?= htmlspecialchars($row['status']) ?></td>
                          <td>
                            <div class="table-actions">
                              <a href="?edit_result=<?= (int) $row['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
                              <form method="post" onsubmit="return confirm('Supprimer ce résultat ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_result">
                                <input type="hidden" name="result_id" value="<?= (int) $row['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                              </form>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
