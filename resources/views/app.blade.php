<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Todo's · Time Tracking</title>
    <meta name="description" content="Todo's — a personal time tracking app with Kanban task boards, timers, reports and automations.">
    <meta name="keywords" content="time tracking, todo, tasks, kanban, timer, productivity, reports">
    <meta name="author" content="Todo's">
    <meta name="theme-color" content="#6366f1">

    <!-- Open Graph / social -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Todo's · Time Tracking">
    <meta property="og:description" content="Track time across Kanban boards, tasks and projects — with timers, reports and automations.">
    <meta property="og:site_name" content="Todo's">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Todo's · Time Tracking">
    <meta name="twitter:description" content="Track time across Kanban boards, tasks and projects — with timers, reports and automations.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/favicon.svg">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-indigo-600">
    <div id="app"></div>
</body>
</html>
