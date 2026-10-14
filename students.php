<?php
require __DIR__ . '/includes/auth.php';
requireAuth(['admin']);

require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();
$message = '';
$csrfToken = generateCsrfToken();
$editStudent = null;

$editStudentId = filter_input(INPUT_GET, 'edit_student', FILTER_VALIDATE_INT);
if ($editStudentId) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$editStudentId]);
    $editStudent = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Token de sécurité invalide.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_student') {
            $prenom = sanitizeText($_POST['prenom'] ?? '');
            $nom = sanitizeText($_POST['nom'] ?? '');
            $email = sanitizeEmail($_POST['email'] ?? '');
            $profile = strtolower(sanitizeText($_POST['profil'] ?? 'student'));
            $password = $_POST['password'] ?? '';
            $name = trim($prenom . ' ' . $nom);

            if ($name === '' || $email === '' || $password === '') {
                $message = 'Tous les champs requis doivent être remplis.';
            } else {
                try {
                    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, profile, matricule) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $name,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $profile,
                        'UNT-' . date('Y') . '-' . rand(1000, 9999)
                    ]);
                    $message = 'Compte ajouté avec succès.';
                } catch (Throwable $e) {
                    $message = 'Cette adresse e-mail est déjà utilisée.';
                }
            }
        }

        if ($action === 'update_student') {
            $id = (int) ($_POST['user_id'] ?? 0);
            $name = sanitizeText($_POST['name'] ?? '');
            $email = sanitizeEmail($_POST['email'] ?? '');
            $profile = strtolower(sanitizeText($_POST['profile'] ?? 'student'));
            $matricule = sanitizeText($_POST['matricule'] ?? '');
            $password = trim((string) ($_POST['password'] ?? ''));

            if ($id <= 0 || $name === '' || $email === '' || $matricule === '') {
                $message = 'Les informations de l’utilisateur sont incomplètes.';
            } else {
                $sql = 'UPDATE users SET name = ?, email = ?, profile = ?, matricule = ?';
                $params = [$name, $email, $profile, $matricule];
                if ($password !== '') {
                    $sql .= ', password_hash = ?';
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }
                $sql .= ' WHERE id = ?';
                $params[] = $id;

                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $message = 'Utilisateur mis à jour avec succès.';
                } catch (Throwable $e) {
                    $message = 'Une erreur est survenue lors de la mise à jour.';
                }
            }
        }

        if ($action === 'delete_student') {
            $id = (int) ($_POST['user_id'] ?? 0);
            if ($id <= 0) {
                $message = 'Utilisateur introuvable.';
            } else {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
                $message = 'Utilisateur supprimé avec succès.';
            }
        }
    }
}

