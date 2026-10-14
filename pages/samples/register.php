<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: /index.php');
    exit;
}

require __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['nom'] ?? '') . ' ' . trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $profile = trim($_POST['profil'] ?? 'student');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Tous les champs requis doivent être remplis.';
    } else {
        $pdo = getDbConnection();
        $matricule = 'UNT-' . date('Y') . '-' . rand(1000, 9999);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, profile, matricule) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hash, strtolower($profile), $matricule]);

            $user = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $user->execute([$email]);
            $dbUser = $user->fetch();
            unset($dbUser['password_hash']);
            $_SESSION['user'] = $dbUser;
            header('Location: /index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Cette adresse e-mail est déjà utilisée. Veuillez en choisir une autre.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Inscription</title>
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
    .auth-card { width:min(100%, 520px); background: rgba(255,255,255,0.94); border:1px solid rgba(29,57,104,0.1); border-radius:22px; box-shadow: 0 18px 45px rgba(17,42,80,0.08); overflow:hidden; }
    .auth-top { display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, var(--unitech-blue-dark), var(--unitech-navy)); padding:22px 20px 16px; }
    .auth-top img { width:170px; height:auto; }
    .auth-body { padding:26px 28px 22px; }
    .eyebrow { color: var(--unitech-blue-dark); text-transform:uppercase; letter-spacing:0.08em; font-size:0.72rem; font-weight:700; margin-bottom:6px; }
    h4 { margin:0 0 6px; color: var(--unitech-blue-dark); font-weight:800; font-size:2rem; }
    .subtitle { color: var(--unitech-muted); margin-bottom:18px; }
    .form-label { color: var(--unitech-text); font-weight:600; }
    .form-control, .form-select { border-radius:12px; border:1px solid rgba(29,57,104,0.15); padding:0.82rem 1rem; }
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
        <div class="eyebrow">Création d'accès</div>
        <h4>Inscription</h4>
        <p class="subtitle">Ouvrez votre compte étudiant ou administratif.</p>

        <?php if (!empty($error)): ?>
          <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="register.php">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="nom">Nom</label>
              <input type="text" class="form-control" id="nom" name="nom" placeholder="Votre nom" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="prenom">Prénom</label>
              <input type="text" class="form-control" id="prenom" name="prenom" placeholder="Votre prénom" required>
            </div>
            <div class="col-md-12">
              <label class="form-label" for="email">Adresse e-mail</label>
              <input type="email" class="form-control" id="email" name="email" placeholder="nom@unitech.edu" required>
            </div>
            <div class="col-md-12">
              <label class="form-label" for="profil">Type de profil</label>
              <select class="form-select" id="profil" name="profil">
                <option value="student">Étudiant</option>
                <option value="admin">Agent administratif</option>
                <option value="teacher">Enseignant</option>
                <option value="finance">Finances</option>
              </select>
            </div>
            <div class="col-md-12">
              <label class="form-label" for="password">Mot de passe</label>
              <input type="password" class="form-control" id="password" name="password" placeholder="Créer un mot de passe" required>
            </div>
          </div>
          <div class="form-check mt-3">
            <label class="form-check-label text-muted">
              <input type="checkbox" class="form-check-input" required>
              J'accepte les conditions générales d'utilisation.
            </label>
          </div>
          <div class="mt-4 d-grid gap-2">
            <button type="submit" class="btn btn-block btn-primary btn-lg fw-medium auth-form-btn">Créer mon compte</button>
          </div>
          <div class="text-center mt-4 tiny">
            Vous avez déjà un compte ? <a href="login.php" class="auth-link">Connexion</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
