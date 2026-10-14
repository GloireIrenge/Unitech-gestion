<?php
require __DIR__ . '/includes/auth.php';
requireAuth(['admin']);

require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();
$message = '';
$csrfToken = generateCsrfToken();
$editPayment = null;

$editPaymentId = filter_input(INPUT_GET, 'edit_payment', FILTER_VALIDATE_INT);
if ($editPaymentId) {
    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
    $stmt->execute([$editPaymentId]);
    $editPayment = $stmt->fetch();
}

if (isset($_GET['export']) && $_GET['export'] === '1') {
    $rows = $pdo->query('SELECT u.name, u.matricule, p.amount, p.status, p.description, p.created_at FROM payments p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC')->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="unitech-paiements.csv"');
    echo "Nom,Matricule,Montant,Statut,Description,Date\n";
    foreach ($rows as $row) {
        echo '"' . str_replace('"', '""', $row['name']) . '",';
        echo '"' . str_replace('"', '""', $row['matricule']) . '",';
        echo $row['amount'] . ',';
        echo '"' . str_replace('"', '""', $row['status']) . '",';
        echo '"' . str_replace('"', '""', $row['description']) . '",';
        echo '"' . str_replace('"', '""', $row['created_at']) . "\n";
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Token de sécurité invalide.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_payment') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $amount = (float) ($_POST['amount'] ?? 0);
            $status = sanitizeText($_POST['status'] ?? '');
            $description = sanitizeText($_POST['description'] ?? '');

            if ($userId <= 0 || $amount <= 0 || $status === '' || $description === '') {
                $message = 'Tous les champs du paiement doivent être remplis.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO payments (user_id, amount, status, description) VALUES (?, ?, ?, ?)');
                $stmt->execute([$userId, $amount, $status, $description]);
                $message = 'Paiement enregistré avec succès.';
            }
        }

        if ($action === 'update_payment') {
            $id = (int) ($_POST['payment_id'] ?? 0);
            $userId = (int) ($_POST['user_id'] ?? 0);
            $amount = (float) ($_POST['amount'] ?? 0);
            $status = sanitizeText($_POST['status'] ?? '');
            $description = sanitizeText($_POST['description'] ?? '');

            if ($id <= 0 || $userId <= 0 || $amount <= 0 || $status === '' || $description === '') {
                $message = 'Les informations du paiement sont incomplètes.';
            } else {
                $stmt = $pdo->prepare('UPDATE payments SET user_id = ?, amount = ?, status = ?, description = ? WHERE id = ?');
                $stmt->execute([$userId, $amount, $status, $description, $id]);
                $message = 'Paiement mis à jour avec succès.';
            }
        }

        if ($action === 'delete_payment') {
            $id = (int) ($_POST['payment_id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare('DELETE FROM payments WHERE id = ?')->execute([$id]);
                $message = 'Paiement supprimé avec succès.';
            }
        }
    }
}

$students = $pdo->query("SELECT id, name, matricule FROM users WHERE profile = 'student' ORDER BY id DESC")->fetchAll();
$payments = $pdo->query('SELECT p.id, u.name, u.matricule, p.amount, p.status, p.description, p.created_at FROM payments p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Gestion des paiements</title>
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
          <li class="nav-item nav-date dropdown"><a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;"><h6 class="date mb-0">Gestion des paiements</h6></a></li>
        </ul>
      </div>
    </nav>

    <div class="page-body-wrapper">
      <div class="main-panel w-100">
        <div class="content-wrapper">
          <div class="page-title">Gestion des paiements</div>

          <?php if ($message !== ''): ?>
            <div class="alert alert-info mb-4"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>

          <div class="row g-4 mb-4">
            <div class="col-12">
              <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <h4 class="mb-0" style="font-weight:800; color: var(--unitech-blue-dark);">Actions</h4>
                  <div class="d-flex gap-2 flex-wrap">
                    <a href="/admin-dashboard.php" class="btn btn-light">Retour</a>
                    <a href="?export=1" class="btn btn-primary">Exporter CSV</a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row g-4">
            <div class="col-xl-4">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">
                  <?= $editPayment ? 'Modifier un paiement' : 'Enregistrer un paiement' ?>
                </h4>
                <form method="post">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="action" value="<?= $editPayment ? 'update_payment' : 'create_payment' ?>">
                  <?php if ($editPayment): ?>
                    <input type="hidden" name="payment_id" value="<?= (int) $editPayment['id'] ?>">
                  <?php endif; ?>
                  <div class="mb-3">
                    <label class="form-label">Étudiant</label>
                    <select name="user_id" class="form-select" required>
                      <?php foreach ($students as $student): ?>
                        <option value="<?= (int) $student['id'] ?>" <?= (($editPayment['user_id'] ?? '') == $student['id']) ? 'selected' : '' ?>><?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['matricule']) ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Montant</label>
                    <input type="number" name="amount" class="form-control" min="0" step="100" value="<?= htmlspecialchars((string) ($editPayment['amount'] ?? '')) ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select" required>
                      <?php $selectedStatus = $editPayment['status'] ?? 'Payé'; ?>
                      <option value="Payé" <?= $selectedStatus === 'Payé' ? 'selected' : '' ?>>Payé</option>
                      <option value="En attente" <?= $selectedStatus === 'En attente' ? 'selected' : '' ?>>En attente</option>
                      <option value="Annulé" <?= $selectedStatus === 'Annulé' ? 'selected' : '' ?>>Annulé</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($editPayment['description'] ?? '') ?>" required>
                  </div>
                  <button type="submit" class="btn btn-primary w-100"><?= $editPayment ? 'Enregistrer les modifications' : 'Enregistrer' ?></button>
                </form>
              </div>
            </div>

            <div class="col-xl-8">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">Historique</h4>
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr><th>Étudiant</th><th>Matricule</th><th>Montant</th><th>Statut</th><th>Description</th><th>Date</th><th>Actions</th></tr>
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
                          <td>
                            <div class="table-actions">
                              <a href="?edit_payment=<?= (int) $payment['id'] ?>" class="btn btn-sm btn-primary">Modifier</a>
                              <form method="post" onsubmit="return confirm('Supprimer ce paiement ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_payment">
                                <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
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
