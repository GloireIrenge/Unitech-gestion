<?php
require __DIR__ . '/includes/auth.php';
$user = requireAuth(['admin', 'student', 'teacher', 'finance']);

require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();
$message = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Token de sécurité invalide.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_document') {
        $documentId = (int) ($_POST['document_id'] ?? 0);
        if ($documentId > 0) {
            $stmt = $pdo->prepare('SELECT file_name, user_id FROM documents WHERE id = ? LIMIT 1');
            $stmt->execute([$documentId]);
            $document = $stmt->fetch();
            if ($document && (strtolower($user['profile']) === 'admin' || (int) $document['user_id'] === (int) $user['id'])) {
                $filePath = __DIR__ . '/uploads/' . $document['file_name'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $pdo->prepare('DELETE FROM documents WHERE id = ?')->execute([$documentId]);
                $message = 'Document supprimé avec succès.';
            }
        }
    } elseif (isset($_FILES['document'])) {
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $file = $_FILES['document'];
        if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName = uniqid('doc_', true) . '.' . $extension;
            $target = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $stmt = $pdo->prepare('INSERT INTO documents (user_id, file_name, original_name, mime_type, size) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([
                    $user['id'],
                    $safeName,
                    $file['name'],
                    $file['type'] ?: 'application/octet-stream',
                    (int) $file['size']
                ]);
                $message = 'Document téléchargé avec succès.';
            } else {
                $message = 'Le document n’a pas pu être enregistré.';
            }
        } else {
            $message = 'Une erreur est survenue lors du téléchargement.';
        }
    }
}

if (strtolower($user['profile']) === 'admin') {
    $documents = $pdo->query('SELECT d.*, u.name FROM documents d JOIN users u ON u.id = d.user_id ORDER BY d.id DESC')->fetchAll();
} else {
    $documents = $pdo->prepare('SELECT d.*, u.name FROM documents d JOIN users u ON u.id = d.user_id WHERE d.user_id = ? ORDER BY d.id DESC');
    $documents->execute([$user['id']]);
    $documents = $documents->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Documents</title>
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
          <a class="navbar-brand brand-logo" href="/index.php"><img src="assets/images/logo.jpg" alt="logo" /></a>
          <a class="navbar-brand brand-logo-mini" href="/index.php"><img src="assets/images/logo.jpg" alt="logo" /></a>
        </div>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item nav-date dropdown"><a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;"><h6 class="date mb-0">Documents académiques</h6></a></li>
        </ul>
      </div>
    </nav>

    <div class="page-body-wrapper">
      <div class="main-panel w-100">
        <div class="content-wrapper">
          <div class="page-title">Documents académiques</div>

          <?php if ($message !== ''): ?>
            <div class="alert alert-info mb-4"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>

          <div class="row g-4">
            <div class="col-xl-4">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">Téléverser un document</h4>
                <form method="post" enctype="multipart/form-data">
                  <div class="mb-3">
                    <label class="form-label">Fichier</label>
                    <input type="file" name="document" class="form-control" required>
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Téléverser</button>
                </form>
              </div>
            </div>

            <div class="col-xl-8">
              <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="mb-0" style="font-weight:800; color: var(--unitech-blue-dark);">Historique</h4>
                  <a href="/index.php" class="btn btn-light">Retour</a>
                </div>
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Propriétaire</th>
                        <th>Nom du fichier</th>
                        <th>Taille</th>
                        <th>Date</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($documents as $doc): ?>
                        <tr>
                          <td><?= htmlspecialchars($doc['name']) ?></td>
                          <td><?= htmlspecialchars($doc['original_name']) ?></td>
                          <td><?= number_format((int) $doc['size'] / 1024, 1, ',', ' ') ?> KB</td>
                          <td><?= htmlspecialchars($doc['uploaded_at']) ?></td>
                          <td>
                            <div class="d-flex gap-2">
                              <a href="/uploads/<?= rawurlencode($doc['file_name']) ?>" class="btn btn-sm btn-primary" target="_blank">Ouvrir</a>
                              <form method="post" onsubmit="return confirm('Supprimer ce document ?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete_document">
                                <input type="hidden" name="document_id" value="<?= (int) $doc['id'] ?>">
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
