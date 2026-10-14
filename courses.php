<?php
require __DIR__ . '/includes/auth.php';
requireAuth(['admin']);

require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();
$message = '';
$csrfToken = generateCsrfToken();
$editCourse = null;

$editCourseId = filter_input(INPUT_GET, 'edit_course', FILTER_VALIDATE_INT);
if ($editCourseId) {
    $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ? LIMIT 1');
    $stmt->execute([$editCourseId]);
    $editCourse = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Token de sécurité invalide.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_course') {
            $code = strtoupper(sanitizeText($_POST['code'] ?? ''));
            $name = sanitizeText($_POST['name'] ?? '');
            $credits = (int) ($_POST['credits'] ?? 0);
            $semester = sanitizeText($_POST['semester'] ?? '');
            $teacher = sanitizeText($_POST['teacher'] ?? '');
            $hours = (int) ($_POST['hours'] ?? 0);

            if ($code === '' || $name === '' || $semester === '' || $teacher === '' || $credits <= 0 || $hours <= 0) {
                $message = 'Tous les champs du cours doivent être remplis.';
            } else {
                try {
                    $stmt = $pdo->prepare('INSERT INTO courses (code, name, credits, semester, teacher, hours) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$code, $name, $credits, $semester, $teacher, $hours]);
                    $message = 'Cours ajouté avec succès.';
                } catch (Throwable $e) {
                    $message = 'Ce code de cours existe déjà.';
                }
            }
        }

        if ($action === 'update_course') {
            $id = (int) ($_POST['course_id'] ?? 0);
            $code = strtoupper(sanitizeText($_POST['code'] ?? ''));
            $name = sanitizeText($_POST['name'] ?? '');
            $credits = (int) ($_POST['credits'] ?? 0);
            $semester = sanitizeText($_POST['semester'] ?? '');
            $teacher = sanitizeText($_POST['teacher'] ?? '');
            $hours = (int) ($_POST['hours'] ?? 0);

            if ($id <= 0 || $code === '' || $name === '' || $semester === '' || $teacher === '' || $credits <= 0 || $hours <= 0) {
                $message = 'Les informations du cours sont incomplètes.';
            } else {
                $stmt = $pdo->prepare('UPDATE courses SET code = ?, name = ?, credits = ?, semester = ?, teacher = ?, hours = ? WHERE id = ?');
                $stmt->execute([$code, $name, $credits, $semester, $teacher, $hours, $id]);
                $message = 'Cours mis à jour avec succès.';
            }
        }

        if ($action === 'delete_course') {
            $id = (int) ($_POST['course_id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare('DELETE FROM courses WHERE id = ?')->execute([$id]);
                $message = 'Cours supprimé avec succès.';
            }
        }
    }
}

$courses = $pdo->query('SELECT * FROM courses ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Gestion des cours</title>
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
          <li class="nav-item nav-date dropdown"><a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;"><h6 class="date mb-0">Gestion des cours</h6></a></li>
        </ul>
      </div>
    </nav>

    <div class="page-body-wrapper">
      <div class="main-panel w-100">
        <div class="content-wrapper">
          <div class="page-title">Gestion des cours</div>

          <?php if ($message !== ''): ?>
            <div class="alert alert-info mb-4"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>

          <div class="row g-4">
            <div class="col-xl-4">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">
                  <?= $editCourse ? 'Modifier un cours' : 'Ajouter un cours' ?>
                </h4>
                <form method="post">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="action" value="<?= $editCourse ? 'update_course' : 'create_course' ?>">
                  <?php if ($editCourse): ?>
                    <input type="hidden" name="course_id" value="<?= (int) $editCourse['id'] ?>">
                  <?php endif; ?>
                  <div class="mb-3"><label class="form-label">Code</label><input type="text" name="code" class="form-control" value="<?= htmlspecialchars($editCourse['code'] ?? '') ?>" required></div>
                  <div class="mb-3"><label class="form-label">Nom</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editCourse['name'] ?? '') ?>" required></div>
                  <div class="mb-3"><label class="form-label">Crédits</label><input type="number" name="credits" class="form-control" value="<?= htmlspecialchars((string) ($editCourse['credits'] ?? '')) ?>" required></div>
                  <div class="mb-3"><label class="form-label">Semestre</label><input type="text" name="semester" class="form-control" value="<?= htmlspecialchars($editCourse['semester'] ?? '') ?>" required></div>
                  <div class="mb-3"><label class="form-label">Enseignant</label><input type="text" name="teacher" class="form-control" value="<?= htmlspecialchars($editCourse['teacher'] ?? '') ?>" required></div>
                  <div class="mb-3"><label class="form-label">Heures</label><input type="number" name="hours" class="form-control" value="<?= htmlspecialchars((string) ($editCourse['hours'] ?? '')) ?>" required></div>
                  <button type="submit" class="btn btn-primary w-100"><?= $editCourse ? 'Enregistrer les modifications' : 'Enregistrer' ?></button>
                </form>
              </div>
            </div>

            <div class="col-xl-8">
              <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="mb-0" style="font-weight:800; color: var(--unitech-blue-dark);">Catalogue</h4>
                  <a href="/admin-dashboard.php" class="btn btn-light">Retour</a>
                </div>
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr><th>Code</th><th>Cours</th><th>Crédits</th><th>Semestre</th><th>Enseignant</th><th>Heures</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                      <?php foreach ($courses as $course): ?>
                        <tr>
                          <td><?= htmlspecialchars($course['code']) ?></td>
                          <td><?= htmlspecialchars($course['name']) ?></td>
                          <td><?= htmlspecialchars((string) $course['credits']) ?></td>
                          <td><?= htmlspecialchars($course['semester']) ?></td>
                          <td><?= htmlspecialchars($course['teacher']) ?></td>
                          <td><?= htmlspecialchars((string) $course['hours']) ?>h</td>
                          <td>
                            <div class="table-actions">
                              <a href="?edit_course=<?= (int) $course['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
                              <form method="post" onsubmit="return confirm('Supprimer ce cours ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_course">
                                <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
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
