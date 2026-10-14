<?php
require __DIR__ . '/includes/auth.php';
requireAuth(['admin']);

require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();

$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE profile = 'student'")->fetchColumn();
$totalTeachers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE profile = 'teacher'")->fetchColumn();
$totalCourses = (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$totalDocuments = (int) $pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn();
$sumPayments = (float) $pdo->query('SELECT COALESCE(SUM(amount), 0) FROM payments')->fetchColumn();
$avgGrade = (float) $pdo->query('SELECT COALESCE(AVG(grade), 0) FROM results')->fetchColumn();
$pendingPayments = (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'En attente'")->fetchColumn();
$recentResults = $pdo->query('SELECT u.name, u.matricule, r.course_code, r.grade, r.status FROM results r JOIN users u ON u.id = r.user_id ORDER BY r.id DESC LIMIT 5')->fetchAll();
$recentPayments = $pdo->query('SELECT u.name, u.matricule, p.amount, p.status FROM payments p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 5')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Rapports</title>
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
    .metric-card .card-body { padding:1.25rem; }
    .metric-card h3 { font-size: 2rem; font-weight: 800; margin-top:0.5rem; color: var(--unitech-blue-dark); }
    .btn-primary { background: linear-gradient(135deg, var(--unitech-blue), var(--unitech-blue-dark)); border:none; border-radius:12px; }
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
          <li class="nav-item nav-date dropdown"><a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;"><h6 class="date mb-0">Rapports académiques</h6></a></li>
        </ul>
      </div>
    </nav>

    <div class="page-body-wrapper">
      <div class="main-panel w-100">
        <div class="content-wrapper">
          <div class="page-title">Rapports et synthèses</div>

          <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="text-muted text-uppercase small">Étudiants</div>
                  <h3><?= $totalStudents ?></h3>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="text-muted text-uppercase small">Enseignants</div>
                  <h3><?= $totalTeachers ?></h3>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="text-muted text-uppercase small">Cours</div>
                  <h3><?= $totalCourses ?></h3>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="text-muted text-uppercase small">Moyenne</div>
                  <h3><?= number_format($avgGrade, 1, ',', ' ') ?></h3>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-xl-6">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">Derniers résultats</h4>
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead><tr><th>Étudiant</th><th>Matricule</th><th>Cours</th><th>Note</th></tr></thead>
                    <tbody>
                      <?php foreach ($recentResults as $row): ?>
                        <tr>
                          <td><?= htmlspecialchars($row['name']) ?></td>
                          <td><?= htmlspecialchars($row['matricule']) ?></td>
                          <td><?= htmlspecialchars($row['course_code']) ?></td>
                          <td><?= htmlspecialchars((string) $row['grade']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="col-xl-6">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">Finance</h4>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item d-flex justify-content-between"><span>Montant total</span><strong><?= number_format((float) $sumPayments, 0, ',', ' ') ?> FC</strong></li>
                  <li class="list-group-item d-flex justify-content-between"><span>Versements en attente</span><strong><?= $pendingPayments ?></strong></li>
                  <li class="list-group-item d-flex justify-content-between"><span>Documents</span><strong><?= $totalDocuments ?></strong></li>
                </ul>
                <div class="mt-4">
                  <h5 class="mb-2" style="font-weight:800; color: var(--unitech-blue-dark);">Dernières transactions</h5>
                  <div class="table-responsive">
                    <table class="table align-middle mb-0">
                      <thead><tr><th>Étudiant</th><th>Matricule</th><th>Montant</th></tr></thead>
                      <tbody>
                        <?php foreach ($recentPayments as $row): ?>
                          <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['matricule']) ?></td>
                            <td><?= number_format((float) $row['amount'], 0, ',', ' ') ?> FC</td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4">
            <a href="/admin-dashboard.php" class="btn btn-light">Retour</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
