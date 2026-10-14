<?php

function getDbConnection(): PDO
{
    $dbDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database';
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
    }

    $dbPath = $dbDir . DIRECTORY_SEPARATOR . 'unitech.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            profile TEXT NOT NULL,
            matricule TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            credits INTEGER NOT NULL,
            semester TEXT NOT NULL,
            teacher TEXT NOT NULL,
            hours INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            course_code TEXT NOT NULL,
            grade REAL NOT NULL,
            status TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            status TEXT NOT NULL,
            description TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            file_name TEXT NOT NULL,
            original_name TEXT NOT NULL,
            mime_type TEXT NOT NULL,
            size INTEGER NOT NULL,
            uploaded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )
    ");

    $seededUsers = [
        ['admin@unitech.edu', 'Élodie Ndayisaba', 'admin123', 'admin', 'ADM-2026-001'],
        ['etudiant@unitech.edu', 'Amélie Ndayisaba', 'etudiant123', 'student', 'UNT-2026-101'],
        ['finance@unitech.edu', 'Sonia Mutoni', 'finance123', 'finance', 'FIN-2026-001'],
        ['teacher@unitech.edu', 'Nicolas Rukundo', 'teacher123', 'teacher', 'ENS-2026-001'],
    ];

    foreach ($seededUsers as [$email, $name, $password, $profile, $matricule]) {
        $existing = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $existing->execute([$email]);
        if ($existing->fetch()) {
            continue;
        }

        $pdo->prepare('INSERT INTO users (name, email, password_hash, profile, matricule) VALUES (?, ?, ?, ?, ?)')->execute([
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $profile,
            $matricule
        ]);
    }

    $courseCount = (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
    if ($courseCount === 0) {
        $courses = [
            ['ALG', 'Algorithmique avancée', 6, 'Semestre 1', 'Prof. K. Amani', 45],
            ['BDD', 'Base de données', 5, 'Semestre 1', 'Dr. M. Imani', 45],
            ['IA', 'Intelligence artificielle', 6, 'Semestre 2', 'Prof. C. Mugenzi', 60],
            ['DEV', 'Développement web avancé', 6, 'Semestre 2', 'Dr. J. Baho', 60],
            ['RSE', 'Réseaux et sécurité', 5, 'Semestre 3', 'Prof. T. Kalisa', 40],
        ];

        $stmt = $pdo->prepare('INSERT INTO courses (code, name, credits, semester, teacher, hours) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($courses as $course) {
            $stmt->execute($course);
        }
    }

    $studentRows = (int) $pdo->query('SELECT COUNT(*) FROM results')->fetchColumn();
    if ($studentRows === 0) {
        $studentId = $pdo->query("SELECT id FROM users WHERE email = 'etudiant@unitech.edu'")->fetchColumn();
        if ($studentId) {
            $stmt = $pdo->prepare('INSERT INTO results (user_id, course_code, grade, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$studentId, 'ALG', 88, 'Validé']);
            $stmt->execute([$studentId, 'BDD', 91, 'Validé']);
            $stmt->execute([$studentId, 'IA', 84, 'Validé']);
            $stmt->execute([$studentId, 'DEV', 89, 'Validé']);
        }
    }

    $paymentRows = (int) $pdo->query('SELECT COUNT(*) FROM payments')->fetchColumn();
    if ($paymentRows === 0) {
        $studentId = $pdo->query("SELECT id FROM users WHERE email = 'etudiant@unitech.edu'")->fetchColumn();
        if ($studentId) {
            $stmt = $pdo->prepare('INSERT INTO payments (user_id, amount, status, description) VALUES (?, ?, ?, ?)');
            $stmt->execute([$studentId, 152000, 'Payé', 'Frais de scolarité - Semestre 1']);
            $stmt->execute([$studentId, 32000, 'En attente', 'Frais de laboratoire']);
        }
    }

    return $pdo;
}
