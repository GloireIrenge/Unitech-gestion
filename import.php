<?php
session_start();
if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['profile']) !== 'admin') {
    header('Location: /pages/samples/login.php');
    exit;
}

require __DIR__ . '/includes/db.php';
$pdo = getDbConnection();
$message = '';
$imported = 0;

function normalizeHeader($value): string
{
    return strtolower(trim(str_replace(['-', '_', ' '], '', preg_replace('/[^a-zA-Z0-9]/', '', $value))));
}

function readCsvRows(string $path): array
{
    $rows = [];
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException('Impossible de lire le fichier CSV.');
    }
    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

function findSheetPathFromWorkbook(string $xml): string
{
    preg_match('/<sheet[^>]+r:id="([^"]+)"/', $xml, $matches);
    if (!isset($matches[1])) {
        throw new RuntimeException('Feuille de calcul introuvable.');
    }
    $relationId = $matches[1];
    preg_match('/<Relationship[^>]+Id="' . preg_quote($relationId, '/') . '"[^>]+Target="([^"]+)"/', $xml, $relMatches);
    if (!isset($relMatches[1])) {
        throw new RuntimeException('Relation de feuille introuvable.');
    }
    return 'xl/' . ltrim($relMatches[1], '/');
}

function parseSharedStrings(string $xml): array
{
    $strings = [];
    if ($xml === '') {
        return $strings;
    }
    $dom = new DOMDocument();
    $dom->loadXML($xml);
    foreach ($dom->getElementsByTagName('si') as $si) {
        $text = '';
        foreach ($si->getElementsByTagName('t') as $tNode) {
            $text .= $tNode->textContent;
        }
        $strings[] = $text;
    }
    return $strings;
}

function readXlsxRows(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Le fichier Excel est invalide.');
    }

    $workbookXml = $zip->getFromName('xl/workbook.xml');
    if ($workbookXml === false) {
        throw new RuntimeException('Le fichier Excel ne contient pas de classeur valide.');
    }

    $sheetPath = findSheetPathFromWorkbook($workbookXml);
    $sheetXml = $zip->getFromName($sheetPath);
    if ($sheetXml === false) {
        throw new RuntimeException('La feuille Excel est introuvable.');
    }

    $sharedStrings = [];
    $sharedStringXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedStringXml !== false) {
        $sharedStrings = parseSharedStrings($sharedStringXml);
    }

    $dom = new DOMDocument();
    $dom->loadXML($sheetXml);
    $rows = [];
    foreach ($dom->getElementsByTagName('row') as $rowNode) {
        $values = [];
        foreach ($rowNode->childNodes as $cellNode) {
            if ($cellNode->nodeType !== XML_ELEMENT_NODE || $cellNode->tagName !== 'c') {
                continue;
            }
            $cellRef = $cellNode->getAttribute('r');
            $cellType = $cellNode->getAttribute('t');
            $value = '';
            $textNode = $cellNode->getElementsByTagName('v')->item(0);
            if ($cellType === 'inlineStr') {
                $inline = $cellNode->getElementsByTagName('is')->item(0);
                if ($inline) {
                    $value = $inline->textContent;
                }
            } elseif ($cellType === 's' && $textNode) {
                $index = (int) $textNode->textContent;
                $value = $sharedStrings[$index] ?? '';
            } elseif ($textNode) {
                $value = $textNode->textContent;
            }
            $values[] = $value;
        }
        if ($values !== []) {
            $rows[] = $values;
        }
    }

    $zip->close();
    return $rows;
}

function buildRowMap(array $row): array
{
    $map = [];
    foreach ($row as $index => $value) {
        $header = normalizeHeader((string) $value);
        if ($header !== '') {
            $map[$header] = $index;
        }
    }
    return $map;
}

