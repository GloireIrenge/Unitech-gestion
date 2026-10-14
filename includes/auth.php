<?php

function currentUser(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }

    return $_SESSION['user'];
}

function requireAuth(array $allowedProfiles = []): array
{
    session_start();

    $user = currentUser();
    if ($user === null) {
        header('Location: /pages/samples/login.php');
        exit;
    }

    if ($allowedProfiles !== [] && !in_array(strtolower((string) $user['profile']), array_map('strtolower', $allowedProfiles), true)) {
        header('Location: /index.php');
        exit;
    }

    return $user;
}

function sanitizeText(?string $value): string
{
    return trim(strip_tags((string) $value));
}

function sanitizeEmail(?string $value): string
{
    return strtolower(trim((string) $value));
}

function generateCsrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], (string) $token);
}
