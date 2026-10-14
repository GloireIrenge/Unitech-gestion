<?php
require __DIR__ . '/../../includes/auth.php';
$currentUser = currentUser();
if ($currentUser !== null) {
    header('Location: /index.php');
    exit;
}

require __DIR__ . '/../../includes/db.php';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Session invalide, merci de réessayer.';
    } else {
        $email = sanitizeEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Veuillez saisir votre e-mail et votre mot de passe.';
        } else {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                unset($user['password_hash']);
                $_SESSION['user'] = $user;
                header('Location: /index.php');
                exit;
            }

            $error = 'Identifiants invalides. Essayez admin@unitech.edu / admin123';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Connexion</title>
  <link rel="stylesheet" href="../../assets/vendors/typicons/typicons.css">
  <link rel="stylesheet" href="../../assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="shortcut icon" href="../../assets/images/favicon.ico" />
  <style>
    :root {
      --unitech-blue: #5d8cf4;
      --unitech-blue-dark: #1d3968;
      --unitech-yellow: #f4dc61;
      --unitech-navy: #112a50;
      --unitech-bg: #edf4ff;
      --unitech-card: #ffffff;
      --unitech-text: #1d2b3a;
      --unitech-muted: #64748b;
    }
    body { background: linear-gradient(135deg, rgba(93,140,244,0.14), rgba(244,220,97,0.14)); font-family: "Poppins", sans-serif; }
    .auth-wrapper { min-height: 100vh; display:grid; place-items:center; padding:24px; }
    .auth-card { width:min(100%, 460px); background: rgba(255,255,255,0.94); border:1px solid rgba(29,57,104,0.1); border-radius:22px; box-shadow: 0 18px 45px rgba(17,42,80,0.08); overflow:hidden; }
    .auth-top { display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, var(--unitech-blue-dark), var(--unitech-navy)); padding:26px 20px 18px; }
    .auth-top img { width:160px; height:auto; }
    .auth-body { padding:28px; }
    .eyebrow { color: var(--unitech-blue-dark); text-transform:uppercase; letter-spacing:0.08em; font-size:0.72rem; font-weight:700; margin-bottom:6px; }
    h4 { margin:0 0 6px; color: var(--unitech-blue-dark); font-weight:800; font-size:2rem; }
    .subtitle { color: var(--unitech-muted); margin-bottom:18px; }
    .form-label { color: var(--unitech-text); font-weight:600; margin-bottom:8px; }
    .form-control { border-radius:12px; border:1px solid rgba(29,57,104,0.15); padding:0.9rem 1rem; }
    .btn-primary { background: linear-gradient(135deg, var(--unitech-blue), var(--unitech-blue-dark)); border:none; border-radius:12px; font-weight:700; padding:0.9rem 1rem; }
    .auth-link { color: var(--unitech-blue-dark); font-weight:600; }
    .tiny { font-size:0.78rem; color: var(--unitech-muted); }
    .alert { background: rgba(236,91,91,0.08); color: #b42318; border:1px solid rgba(236,91,91,0.18); border-radius:10px; padding:0.8rem 1rem; margin-bottom:1rem; }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="auth-top">
        <img src="../../assets/images/logo.jpg" alt="UNITECH logo">
      </div>
      <div class="auth-body">
        <div class="eyebrow">Portail académique</div>
        <h4>Connexion</h4>
        <p class="subtitle">Accédez à votre espace UNITECH.</p>

        <?php if (!empty($error)): ?>
          <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <div class="form-group mb-3">
            <label class="form-label" for="email">Adresse e-mail</label>
            <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="nom@unitech.edu" required>
          </div>
          <div class="form-group mb-3">
            <label class="form-label" for="password">Mot de passe</label>
            <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="••••••••" required>
          </div>
          <div class="form-check mb-3">
            <label class="form-check-label text-muted">
              <input type="checkbox" class="form-check-input" name="remember">
              Garder ma session active
            </label>
          </div>
          <div class="mt-3 d-grid gap-2">
            <button type="submit" class="btn btn-block btn-primary btn-lg fw-medium auth-form-btn">Se connecter</button>
          </div>
          <div class="text-center mt-4 tiny">
            Vous n'avez pas de compte ? <a href="register.php" class="auth-link">Créer un accès</a>
          </div>
          <div class="text-center mt-2 tiny">
            <a href="#" class="auth-link">Mot de passe oublié ?</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
