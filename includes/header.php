<?php
if(session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Flor de Liz</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="site-wrapper">
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <a class="brand" href="/">Flor de Liz</a>
    <button class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">×</button>
  </div>
  <nav class="sidebar-nav">
    <a href="/" class="nav-link">Home</a>
    <a href="/pages/login_staff.php" class="nav-link">Staff Login</a>
    <a href="/pages/login_admin.php" class="nav-link">Admin Login</a>
  </nav>
</aside>
<div class="main-wrapper">
<header class="site-header">
  <div class="header-inner">
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">☰</button>
    <a class="brand" href="/">Flor de Liz</a>
    <nav class="top-nav">
      <a href="/">Home</a>
      <a href="/pages/login_staff.php">Staff</a>
      <a href="/pages/login_admin.php">Admin</a>
    </nav>
  </div>
</header>
<main class="main-content">
