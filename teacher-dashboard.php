<?php
require __DIR__ . '/includes/auth.php';
$user = requireAuth(['teacher']);

require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();

$courseCount = (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$studentCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE profile = 'student'")->fetchColumn();
$results = $pdo->query('SELECT r.id, u.name AS student_name, u.matricule, r.course_code, r.grade, r.status FROM results r JOIN users u ON u.id = r.user_id ORDER BY r.id DESC LIMIT 6')->fetchAll();
$courses = $pdo->query('SELECT * FROM courses ORDER BY id DESC LIMIT 5')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Espace enseignant</title>
  <link rel="stylesheet" href="assets/vendors/typicons/typicons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    :root { --unitech-blue: #5d8cf4; --unitech-blue-dark: #1d3968; --unitech-yellow: #f4dc61; --unitech-navy: #112a50; --unitech-bg: #f4f7ff; --unitech-card:#fff; --unitech-text:#1d2b3a; --unitech-muted:#66758c; }
    body { background: radial-gradient(circle at top, rgba(93,140,244,0.12), rgba(244,220,97,0.06) 25%, var(--unitech-bg) 60%); color: var(--unitech-text); font-family: "Poppins", sans-serif; }
    .page-body-wrapper { padding-top: 72px; }
    .content-wrapper { padding: 1.5rem; }
    .page-title { font-size: 2rem; font-weight: 800; color: var(--unitech-blue-dark); margin-bottom: 1rem; }
    .card { border:none; border-radius:18px; box-shadow:0 10px 24px rgba(17,42,80,0.06); }
    .dashboard-hero { display:flex; justify-content:space-between; align-items:center; gap:1rem; background: linear-gradient(135deg, rgba(93,140,244,0.12), rgba(244,220,97,0.18)); border:1px solid rgba(29,57,104,0.08); border-radius:20px; padding:1.3rem 1.5rem; margin-bottom:1.5rem; }
    .dashboard-hero h2 { margin:0; color: var(--unitech-blue-dark); font-size: 1.6rem; font-weight:800; }
    .dashboard-hero p { margin:0.25rem 0 0; color: var(--unitech-muted); }
    .profile-chip { display:inline-flex; align-items:center; gap:0.55rem; padding:0.55rem 0.9rem; border-radius:999px; background: rgba(17,42,80,0.06); color: var(--unitech-blue-dark); font-weight:700; }
    .btn-primary { background: linear-gradient(135deg, var(--unitech-blue), var(--unitech-blue-dark)); border:none; border-radius:12px; box-shadow: 0 10px 18px rgba(93,140,244,0.28); }
    .btn-primary:hover { filter: brightness(1.03); }
    .metric-card .card-body { padding:1.1rem; }
    .metric-card h3 { font-size: 2.1rem; font-weight: 800; color: var(--unitech-blue-dark); margin-top: 0.5rem; }
    .list-group-item { border-color: rgba(17,42,80,0.08); }
  </style>
</head>
<body>
  <div class="container-scroller">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="navbar-brand-wrapper d-flex justify-content-center">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">
          <a class="navbar-brand brand-logo" href="/teacher-dashboard.php"><img src="assets/images/logo.jpg" alt="logo" /></a>
          <a class="navbar-brand brand-logo-mini" href="/teacher-dashboard.php"><img src="assets/images/logo.jpg" alt="logo" /></a>
        </div>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item nav-date dropdown"><a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;"><h6 class="date mb-0">Espace enseignant</h6></a></li>
        </ul>
      </div>
    </nav>

    <div class="page-body-wrapper">
      <div class="main-panel w-100">
        <div class="content-wrapper">
          <div class="page-title">Bienvenue, <?= htmlspecialchars($user['name']) ?></div>

          <div class="dashboard-hero">
            <div>
              <h2>Tableau de bord enseignant</h2>
              <p>Suivi des performances, cours et évaluations de la cohorte active.</p>
            </div>
            <div class="profile-chip"><i class="typcn typcn-group"></i> Professeur</div>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="text-muted text-uppercase small">Étudiants</div>
                  <h3><?= $studentCount ?></h3>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="text-muted text-uppercase small">Cours</div>
                  <h3><?= $courseCount ?></h3>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="text-muted text-uppercase small">Notes saisies</div>
                  <h3><?= count($results) ?></h3>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="text-muted text-uppercase small">Niveau</div>
                  <h3>Licence</h3>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-xl-7">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">Derniers résultats</h4>
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead><tr><th>Étudiant</th><th>Matricule</th><th>Cours</th><th>Note</th><th>Statut</th></tr></thead>
                    <tbody>
                      <?php foreach ($results as $row): ?>
                        <tr>
                          <td><?= htmlspecialchars($row['student_name']) ?></td>
                          <td><?= htmlspecialchars($row['matricule']) ?></td>
                          <td><?= htmlspecialchars($row['course_code']) ?></td>
                          <td><?= htmlspecialchars((string) $row['grade']) ?></td>
                          <td><?= htmlspecialchars($row['status']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-xl-5">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">Cours dispensés</h4>
                <ul class="list-group list-group-flush">
                  <?php foreach ($courses as $course): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span><?= htmlspecialchars($course['name']) ?></span>
                      <span class="badge bg-light text-dark"><?= htmlspecialchars($course['code']) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
                <div class="mt-3">
                  <a href="/results.php" class="btn btn-primary w-100">Gérer les évaluations</a>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <a href="/logout.php" class="btn btn-light">Déconnexion</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