function getCell(array $row, array $map, string $field): string
{
    $index = $map[$field] ?? null;
    if ($index === null || !isset($row[$index])) {
        return '';
    }
    return trim((string) $row[$index]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    $mode = $_POST['import_mode'] ?? 'students';

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Le fichier n’a pas pu être téléchargé.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $tmp = $file['tmp_name'];

        try {
            $rows = [];
            if ($ext === 'csv') {
                $rows = readCsvRows($tmp);
            } elseif ($ext === 'xlsx') {
                $rows = readXlsxRows($tmp);
            } else {
                throw new RuntimeException('Format non supporté. Téléchargez un fichier .xlsx ou .csv.');
            }

            if (count($rows) < 2) {
                throw new RuntimeException('Le fichier ne contient pas de données exploitables.');
            }

            $headerRow = $rows[0];
            $map = buildRowMap($headerRow);
            $dataRows = array_slice($rows, 1);
            $modeName = $mode;

            if ($modeName === 'students') {
                foreach ($dataRows as $row) {
                    if (count($row) === 0 || trim(implode('', $row)) === '') {
                        continue;
                    }
                    $nom = getCell($row, $map, 'nom');
                    $prenom = getCell($row, $map, 'prenom');
                    $email = getCell($row, $map, 'email');
                    $profil = strtolower(getCell($row, $map, 'profil') ?: 'student');
                    $password = getCell($row, $map, 'password');
                    if ($nom === '' || $prenom === '' || $email === '' || $password === '') {
                        continue;
                    }

                    $fullName = trim($prenom . ' ' . $nom);
                    $existing = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                    $existing->execute([$email]);
                    if ($existing->fetch()) {
                        continue;
                    }

                    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, profile, matricule) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([
                        $fullName,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        in_array($profil, ['student', 'admin', 'teacher', 'finance'], true) ? $profil : 'student',
                        'UNT-' . date('Y') . '-' . rand(1000, 9999)
                    ]);
                    $imported++;
                }
            } elseif ($modeName === 'courses') {
                foreach ($dataRows as $row) {
                    if (count($row) === 0 || trim(implode('', $row)) === '') {
                        continue;
                    }
                    $code = getCell($row, $map, 'code');
                    $name = getCell($row, $map, 'name');
                    $credits = (int) getCell($row, $map, 'credits');
                    $semester = getCell($row, $map, 'semester');
                    $teacher = getCell($row, $map, 'teacher');
                    $hours = (int) getCell($row, $map, 'hours');

                    if ($code === '' || $name === '' || $semester === '' || $teacher === '') {
                        continue;
                    }

                    $stmt = $pdo->prepare('INSERT OR IGNORE INTO courses (code, name, credits, semester, teacher, hours) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$code, $name, $credits, $semester, $teacher, $hours]);
                    $imported++;
                }
            } elseif ($modeName === 'results') {
                foreach ($dataRows as $row) {
                    if (count($row) === 0 || trim(implode('', $row)) === '') {
                        continue;
                    }
                    $studentEmail = getCell($row, $map, 'studentemail');
                    if ($studentEmail === '') {
                        $studentEmail = getCell($row, $map, 'email');
                    }
                    $courseCode = getCell($row, $map, 'coursecode');
                    $grade = (float) (getCell($row, $map, 'grade') ?: 0);
                    $status = getCell($row, $map, 'status') ?: 'Validé';
                    if ($studentEmail === '' || $courseCode === '') {
                        continue;
                    }

                    $user = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $user->execute([$studentEmail]);
                    $userRow = $user->fetch();
                    if (!$userRow) {
                        continue;
                    }

                    $stmt = $pdo->prepare('INSERT INTO results (user_id, course_code, grade, status) VALUES (?, ?, ?, ?)');
                    $stmt->execute([(int) $userRow['id'], $courseCode, $grade, $status]);
                    $imported++;
                }
            }

            $message = 'Import réussi : ' . $imported . ' ligne(s) traitée(s).';
        } catch (Throwable $e) {
            $message = 'Erreur d’import : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UNITECH - Import Excel</title>
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
    .code-box { background:#f7f9ff; border:1px solid rgba(93,140,244,0.1); border-radius:12px; padding:1rem; font-size:0.88rem; }
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
          <li class="nav-item nav-date dropdown"><a class="nav-link d-flex justify-content-center align-items-center" href="javascript:;"><h6 class="date mb-0">Import Excel / CSV</h6></a></li>
        </ul>
      </div>
    </nav>

    <div class="page-body-wrapper">
      <div class="main-panel w-100">
        <div class="content-wrapper">
          <div class="page-title">Import Excel / CSV</div>

          <?php if ($message !== ''): ?>
            <div class="alert alert-info mb-4"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>

          <div class="row g-4">
            <div class="col-xl-4">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">Téléverser un fichier</h4>
                <form method="post" enctype="multipart/form-data">
                  <div class="mb-3">
                    <label class="form-label">Type d’import</label>
                    <select name="import_mode" class="form-select" required>
                      <option value="students">Étudiants</option>
                      <option value="courses">Cours</option>
                      <option value="results">Résultats</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Fichier</label>
                    <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx" required>
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Importer</button>
                </form>
              </div>
            </div>

            <div class="col-xl-8">
              <div class="card p-3">
                <h4 class="mb-3" style="font-weight:800; color: var(--unitech-blue-dark);">Formats attendus</h4>
                <div class="code-box">
                  <strong>Étudiants :</strong> nom, prenom, email, profil, password<br>
                  <strong>Cours :</strong> code, name, credits, semester, teacher, hours<br>
                  <strong>Résultats :</strong> student_email, course_code, grade, status
                </div>
                <div class="mt-3">
                  <a href="/admin-dashboard.php" class="btn btn-light">Retour</a>
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
