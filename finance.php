<?php
require __DIR__ . '/includes/auth.php';
requireAuth(['finance']);

require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();
$payments = $pdo->query('SELECT p.id, u.name, u.matricule, p.amount, p.status, p.description, p.created_at FROM payments p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC')->fetchAll();
$sum = array_sum(array_map(fn($row) => (float) $row['amount'], $payments));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Finance</title>
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
  </style>
</head>
<body>
  <div class="container-scroller">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="navbar-brand-wrapper d-flex justify-content-center">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">
          <a class="navbar-brand brand-logo" href="/finance.php"><img src="assets/images/logo.jpg" alt="logo" /></a>
          <a class="navbar-brand brand-logo-mini" href="/finance.php"><img src="assets/images/logo.jpg" alt="logo" /></a>
        </div>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item nav-date dropdown"><a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;"><h6 class="date mb-0">Finance UNITECH</h6></a></li>
        </ul>
      </div>
    </nav>

    <div class="page-body-wrapper">
      <div class="main-panel w-100">
        <div class="content-wrapper">
          <div class="page-title">Finance et recouvrement</div>

          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <div class="card p-3">
                <div class="label text-muted">Montant total</div>
                <h3><?= number_format((float) $sum, 0, ',', ' ') ?> FC</h3>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card p-3">
                <div class="label text-muted">Transactions</div>
                <h3><?= count($payments) ?></h3>
              </div>
            </div>
          </div>

          <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h4 class="mb-0" style="font-weight:800; color: var(--unitech-blue-dark);">Historique</h4>
              <a href="/logout.php" class="btn btn-light">Déconnexion</a>
            </div>
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr><th>Étudiant</th><th>Matricule</th><th>Montant</th><th>Statut</th><th>Description</th><th>Date</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($payments as $payment): ?>
                    <tr>
                      <td><?= htmlspecialchars($payment['name']) ?></td>
                      <td><?= htmlspecialchars($payment['matricule']) ?></td>
                      <td><?= number_format((float) $payment['amount'], 0, ',', ' ') ?> FC</td>
                      <td><?= htmlspecialchars($payment['status']) ?></td>
                      <td><?= htmlspecialchars($payment['description']) ?></td>
                      <td><?= htmlspecialchars($payment['created_at']) ?></td>
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
</body>
</html>
