<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · MOA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya+SC:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app.css">
</head>
<body>
<main class="auth-page auth-flow-page">
    <section class="auth-page-card auth-flow-card">
        <a class="auth-brand" href="/"><span class="brand-mark"><i></i><i></i></span><strong>MOA</strong></a>
        @yield('content')
    </section>
</main>
@stack('scripts')
</body>
</html>