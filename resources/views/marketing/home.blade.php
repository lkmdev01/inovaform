<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="InovaForm é uma plataforma para criar, publicar e otimizar funis, formulários e automações de vendas. Organize respostas e qualifique leads em um só lugar.">
    <meta property="og:title" content="InovaForm — Funis, formulários e automação">
    <meta property="og:description" content="Crie, publique e otimize funis, formulários e automações de vendas em um só lugar.">
    <meta property="og:type" content="website">
    <title>InovaForm — Funis, formulários e automação</title>
    @verbatim
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "InovaForm",
            "applicationCategory": "BusinessApplication",
            "description": "Plataforma para criar, publicar e otimizar funis, formulários e automações de vendas."
        }
    </script>
    @endverbatim
    <style>
        :root { color-scheme: dark; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #030916; color: #d6e5ff; }
        a { color: inherit; }
        .shell { min-height: 100vh; padding: 24px; background: radial-gradient(circle at 15% 20%, #1347d455 0, transparent 40%), radial-gradient(circle at 80% 10%, #1e3d8a66 0, transparent 40%), #030916; }
        .page { width: min(1120px, 100%); margin: 0 auto; }
        header, .actions, .footer-links { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        header { justify-content: space-between; }
        .brand, .button { border-radius: 999px; padding: 11px 18px; text-decoration: none; font-weight: 700; }
        .brand { border: 1px solid #2952ad; background: #0a1633cc; letter-spacing: .04em; }
        .button { background: #2f60cb; color: #fff; }
        .button.secondary { border: 1px solid #3a62c2; background: #0e1f43; color: #d0e0ff; }
        main { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(300px, .9fr); gap: 48px; align-items: center; padding: 80px 0; }
        .eyebrow { color: #90b2ff; font-size: .76rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 12px 0 20px; max-width: 760px; font-size: clamp(2.4rem, 6vw, 4.4rem); line-height: 1.08; }
        h2 { margin: 0 0 10px; color: #fff; font-size: 1.15rem; }
        p { color: #a9c2f7; font-size: 1.05rem; line-height: 1.65; }
        .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 28px; }
        .card, .google, .panel { border: 1px solid #274b99; border-radius: 18px; background: #0a1733e6; padding: 20px; }
        .google { margin-top: 20px; border-color: #315aaf; background: #091a3ae6; }
        .google a, footer a { color: #9fc1ff; }
        .panel { background: #081126f2; box-shadow: 0 25px 80px rgba(5, 12, 34, .75); }
        .metric { margin: 12px 0; border: 1px solid #284a92; border-radius: 14px; background: #0c1b3c; padding: 16px; }
        .metric strong { display: block; font-size: 1.6rem; color: #fff; }
        footer { border-top: 1px solid #1e3157; padding: 24px 0; color: #7d9fdb; font-size: .9rem; }
        @@media (max-width: 800px) { .shell { padding: 18px; } main { grid-template-columns: 1fr; padding: 52px 0; } .cards { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="shell">
        <div class="page">
            <header>
                <a class="brand" href="{{ route('home') }}" aria-label="InovaForm, página inicial">InovaForm</a>
                <nav aria-label="Acesso à conta" class="actions">
                    <a href="{{ route('login') }}">Entrar</a>
                    @if ($canRegister)
                        <a class="button" href="{{ route('register') }}">Criar conta</a>
                    @endif
                </nav>
            </header>

            <main>
                <section aria-labelledby="inovaform-title">
                    <span class="eyebrow">SaaS para funis e formulários</span>
                    <h1 id="inovaform-title">InovaForm: crie funis, formulários e automações em um só lugar.</h1>
                    <p>O InovaForm ajuda empresas e criadores a criar, publicar e otimizar páginas, formulários e funis de vendas. Organize respostas, qualifique leads e acompanhe a conversão sem depender de ferramentas separadas.</p>
                    <div class="actions">
                        <a class="button" href="{{ auth()->check() ? route('dashboard') : route('login') }}">{{ auth()->check() ? 'Ir para o painel' : 'Começar agora' }}</a>
                        @if ($canRegister)
                            <a class="button secondary" href="{{ route('register') }}">Teste gratuito</a>
                        @endif
                    </div>
                    <div class="cards" aria-label="Principais funcionalidades">
                        <article class="card"><h2>Captura</h2><p>Landing pages e formulários com blocos configuráveis.</p></article>
                        <article class="card"><h2>Qualificação</h2><p>Regras, tags e prioridades para organizar contatos.</p></article>
                        <article class="card"><h2>Conversão</h2><p>Automações para acompanhamento, vendas e onboarding.</p></article>
                    </div>
                    <section class="google" aria-labelledby="google-login-purpose">
                        <h2 id="google-login-purpose">Login com Google, com dados mínimos</h2>
                        <p>Ao entrar com Google, o InovaForm usa somente nome, e-mail e foto de perfil para criar ou acessar sua conta. Não lemos e-mails, arquivos, contatos ou calendário da sua conta Google.</p>
                        <a href="{{ route('privacy-policy') }}">Ver Política de Privacidade</a>
                    </section>
                </section>
                <aside class="panel" aria-label="Resumo do InovaForm">
                    <span class="eyebrow">Operação centralizada</span>
                    <h2 style="margin-top: 12px; font-size: 1.55rem;">Da captação à conversão</h2>
                    <div class="metric"><strong>Funis e formulários</strong><span>Crie jornadas adequadas para cada campanha.</span></div>
                    <div class="metric"><strong>Leads organizados</strong><span>Visualize respostas, status, tags e prioridades.</span></div>
                    <div class="metric"><strong>Automação comercial</strong><span>Prepare fluxos para acelerar o acompanhamento.</span></div>
                </aside>
            </main>

            <footer>
                <strong>InovaForm</strong> — Plataforma SaaS para funis, formulários e automação comercial.
                <div class="footer-links" style="margin-top: 12px;"><a href="{{ route('privacy-policy') }}">Política de Privacidade</a><a href="{{ route('terms-of-service') }}">Termos de Serviço</a></div>
            </footer>
        </div>
    </div>
</body>
</html>