$students = $pdo->query("SELECT * FROM users WHERE profile IN ('student', 'teacher', 'finance', 'admin') ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Gestion des utilisateurs</title>
  <link rel="stylesheet" href="assets/vendors/typicons/typicons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    :root { --unitech-blue: #5d8cf4; --unitech-blue-dark: #1d3968; --unitech-yellow: #f4dc61; --unitech-navy: #112a50; --unitech-bg: #f4f7ff; --unitech-card:#fff; --unitech-text:#1d2b3a; --unitech-muted:#66758c; }
    body { background: var(--unitech-bg); color: var(--unitech-text); font-family: "Poppins", sans-serif; }
    .container-scroller { min-height: 100vh; }
    .navbar { background: linear-gradient(135deg, var(--unitech-blue-dark), var(--unitech-navy)); }
    .page-body-wrapper { padding-top: 72px; }
    .content-wrapper { padding: 1.5rem; }
    .page-title { font-size: 2rem; font-weight: 800; color: var(--unitech-blue-dark); margin-bottom: 1rem; }
    .card { border:none; border-radius:18px; box-shadow:0 10px 24px rgba(17,42,80,0.06); }
    .btn-primary { background: linear-gradient(135deg, var(--unitech-blue), var(--unitech-blue-dark)); border:none; border-radius:12px; }
    .status-pill { display:inline-flex; padding:0.35rem 0.7rem; border-radius:999px; font-size:0.75rem; font-weight:700; }
    .status-student { background: rgba(93,140,244,0.12); color: var(--unitech-blue-dark); }
    .status-admin { background: rgba(244,220,97,0.18); color:#775c00; }
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
          <li class="nav-item nav-date dropdown"><a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;"><h6 class="date mb-0">Gestion des utilisateurs</h6></a></li>
        </ul>
      </div>
    </nav>

    <div class="page-body-wrapper">
      <div class="main-panel w-100">
        <div class="content-wrapper">
          <div class="page-title">Gestion des utilisateurs</div>

          <?php if ($message !== ''): ?>
            <div class="alert alert-info mb-4"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>

          <div class="row g-4">
            <div class="col-xl-4">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">
                  <?= $editStudent ? 'Modifier le compte' : 'Ajouter un compte' ?>
                </h4>
                <form method="post">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="action" value="<?= $editStudent ? 'update_student' : 'create_student' ?>">
                  <?php if ($editStudent): ?>
                    <input type="hidden" name="user_id" value="<?= (int) $editStudent['id'] ?>">
                  <?php endif; ?>
                  <?php if (!$editStudent): ?>
                    <div class="mb-3">
                      <label class="form-label">Nom</label>
                      <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Prénom</label>
                      <input type="text" name="prenom" class="form-control" required>
                    </div>
                  <?php else: ?>
                    <div class="mb-3">
                      <label class="form-label">Nom complet</label>
                      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editStudent['name']) ?>" required>
                    </div>
                  <?php endif; ?>
                  <div class="mb-3">
                    <label class="form-label">Adresse e-mail</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editStudent['email'] ?? '') ?>" required>
                  </div>
                  <?php if ($editStudent): ?>
                    <div class="mb-3">
                      <label class="form-label">Matricule</label>
                      <input type="text" name="matricule" class="form-control" value="<?= htmlspecialchars($editStudent['matricule']) ?>" required>
                    </div>
                  <?php endif; ?>
                  <div class="mb-3">
                    <label class="form-label">Profil</label>
                    <select name="profile" class="form-select">
                      <option value="student" <?= (($editStudent['profile'] ?? 'student') === 'student') ? 'selected' : '' ?>>Étudiant</option>
                      <option value="teacher" <?= (($editStudent['profile'] ?? '') === 'teacher') ? 'selected' : '' ?>>Enseignant</option>
                      <option value="finance" <?= (($editStudent['profile'] ?? '') === 'finance') ? 'selected' : '' ?>>Finances</option>
                      <option value="admin" <?= (($editStudent['profile'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrateur</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Mot de passe <?= $editStudent ? '(laisser vide pour conserver)' : '' ?></label>
                    <input type="password" name="password" class="form-control" <?= $editStudent ? '' : 'required' ?>>
                  </div>
                  <button type="submit" class="btn btn-primary w-100"><?= $editStudent ? 'Enregistrer les changements' : 'Enregistrer' ?></button>
                </form>
              </div>
            </div>

            <div class="col-xl-8">
              <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="mb-0" style="font-weight:800; color: var(--unitech-blue-dark);">Utilisateurs</h4>
                  <a href="/admin-dashboard.php" class="btn btn-light">Retour</a>
                </div>
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Profil</th>
                        <th>Matricule</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($students as $student): ?>
                        <tr>
                          <td><?= htmlspecialchars($student['name']) ?></td>
                          <td><?= htmlspecialchars($student['email']) ?></td>
                          <td><span class="status-pill <?= strtolower($student['profile']) === 'admin' ? 'status-admin' : 'status-student' ?>"><?= htmlspecialchars($student['profile']) ?></span></td>
                          <td><?= htmlspecialchars($student['matricule']) ?></td>
                          <td>
                            <div class="table-actions">
                              <a href="?edit_student=<?= (int) $student['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
                              <form method="post" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_student">
                                <input type="hidden" name="user_id" value="<?= (int) $student['id'] ?>">
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
