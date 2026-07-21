<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="InovaForm é a plataforma para criar funis, formulários e automações de vendas com uma experiência visual, rápida e organizada.">
    <meta property="og:title" content="InovaForm — Funis, formulários e automação">
    <meta property="og:description" content="Crie experiências que capturam, qualificam e movem seus leads para a próxima conversa.">
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
        :root { color-scheme: dark; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; overflow-x: hidden; background: #05091a; color: #eff5ff; }
        a { color: inherit; }
        .page { position: relative; overflow: hidden; }
        .container { width: min(1180px, calc(100% - 48px)); margin: 0 auto; }
        .orb { position: absolute; border-radius: 999px; filter: blur(4px); pointer-events: none; }
        .orb.one { width: 600px; height: 600px; top: -300px; right: -170px; background: radial-gradient(circle, rgba(49, 118, 255, .28), transparent 68%); }
        .orb.two { width: 520px; height: 520px; top: 660px; left: -330px; background: radial-gradient(circle, rgba(30, 202, 190, .13), transparent 68%); }
        .topbar { position: relative; z-index: 2; border-bottom: 1px solid rgba(139, 177, 255, .12); }
        .topbar .container { display: flex; min-height: 78px; align-items: center; justify-content: space-between; gap: 24px; }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: #fff; font-size: 1.12rem; font-weight: 800; letter-spacing: -.035em; text-decoration: none; }
        .brand-mark { display: grid; width: 32px; height: 32px; place-items: center; border: 1px solid rgba(145, 184, 255, .6); border-radius: 10px; background: linear-gradient(135deg, #2b6ffc, #69a6ff); box-shadow: 0 8px 24px rgba(28, 92, 235, .38); font-size: .72rem; letter-spacing: -.04em; }
        nav { display: flex; align-items: center; gap: 26px; color: #b7c9e9; font-size: .9rem; }
        nav a { text-decoration: none; transition: color .2s ease; }
        nav a:hover { color: #fff; }
        .actions { display: flex; align-items: center; gap: 10px; }
        .button { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; gap: 9px; border: 1px solid transparent; border-radius: 12px; padding: 0 17px; color: #fff; font-size: .9rem; font-weight: 700; text-decoration: none; transition: transform .2s ease, background .2s ease, border-color .2s ease; }
        .button:hover { transform: translateY(-2px); }
        .button.primary { background: linear-gradient(135deg, #3575ff, #5b9bff); box-shadow: 0 12px 30px rgba(39, 102, 239, .3); }
        .button.ghost { border-color: rgba(139, 177, 255, .26); background: rgba(13, 29, 63, .56); color: #d9e7ff; }
        .hero { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1.03fr) minmax(440px, .97fr); gap: 48px; align-items: center; padding: 94px 0 102px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(104, 156, 255, .24); border-radius: 999px; background: rgba(39, 93, 191, .13); padding: 8px 12px; color: #99bdff; font-size: .72rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
        .pulse { display: block; width: 7px; height: 7px; border-radius: 999px; background: #64d9d0; box-shadow: 0 0 0 5px rgba(100, 217, 208, .12); }
        h1 { max-width: 700px; margin: 22px 0; color: #fff; font-size: clamp(3rem, 5.3vw, 5.2rem); letter-spacing: -.067em; line-height: .99; }
        h1 em { font-style: normal; background: linear-gradient(110deg, #73a9ff, #79e0da); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .hero-copy { max-width: 630px; margin: 0; color: #aebfdd; font-size: 1.1rem; line-height: 1.7; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
        .under-cta { display: flex; align-items: center; gap: 10px; margin: 19px 0 0; color: #89a0c9; font-size: .8rem; }
        .check { display: grid; width: 18px; height: 18px; place-items: center; border-radius: 999px; background: rgba(96, 214, 187, .16); color: #6ce4c8; font-size: .72rem; }
        .product-window { position: relative; border: 1px solid rgba(132, 172, 255, .3); border-radius: 21px; background: linear-gradient(155deg, rgba(18, 36, 78, .95), rgba(7, 16, 39, .98)); padding: 10px; box-shadow: 0 30px 70px rgba(0, 0, 0, .38), inset 0 1px rgba(255, 255, 255, .06); transform: rotate(1.5deg); }
        .window-head { display: flex; align-items: center; gap: 6px; padding: 4px 4px 13px; }
        .window-head i { width: 8px; height: 8px; border-radius: 99px; background: #315379; }
        .window-head span { width: 50%; height: 7px; margin-left: 8px; border-radius: 999px; background: #18335f; }
        .product-surface { min-height: 404px; overflow: hidden; border: 1px solid rgba(110, 155, 245, .19); border-radius: 14px; background: #071632; }
        .app-nav { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #18365f; padding: 14px 16px; }
        .app-logo { color: #dce9ff; font-size: .72rem; font-weight: 800; letter-spacing: .08em; }
        .app-controls { display: flex; gap: 6px; }
        .app-controls span { width: 24px; height: 20px; border: 1px solid #2a528d; border-radius: 5px; background: #0c234a; }
        .app-body { display: grid; grid-template-columns: 96px 1fr; min-height: 350px; }
        .app-sidebar { display: grid; align-content: start; gap: 10px; border-right: 1px solid #18365f; padding: 18px 10px; }
        .app-sidebar span { height: 9px; border-radius: 99px; background: #173561; }
        .app-sidebar span:nth-child(2) { background: #3979e8; }
        .canvas { position: relative; padding: 28px 26px; }
        .canvas-label { color: #7fa7ed; font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
        .canvas-title { margin: 10px 0 7px; color: #fff; font-size: 1.32rem; font-weight: 800; }
        .canvas-subtitle { width: 75%; height: 8px; border-radius: 99px; background: #24487c; }
        .question-card { position: relative; z-index: 1; margin-top: 24px; border: 1px solid #3966a9; border-radius: 12px; background: #0c254d; padding: 15px; }
        .question-card strong { color: #eff5ff; font-size: .88rem; }
        .choice { display: flex; align-items: center; gap: 8px; margin-top: 10px; border: 1px solid #244e87; border-radius: 8px; padding: 9px; color: #9ab9eb; font-size: .72rem; }
        .choice b { width: 12px; height: 12px; border: 1px solid #619aff; border-radius: 99px; }
        .choice.active { border-color: #5b95f6; background: #163f78; color: #e5efff; }
        .choice.active b { border: 3px solid #78aeff; }
        .flow-line { position: absolute; top: 99px; right: -34px; width: 68px; border-top: 1px dashed #62a2ff; transform: rotate(-32deg); }
        .floating-card { position: absolute; right: -30px; bottom: 32px; z-index: 3; width: 178px; border: 1px solid rgba(120, 167, 255, .48); border-radius: 13px; background: #112c5c; padding: 13px; box-shadow: 0 20px 38px rgba(0, 0, 0, .35); transform: rotate(-5deg); }
        .floating-card small { color: #87ace7; font-size: .65rem; }
        .floating-card strong { display: block; margin-top: 4px; color: #fff; font-size: .98rem; }
        .floating-card div { height: 6px; margin-top: 10px; border-radius: 99px; background: linear-gradient(90deg, #69e0d2 62%, #255180 62%); }
        .logo-band { position: relative; z-index: 1; border-top: 1px solid rgba(128, 169, 255, .13); border-bottom: 1px solid rgba(128, 169, 255, .13); background: rgba(8, 19, 43, .52); }
        .logo-band .container { display: flex; align-items: center; justify-content: space-between; gap: 30px; padding: 25px 0; }
        .logo-band p { margin: 0; color: #758db8; font-size: .8rem; }
        .mini-features { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px; }
        .mini-feature { border: 1px solid rgba(101, 142, 213, .2); border-radius: 99px; padding: 8px 12px; color: #a2b6db; font-size: .72rem; }
        .section { position: relative; z-index: 1; padding: 116px 0; }
        .section-heading { max-width: 700px; }
        .section-heading h2 { margin: 14px 0 0; color: #fff; font-size: clamp(2.15rem, 4.2vw, 3.55rem); letter-spacing: -.052em; line-height: 1.06; }
        .section-heading p { max-width: 620px; color: #9db0d1; font-size: 1rem; line-height: 1.7; }
        .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-top: 45px; }
        .feature { min-height: 245px; border: 1px solid rgba(119, 158, 224, .18); border-radius: 18px; background: linear-gradient(150deg, rgba(17, 36, 75, .72), rgba(8, 17, 39, .88)); padding: 25px; }
        .feature-number { display: grid; width: 36px; height: 36px; place-items: center; border-radius: 10px; background: rgba(65, 125, 242, .18); color: #91b8ff; font-size: .76rem; font-weight: 800; }
        .feature h3 { margin: 30px 0 10px; color: #fff; font-size: 1.14rem; }
        .feature p { margin: 0; color: #99acce; font-size: .9rem; line-height: 1.65; }
        .workflow { display: grid; grid-template-columns: .87fr 1.13fr; gap: 65px; align-items: center; border-top: 1px solid rgba(128, 169, 255, .13); border-bottom: 1px solid rgba(128, 169, 255, .13); }
        .steps { display: grid; gap: 0; }
        .step { display: grid; grid-template-columns: 48px 1fr; gap: 18px; padding: 21px 0; border-bottom: 1px solid rgba(128, 169, 255, .13); }
        .step:last-child { border-bottom: 0; }
        .step-index { display: grid; width: 34px; height: 34px; place-items: center; border: 1px solid #3268b8; border-radius: 99px; color: #9fc0ff; font-size: .74rem; font-weight: 800; }
        .step h3 { margin: 3px 0 8px; color: #fff; font-size: 1rem; }
        .step p { margin: 0; color: #91a7cd; font-size: .88rem; line-height: 1.6; }
        .insight-card { border: 1px solid rgba(105, 162, 255, .28); border-radius: 24px; background: linear-gradient(145deg, #102b58, #081634); padding: 28px; box-shadow: 0 28px 65px rgba(0, 0, 0, .25); }
        .insight-top { display: flex; align-items: center; justify-content: space-between; color: #b6d0ff; font-size: .82rem; }
        .status { border-radius: 99px; background: rgba(95, 224, 190, .14); padding: 6px 9px; color: #7de5c7; font-size: .68rem; font-weight: 750; }
        .chart { display: flex; height: 180px; align-items: end; gap: 12px; margin: 30px 0 15px; padding: 0 8px; border-bottom: 1px solid #345687; }
        .bar { flex: 1; border-radius: 7px 7px 0 0; background: linear-gradient(#75a9ff, #2860c9); }
        .bar:nth-child(1) { height: 34%; opacity: .45; }.bar:nth-child(2) { height: 52%; opacity: .6; }.bar:nth-child(3) { height: 46%; opacity: .7; }.bar:nth-child(4) { height: 72%; opacity: .85; }.bar:nth-child(5) { height: 94%; }.bar:nth-child(6) { height: 78%; opacity: .88; }
        .insight-bottom { display: flex; justify-content: space-between; color: #86a0cc; font-size: .78rem; }
        .insight-bottom strong { color: #fff; font-size: 1rem; }
        .audience { background: linear-gradient(180deg, rgba(18, 41, 83, .5), transparent); }
        .audience-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 42px; }
        .audience-card { border: 1px solid rgba(124, 161, 221, .2); border-radius: 16px; background: rgba(9, 21, 48, .7); padding: 22px; }
        .audience-icon { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 12px; background: #14366f; color: #8fc1ff; font-weight: 800; }
        .audience-card h3 { margin: 20px 0 9px; color: #fff; font-size: 1rem; }.audience-card p { margin: 0; color: #95a9cc; font-size: .88rem; line-height: 1.6; }
        .faq-layout { display: grid; grid-template-columns: .8fr 1.2fr; gap: 70px; align-items: start; }
        .faq { border-top: 1px solid rgba(133, 169, 224, .22); }.faq details { border-bottom: 1px solid rgba(133, 169, 224, .22); padding: 20px 0; }.faq summary { cursor: pointer; color: #eaf2ff; font-size: .98rem; font-weight: 700; list-style: none; }.faq summary::-webkit-details-marker { display: none; }.faq summary::after { float: right; color: #81acfc; content: '+'; font-size: 1.3rem; font-weight: 400; }.faq details[open] summary::after { content: '−'; }.faq p { margin: 14px 35px 0 0; color: #93a9ce; font-size: .9rem; line-height: 1.65; }
        .final-cta { position: relative; overflow: hidden; border: 1px solid rgba(114, 165, 255, .36); border-radius: 28px; background: linear-gradient(125deg, #173e89, #0d2453 57%, #092142); padding: 62px; text-align: center; }.final-cta::before { position: absolute; top: -180px; left: 50%; width: 500px; height: 360px; border-radius: 999px; background: rgba(99, 174, 255, .18); content: ''; filter: blur(40px); transform: translateX(-50%); }.final-cta > * { position: relative; }.final-cta h2 { max-width: 760px; margin: 13px auto; color: #fff; font-size: clamp(2.15rem, 4.7vw, 3.7rem); letter-spacing: -.055em; line-height: 1.03; }.final-cta p { max-width: 550px; margin: 0 auto 26px; color: #bfdbff; line-height: 1.65; }
        footer { position: relative; z-index: 1; padding: 42px 0 32px; color: #8097be; font-size: .82rem; }.footer-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding-top: 28px; border-top: 1px solid rgba(128, 169, 255, .15); }.footer-links { display: flex; flex-wrap: wrap; gap: 18px; }.footer-links a { color: #a6c2ef; text-decoration: none; }
        @@media (max-width: 900px) { .hero, .workflow, .faq-layout { grid-template-columns: 1fr; }.hero { padding-top: 70px; }.product-window { width: min(580px, calc(100% - 26px)); margin: 0 auto; }.workflow { gap: 35px; }.feature-grid, .audience-grid { grid-template-columns: 1fr 1fr; }.floating-card { right: -15px; }.logo-band .container { align-items: flex-start; flex-direction: column; }.mini-features { justify-content: flex-start; } }
        @@media (max-width: 650px) { .container { width: min(100% - 32px, 1180px); }.topbar .container { min-height: 68px; }.topbar nav { display: none; }.brand { font-size: 1rem; }.actions .ghost { display: none; }.hero { gap: 35px; padding: 54px 0 68px; }h1 { font-size: clamp(2.75rem, 13vw, 4.1rem); }.hero-copy { font-size: 1rem; }.section { padding: 78px 0; }.feature-grid, .audience-grid { grid-template-columns: 1fr; }.product-window { transform: none; }.product-surface { min-height: 350px; }.app-body { grid-template-columns: 68px 1fr; }.canvas { padding: 22px 16px; }.floating-card { right: -8px; bottom: 20px; width: 146px; }.final-cta { padding: 45px 21px; }.footer-row { align-items: flex-start; flex-direction: column; }.cards { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="page">
        <span class="orb one"></span><span class="orb two"></span>
        <header class="topbar">
            <div class="container">
                <a class="brand" href="{{ route('home') }}" aria-label="InovaForm, página inicial"><span class="brand-mark">IF</span> InovaForm</a>
                <nav aria-label="Navegação principal"><a href="#produto">Produto</a><a href="#como-funciona">Como funciona</a><a href="#recursos">Recursos</a><a href="#perguntas">Perguntas</a></nav>
                <div class="actions"><a class="button ghost" href="{{ route('login') }}">Entrar</a>@if ($canRegister)<a class="button primary" href="{{ route('register') }}">Começar grátis <span>→</span></a>@endif</div>
            </div>
        </header>

        <main>
            <section class="container hero" id="produto">
                <div>
                    <span class="eyebrow"><span class="pulse"></span> Funis que acompanham sua estratégia</span>
                    <h1>Transforme interesse em <em>próximos passos.</em></h1>
                    <p class="hero-copy">O InovaForm reúne criação visual, páginas interativas e gestão de leads para você construir jornadas que fazem sentido para cada campanha — sem depender de código ou de uma pilha de ferramentas.</p>
                    <div class="hero-actions"><a class="button primary" href="{{ auth()->check() ? route('dashboard') : route('register') }}">{{ auth()->check() ? 'Abrir meu painel' : 'Criar meu primeiro funil' }} <span>→</span></a><a class="button ghost" href="#como-funciona">Conhecer a plataforma</a></div>
                    <p class="under-cta"><span class="check">✓</span> Comece criando uma jornada do seu jeito</p>
                </div>
                <div class="product-window" aria-label="Prévia da interface de criação de funis">
                    <div class="window-head"><i></i><i></i><i></i><span></span></div>
                    <div class="product-surface"><div class="app-nav"><span class="app-logo">INOVAFORM / BUILDER</span><div class="app-controls"><span></span><span></span><span></span></div></div><div class="app-body"><aside class="app-sidebar"><span></span><span></span><span></span><span></span><span></span></aside><div class="canvas"><span class="canvas-label">Etapa 01 · Diagnóstico</span><div class="canvas-title">Vamos encontrar o melhor caminho?</div><div class="canvas-subtitle"></div><article class="question-card"><strong>Qual é seu principal objetivo agora?</strong><div class="choice active"><b></b>Captar oportunidades</div><div class="choice"><b></b>Qualificar contatos</div><div class="choice"><b></b>Apresentar uma oferta</div></article><span class="flow-line"></span></div></div></div>
                    <div class="floating-card"><small>Jornada em andamento</small><strong>4 de 6 etapas</strong><div></div></div>
                </div>
            </section>

            <section class="logo-band"><div class="container"><p>Uma plataforma pensada para conectar sua ideia, sua página e sua próxima conversa.</p><div class="mini-features"><span class="mini-feature">Editor visual</span><span class="mini-feature">Leads organizados</span><span class="mini-feature">Domínio próprio</span><span class="mini-feature">Acesso compartilhado</span></div></div></section>

            <section class="container section" id="recursos">
                <div class="section-heading"><span class="eyebrow">Tudo no lugar certo</span><h2>Menos esforço operacional. Mais clareza para testar, ajustar e publicar.</h2><p>Você controla a experiência do início ao fim e deixa as informações importantes prontas para a tomada de decisão.</p></div>
                <div class="feature-grid"><article class="feature"><span class="feature-number">01</span><h3>Construtor visual</h3><p>Monte etapas, perguntas, textos, mídia e chamadas para ação em uma tela feita para organizar a jornada.</p></article><article class="feature"><span class="feature-number">02</span><h3>Experiências personalizadas</h3><p>Use condições, respostas e componentes para apresentar caminhos mais relevantes em cada interação.</p></article><article class="feature"><span class="feature-number">03</span><h3>Leads com contexto</h3><p>Centralize respostas e acompanhe seus contatos com filtros, status, tags, prioridades e exportação.</p></article><article class="feature"><span class="feature-number">04</span><h3>Design da sua marca</h3><p>Ajuste cores, tipografia, superfícies e identidade de cada funil sem recriar sua estrutura.</p></article><article class="feature"><span class="feature-number">05</span><h3>Publicação preparada</h3><p>Configure SEO, imagem de compartilhamento, disponibilidade e domínio personalizado em um único espaço.</p></article><article class="feature"><span class="feature-number">06</span><h3>Criação assistida por IA</h3><p>Comece com uma direção estratégica e edite cada etapa para transformar o esboço em uma jornada sua.</p></article></div>
            </section>

            <section class="container section workflow" id="como-funciona">
                <div class="section-heading"><span class="eyebrow">Do insight à publicação</span><h2>Uma jornada simples para colocar sua próxima campanha no ar.</h2><p>O InovaForm foi desenhado para que você avance com segurança, sem perder o controle criativo.</p></div>
                <div class="steps"><article class="step"><span class="step-index">01</span><div><h3>Defina a conversa</h3><p>Comece do zero, com um modelo ou com uma orientação inicial para estruturar sua ideia.</p></div></article><article class="step"><span class="step-index">02</span><div><h3>Construa a experiência</h3><p>Organize perguntas, componentes e regras para criar uma sequência que respeita o contexto do lead.</p></div></article><article class="step"><span class="step-index">03</span><div><h3>Publique e acompanhe</h3><p>Compartilhe a URL, use seu domínio e acompanhe as respostas diretamente no painel.</p></div></article></div>
            </section>

            <section class="container section workflow">
                <div class="insight-card" aria-label="Exemplo de acompanhamento de jornada"><div class="insight-top"><span>Visão da jornada</span><span class="status">Ao vivo</span></div><div class="chart"><span class="bar"></span><span class="bar"></span><span class="bar"></span><span class="bar"></span><span class="bar"></span><span class="bar"></span></div><div class="insight-bottom"><span>Respostas por etapa</span><strong>Visão centralizada</strong></div></div>
                <div class="section-heading"><span class="eyebrow">Informação acionável</span><h2>Não colete apenas contatos. Entenda em que ponto a conversa evolui.</h2><p>As respostas ficam conectadas ao funil que as gerou, para você revisar a jornada e orientar o próximo contato com mais contexto.</p><div class="hero-actions"><a class="button ghost" href="{{ route('login') }}">Explorar o painel</a></div></div>
            </section>

            <section class="audience section"><div class="container"><div class="section-heading"><span class="eyebrow">Feito para diferentes operações</span><h2>Uma base flexível para quem quer criar uma experiência melhor antes da venda.</h2></div><div class="audience-grid"><article class="audience-card"><span class="audience-icon">01</span><h3>Especialistas e criadores</h3><p>Estruture diagnósticos, captações e páginas de oferta com uma narrativa que combina com o seu posicionamento.</p></article><article class="audience-card"><span class="audience-icon">02</span><h3>Times de marketing</h3><p>Construa campanhas sem abrir mão de consistência visual, organização de respostas e espaço para testes.</p></article><article class="audience-card"><span class="audience-icon">03</span><h3>Agências e operações</h3><p>Centralize funis de diferentes projetos, compartilhe acesso e mantenha cada entrega organizada.</p></article></div></div></section>

            <section class="container section faq-layout" id="perguntas"><div class="section-heading"><span class="eyebrow">Perguntas frequentes</span><h2>O essencial, antes de você começar.</h2><p>Você pode explorar a plataforma e adaptar cada parte da experiência conforme a sua operação evolui.</p></div><div class="faq"><details open><summary>Preciso saber programar para criar um funil?</summary><p>Não. O construtor foi pensado para organizar a experiência visualmente, com etapas e componentes configuráveis.</p></details><details><summary>Posso usar a identidade da minha marca?</summary><p>Sim. Cada funil pode ter seus próprios tokens de cores, tipografia, superfícies e configurações de publicação.</p></details><details><summary>Onde acompanho os contatos que responderam?</summary><p>As respostas ficam no painel de leads, onde você pode filtrar, organizar, atualizar status e exportar informações.</p></details><details><summary>É possível publicar com domínio personalizado?</summary><p>Sim. O InovaForm orienta a configuração e mostra o status de DNS e HTTPS para o domínio do seu funil.</p></details></div></section>

            <section class="container section"><div class="final-cta"><span class="eyebrow"><span class="pulse"></span> Sua próxima jornada começa aqui</span><h2>Crie uma experiência que deixa claro qual é o próximo passo.</h2><p>Transforme sua estratégia em uma jornada publicável, visual e pronta para gerar conversas melhores.</p><a class="button primary" href="{{ auth()->check() ? route('dashboard') : route('register') }}">{{ auth()->check() ? 'Ir para meu painel' : 'Começar com InovaForm' }} <span>→</span></a><p class="under-cta" style="justify-content: center;"><span class="check">✓</span> Login com Google disponível com dados mínimos</p></div></section>
        </main>

        <footer><div class="container footer-row"><a class="brand" href="{{ route('home') }}"><span class="brand-mark">IF</span> InovaForm</a><span>Plataforma SaaS para funis, formulários e automação comercial.</span><div class="footer-links"><a href="{{ route('privacy-policy') }}">Política de Privacidade</a><a href="{{ route('terms-of-service') }}">Termos de Serviço</a></div></div></footer>
    </div>
</body>
</html>
