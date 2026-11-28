<?php
/**
 * includes/header.php — ReFindUHO
 * Tag <head>, Google Fonts, Bootstrap 5.3.3 CSS, Bootstrap Icons, style.css,
 * dan judul halaman dinamis ($pageTitle).
 */

if (!function_exists('escape')) {
    function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$pageTitle = $pageTitle ?? 'ReFindUHO';
$catalogThemePages = ['daftar_barang.php', 'detail_barang.php'];
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$bodyClass = 'site-theme';
if ($currentPage === 'index.php') {
    $bodyClass .= ' landing-theme';
}
if (in_array($currentPage, $catalogThemePages, true)) {
    $bodyClass .= ' catalog-theme';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($pageTitle) ?> — ReFindUHO</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons 1.11.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="<?= escape($bodyClass) ?>">
<div class="page-transition-overlay" aria-hidden="true">
    <span class="page-transition-progress"></span>
</div>
<span class="visually-hidden" role="status" aria-live="polite" data-page-transition-status></span>
