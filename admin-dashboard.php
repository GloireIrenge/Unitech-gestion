<?php
require __DIR__ . '/includes/auth.php';
requireAuth(['admin']);

require __DIR__ . '/includes/db.php';$pdo = getDbConnection();
$studentCount = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE profile = "student"')->fetchColumn();
$teacherCount = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE profile = "teacher"')->fetchColumn();
$paymentCount = (int) $pdo->query('SELECT COUNT(*) FROM payments')->fetchColumn();
$courseCount = (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$recentUsers = $pdo->query('SELECT name, email, profile, matricule FROM users ORDER BY id DESC LIMIT 5')->fetchAll();
$latestPayments = $pdo->query('SELECT p.amount, p.status, u.name FROM payments p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 5')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Administration</title>
  <link rel="stylesheet" href="../assets/vendors/typicons/typicons.css">
  <link rel="stylesheet" href="../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="shortcut icon" href="../assets/images/favicon.ico" />
  <style>
    :root { --unitech-blue: #5d8cf4; --unitech-blue-dark: #1d3968; --unitech-yellow: #f4dc61; --unitech-navy: #112a50; --unitech-bg: #f4f7ff; --unitech-card:#fff; --unitech-text:#1d2b3a; --unitech-muted:#66758c; --unitech-success:#2fbf71; --unitech-warning:#f5b642; --unitech-danger:#ec5b5b; }
    body { background: radial-gradient(circle at top, rgba(93,140,244,0.12), rgba(244,220,97,0.06) 25%, var(--unitech-bg) 60%); color: var(--unitech-text); font-family: "Poppins", sans-serif; }
    .navbar { background: linear-gradient(135deg, var(--unitech-blue-dark), var(--unitech-navy)); }
    .sidebar { background: rgba(255,255,255,0.8); border-right: 1px solid rgba(29,57,104,0.08); }
    .page-body-wrapper { padding-top: 72px; }
    .content-wrapper { padding: 1.5rem; }
    .page-title { font-size: 2rem; font-weight: 800; color: var(--unitech-blue-dark); margin-bottom: 1rem; }
    .card { border: none; border-radius: 18px; box-shadow: 0 10px 24px rgba(17,42,80,0.06); }
    .dashboard-hero { display:flex; justify-content:space-between; align-items:center; gap:1rem; background: linear-gradient(135deg, rgba(93,140,244,0.12), rgba(244,220,97,0.18)); border:1px solid rgba(29,57,104,0.08); border-radius:20px; padding:1.3rem 1.5rem; margin-bottom:1.5rem; }
    .dashboard-hero h2 { margin:0; color: var(--unitech-blue-dark); font-size: 1.6rem; font-weight:800; }
    .dashboard-hero p { margin:0.25rem 0 0; color: var(--unitech-muted); }
    .profile-chip { display:inline-flex; align-items:center; gap:0.55rem; padding:0.55rem 0.9rem; border-radius:999px; background: rgba(17,42,80,0.06); color: var(--unitech-blue-dark); font-weight:700; }
    .metric-card .card-body { padding: 1.3rem; }
    .metric-card .label { font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--unitech-muted); }
    .metric-card h3 { font-size: 2rem; font-weight: 800; margin: 0.4rem 0; color: var(--unitech-blue-dark); }
    .status-pill { padding: 0.35rem 0.7rem; border-radius:999px; font-size:0.75rem; font-weight:700; }
    .status-ok { background: rgba(47,191,113,0.12); color: var(--unitech-success); }
    .status-warn { background: rgba(245,182,66,0.12); color: var(--unitech-warning); }
    .chip { display:inline-flex; padding:0.35rem 0.7rem; background: rgba(93,140,244,0.12); border-radius:999px; color: var(--unitech-blue-dark); font-size:0.72rem; font-weight:700; }
    .btn-primary { background: linear-gradient(135deg, var(--unitech-blue), var(--unitech-blue-dark)); border:none; border-radius:12px; box-shadow: 0 10px 18px rgba(93,140,244,0.28); }
    .btn-primary:hover { filter: brightness(1.03); }
  </style>
</head>
<body>
  <div class="container-scroller">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="navbar-brand-wrapper d-flex justify-content-center">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">
          <a class="navbar-brand brand-logo" href="/index.php"><img src="../assets/images/logo.jpg" alt="logo" /></a>
          <a class="navbar-brand brand-logo-mini" href="/index.php"><img src="../assets/images/logo.jpg" alt="logo" /></a>
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize"><span class="typcn typcn-th-menu"></span></button>
        </div>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item nav-date dropdown">
            <a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;">
              <h6 class="date mb-0">Aujourd'hui : <?= date('d M Y') ?></h6>
              <i class="typcn typcn-calendar"></i>
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <div class="page-body-wrapper">
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item"><a class="nav-link active" href="admin-dashboard.php"><i class="typcn typcn-device-desktop menu-icon"></i><span class="menu-title">Dashboard</span></a></li>
          <li class="nav-item"><a class="nav-link" href="/students.php"><i class="typcn typcn-user menu-icon"></i><span class="menu-title">Étudiants</span></a></li>
          <li class="nav-item"><a class="nav-link" href="/courses.php"><i class="typcn typcn-book menu-icon"></i><span class="menu-title">Cours</span></a></li>
          <li class="nav-item"><a class="nav-link" href="/results.php"><i class="typcn typcn-chart-bar menu-icon"></i><span class="menu-title">Résultats</span></a></li>
          <li class="nav-item"><a class="nav-link" href="/payments.php"><i class="typcn typcn-credit-card menu-icon"></i><span class="menu-title">Paiements</span></a></li>
          <li class="nav-item"><a class="nav-link" href="/documents.php"><i class="typcn typcn-folder menu-icon"></i><span class="menu-title">Documents</span></a></li>
          <li class="nav-item"><a class="nav-link" href="/import.php"><i class="typcn typcn-download menu-icon"></i><span class="menu-title">Import Excel</span></a></li>
          <li class="nav-item"><a class="nav-link" href="/reports.php"><i class="typcn typcn-document-text menu-icon"></i><span class="menu-title">Rapports</span></a></li>
          <li class="nav-item"><a class="nav-link" href="/index.php"><i class="typcn typcn-arrow-back menu-icon"></i><span class="menu-title">Retour étudiant</span></a></li>
          <li class="nav-item"><a class="nav-link" href="/logout.php"><i class="typcn typcn-eject menu-icon"></i><span class="menu-title">Déconnexion</span></a></li>
        </ul>
      </nav>

      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-title">Administration UNITECH</div>

          <div class="dashboard-hero">
            <div>
              <h2>Centre de contrôle académique</h2>
              <p>Suivi des étudiants, résultats, finances et import de données.</p>
            </div>
            <div class="profile-chip"><i class="typcn typcn-bell"></i> Campus numérique</div>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="label">Étudiants</div>
                  <h3><?= $studentCount ?></h3>
                  <span class="status-ok">+6.2% ce trimestre</span>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="label">Enseignants</div>
                  <h3><?= $teacherCount ?></h3>
                  <span class="status-ok">+18 nouveaux</span>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="label">Cours</div>
                  <h3><?= $courseCount ?></h3>
                  <span class="status-warn">12 en préparation</span>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-xl-3">
              <div class="card metric-card h-100">
                <div class="card-body">
                  <div class="label">Paiements</div>
                  <h3><?= $paymentCount ?></h3>
                  <span class="status-ok">Règlements validés</span>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-12">
              <div class="card p-3">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                  <h4 class="mb-0" style="font-weight:800; color: var(--unitech-blue-dark);">Actions rapides</h4>
                  <div class="d-flex flex-wrap gap-2">
                    <a href="/students.php" class="btn btn-primary">Gérer étudiants</a>
                    <a href="/courses.php" class="btn btn-primary">Gérer cours</a>
                    <a href="/results.php" class="btn btn-primary">Gérer résultats</a>
                    <a href="/payments.php" class="btn btn-primary">Gérer paiements</a>
                    <a href="/documents.php" class="btn btn-primary">Documents</a>
                    <a href="/import.php" class="btn btn-primary">Import Excel</a>
                    <a href="/reports.php" class="btn btn-primary">Rapports</a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-xl-8">
              <div class="card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0" style="font-weight:800; color: var(--unitech-blue-dark);">Utilisateurs récents</h4>
                    <span class="chip">Mise à jour automatique</span>
                  </div>
                  <div class="table-responsive">
                    <table class="table align-middle mb-0">
                      <thead>
                        <tr>
                          <th>Nom</th>
                          <th>Email</th>
                          <th>Profil</th>
                          <th>Matricule</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                          <tr>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['profile']) ?></td>
                            <td><?= htmlspecialchars($user['matricule']) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0" style="font-weight:800; color: var(--unitech-blue-dark);">Paiements</h4>
                    <span class="chip">Journal</span>
                  </div>
                  <ul class="list-unstyled mb-0">
                    <?php foreach ($latestPayments as $payment): ?>
                      <li class="mb-3 border-bottom pb-2">
                        <div class="d-flex justify-content-between"><strong><?= htmlspecialchars($payment['name']) ?></strong><span class="status-pill status-ok"><?= htmlspecialchars($payment['status']) ?></span></div>
                        <div class="text-muted small"><?= number_format((float) $payment['amount'], 0, ',', ' ') ?> FC</div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
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
