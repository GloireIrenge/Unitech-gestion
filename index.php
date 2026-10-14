<?php
require __DIR__ . '/includes/auth.php';
$user = requireAuth();

if (strtolower((string) $user['profile']) === 'admin') {
    header('Location: /admin-dashboard.php');
    exit;
}
if (strtolower((string) $user['profile']) === 'finance') {
    header('Location: /finance.php');
    exit;
}
if (strtolower((string) $user['profile']) === 'teacher') {
    header('Location: /teacher-dashboard.php');
    exit;
}

require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();

$studentResults = $pdo->prepare('SELECT course_code, grade, status FROM results WHERE user_id = ? ORDER BY id DESC');
$studentResults->execute([$user['id']]);
$results = $studentResults->fetchAll();

$payments = $pdo->prepare('SELECT amount, status, description FROM payments WHERE user_id = ? ORDER BY id DESC');
$payments->execute([$user['id']]);
$paymentRows = $payments->fetchAll();

$courses = $pdo->query('SELECT code, name, credits, semester, teacher, hours FROM courses ORDER BY id LIMIT 4')->fetchAll();
$average = 0;
if ($results) {
    $average = array_sum(array_column($results, 'grade')) / count($results);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH – Système de Gestion Académique</title>
  <link rel="stylesheet" href="assets/vendors/typicons/typicons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="assets/images/favicon.ico" />
  <style>
    :root {
      --unitech-blue: #5d8cf4;
      --unitech-blue-dark: #1d3968;
      --unitech-yellow: #f4dc61;
      --unitech-navy: #112a50;
      --unitech-bg: #f2f6ff;
      --unitech-card: #ffffff;
      --unitech-border: rgba(28, 55, 99, 0.12);
      --unitech-text: #1e2a3b;
      --unitech-muted: #66758c;
      --unitech-success: #2fbf71;
      --unitech-warning: #f5b642;
      --unitech-danger: #ec5b5b;
    }
    body { background: var(--unitech-bg); color: var(--unitech-text); font-family: "Poppins", sans-serif; }
    .navbar { background: linear-gradient(135deg, var(--unitech-blue-dark) 0%, var(--unitech-navy) 100%); }
    .sidebar { background: rgba(255,255,255,0.8); border-right: 1px solid var(--unitech-border); }
    .page-body-wrapper { padding-top: 72px; }
    .content-wrapper { padding: 1.5rem 1.4rem 0; }
    .page-header { padding: 1.2rem 1.4rem; border-radius: 18px; background: linear-gradient(135deg, rgba(93,140,244,0.12), rgba(244,220,97,0.18)); }
    .metric-card, .university-card { border: 1px solid var(--unitech-border); border-radius: 18px; background: var(--unitech-card); box-shadow: 0 10px 28px rgba(17,42,80,0.05); }
    .student-card { position: relative; overflow: hidden; }
    .status-pill { display:inline-flex; padding:0.32rem 0.7rem; border-radius:999px; font-size:0.72rem; font-weight:700; }
    .status-pill.ok { background: rgba(47,191,113,0.12); color: var(--unitech-success); }
    .status-pill.pending { background: rgba(245,182,66,0.12); color: var(--unitech-warning); }
    .chip { display:inline-flex; padding:0.35rem 0.7rem; border-radius:999px; background: rgba(93,140,244,0.12); color: var(--unitech-blue-dark); font-size:0.72rem; font-weight:700; }
    .chip.accent { background: rgba(244,220,97,0.2); color: #7a6200; }
    .schedule-list, .notice-list, .request-list, .agenda-list { list-style:none; margin:0; padding:0; }
    .schedule-list li, .notice-list li, .request-list li { display:flex; justify-content:space-between; gap:1rem; padding:0.8rem 0; border-bottom:1px solid rgba(17,42,80,0.08); }
    .schedule-list li:last-child, .notice-list li:last-child, .request-list li:last-child { border-bottom:none; }
  </style>
</head>
<body>
  <div class="container-scroller">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="navbar-brand-wrapper d-flex justify-content-center">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">
          <a class="navbar-brand brand-logo" href="index.php"><img src="assets/images/logo.jpg" alt="logo" /></a>
          <a class="navbar-brand brand-logo-mini" href="index.php"><img src="assets/images/logo.jpg" alt="logo" /></a>
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
          <li class="nav-item"><a class="nav-link active" href="index.php"><i class="typcn typcn-home menu-icon"></i><span class="menu-title">Accueil</span></a></li>
          <li class="nav-item"><a class="nav-link" href="#resultats"><i class="typcn typcn-chart-bar menu-icon"></i><span class="menu-title">Résultats</span></a></li>
          <li class="nav-item"><a class="nav-link" href="#paiements"><i class="typcn typcn-credit-card menu-icon"></i><span class="menu-title">Paiements</span></a></li>
          <li class="nav-item"><a class="nav-link" href="#cours"><i class="typcn typcn-book menu-icon"></i><span class="menu-title">Cours</span></a></li>
          <li class="nav-item"><a class="nav-link" href="documents.php"><i class="typcn typcn-folder menu-icon"></i><span class="menu-title">Documents</span></a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php"><i class="typcn typcn-arrow-back menu-icon"></i><span class="menu-title">Déconnexion</span></a></li>
        </ul>
      </nav>

      <div class="main-panel">
        <div class="content-wrapper">
          <div class="page-header">
            <div>
              <div class="eyebrow">Portail étudiant</div>
              <h1>Tableau de bord académique</h1>
            </div>
            <div class="chip">Session active : <?= htmlspecialchars($user['profile']) ?></div>
          </div>

          <div class="row g-4 mb-4">
            <div class="col-xl-4">
              <div class="card university-card student-card h-100">
                <div class="card-body">
                  <div class="status-badge">Étudiant actif</div>
                  <div class="d-flex align-items-center gap-3">
                    <img class="student-avatar" src="assets/images/logo.jpg" alt="avatar">
                    <div>
                      <h3><?= htmlspecialchars($user['name']) ?></h3>
                      <p><?= htmlspecialchars($user['matricule']) ?></p>
                    </div>
                  </div>
                  <div class="student-meta">
                    <div><span>Programme</span><strong>Licence Informatique</strong></div>
                    <div><span>Niveau</span><strong>Semestre 5</strong></div>
                    <div><span>Année</span><strong>2026 - 2027</strong></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-8">
              <div class="row g-4">
                <div class="col-md-6 col-xl-3">
                  <div class="card metric-card h-100">
                    <div class="card-body">
                      <p class="metric-label">Moyenne</p>
                      <h3><?= number_format($average, 1, ',', ' ') ?></h3>
                      <small class="up">+2,1 % vs dernière période</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3">
                  <div class="card metric-card h-100">
                    <div class="card-body">
                      <p class="metric-label">Crédits</p>
                      <h3>120</h3>
                      <small class="up">+12 crédits acquis</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3">
                  <div class="card metric-card h-100">
                    <div class="card-body">
                      <p class="metric-label">Paiements</p>
                      <h3>91%</h3>
                      <small class="neutral">2 versements en attente</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-3">
                  <div class="card metric-card h-100">
                    <div class="card-body">
                      <p class="metric-label">Absences</p>
                      <h3>4</h3>
                      <small class="neutral">Aucun cas critique</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <section class="row g-4 mb-4" id="resultats">
            <div class="col-xl-8">
              <div class="card university-card h-100">
                <div class="card-header"><h4>Résultats académiques</h4><span class="chip">Dernière mise à jour</span></div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table mb-0">
                      <thead>
                        <tr>
                          <th>Cours</th>
                          <th>Note</th>
                          <th>Statut</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($results as $result): ?>
                          <tr>
                            <td><?= htmlspecialchars($result['course_code']) ?></td>
                            <td><?= htmlspecialchars((string) $result['grade']) ?></td>
                            <td><span class="status-pill ok"><?= htmlspecialchars($result['status']) ?></span></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-4">
              <div class="card university-card h-100">
                <div class="card-header"><h4>Notifications</h4><span class="chip accent">3 nouvelles</span></div>
                <div class="card-body">
                  <ul class="notice-list">
                    <li><div><strong>Inscription validée</strong><p>Votre semestre 5 a été validé.</p></div><span class="notice-tag">Info</span></li>
                    <li><div><strong>Rentrée académique</strong><p>La cérémonie a lieu le 12 novembre.</p></div><span class="notice-tag urgent">Urgent</span></li>
                    <li><div><strong>Résultats provisoires</strong><p>Disponibles sur le portail.</p></div><span class="notice-tag">Mise à jour</span></li>
                  </ul>
                </div>
              </div>
            </div>
          </section>

          <section class="row g-4" id="cours">
            <div class="col-xl-7">
              <div class="card university-card h-100">
                <div class="card-header"><h4>Cours suivis</h4><span class="chip">Semestre actuel</span></div>
                <div class="card-body">
                  <div class="course-list">
                    <?php foreach ($courses as $course): ?>
                      <div class="course-item">
                        <div>
                          <strong><?= htmlspecialchars($course['name']) ?></strong>
                          <p><?= htmlspecialchars($course['teacher']) ?> • <?= htmlspecialchars($course['semester']) ?></p>
                        </div>
                        <a href="#"><?= htmlspecialchars($course['code']) ?></a>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-5" id="paiements">
              <div class="card university-card h-100">
                <div class="card-header"><h4>Paiements</h4><span class="chip accent">3 transactions</span></div>
                <div class="card-body">
                  <div class="finance-summary">
                    <?php foreach ($paymentRows as $payment): ?>
                      <div>
                        <span><?= htmlspecialchars($payment['description']) ?></span>
                        <strong><?= number_format((float) $payment['amount'], 0, ',', ' ') ?> FC</strong>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="assets/js/off-canvas.js"></script>
  <script src="assets/js/hoverable-collapse.js"></script>
  <script src="assets/js/template.js"></script>
  <script src="assets/js/settings.js"></script>
  <script src="assets/js/todolist.js"></script>
  <script>
    const sessionUser = localStorage.getItem('unitechUser');
    if (!sessionUser) {
      const banner = document.createElement('div');
      banner.style.position = 'fixed';
      banner.style.right = '18px';
      banner.style.bottom = '18px';
      banner.style.padding = '12px 16px';
      banner.style.background = '#1d3968';
      banner.style.color = '#fff';
      banner.style.borderRadius = '12px';
      banner.style.fontFamily = 'Poppins, sans-serif';
      banner.innerHTML = 'Session PHP active';
      document.body.appendChild(banner);
    }
  </script>
</body>
</html>
