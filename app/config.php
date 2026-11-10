<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Projeto Integrador</title>
    <link rel="stylesheet" href="CSS/config.css">
</head>
<body>

    <nav class="menu">
        <div class="logo">🪐 Solar<b>Control</b></div>
        <a href="index.html">📊 Dashboard</a>
        <a href="paineis.html">☀️ Painéis</a>
        <a href="relatorios.html">📈 Relatórios</a>
        <a href="alertas.html">🔔 Alertas</a>
        <a href="config.html" class="ativo">⚙️ Configurações</a>
        <a href="login.html" class="sair">🚪 Sair</a>
    </nav>

    <main class="pagina">  <!-- ESTA LINHA ESTAVA FALTANDO -->

        <!-- TOPO -->
        <header class="topo">
            <div>
                <small class="crumb">Ajustar</small>
                <h1>Configurações</h1>
            </div>
            <div class="topo-acoes">
                <a href="alertas.html" class="sino" aria-label="Alertas">🔔<b>5</b></a>
                <div class="avatar">IR</div>
            </div>
        </header>

        <form method="post" class="cfg">

            <!-- ABAS DA ESQUERDA -->
            <nav class="cfg-nav card">
                <a href="#perfil" class="aba-perfil">Perfil</a>
                <a href="#usina" class="aba-usina">Usina</a>
                <a href="#notificacoes" class="aba-notif">Notificações</a>
            </nav>

            <div class="cfg-conteudo">

                <!-- PERFIL -->
                <section id="perfil" class="cfg-sec card">
                    <h3>Perfil</h3>
                    <p class="desc">Como você aparece no SolarControl.</p>
                    <div class="duas">
                        <div>
                            <label for="nome">Nome</label>
                            <input id="nome" name="nome" value="Igor">
                        </div>
                        <div>
                            <label for="email">E-mail</label>
                            <input id="email" name="email" type="email" value="igor.rego@gm.com">
                        </div>
                    </div>
                </section>

                <!-- USINA -->
                <section id="usina" class="cfg-sec card">
                    <h3>Usina</h3>
                    <p class="desc">A tarifa é usada para calcular a economia em reais.</p>
                    <div class="duas">
                        <div>
                            <label for="kwp">Capacidade instalada (kWp)</label>
                            <input id="kwp" name="kwp" value="8,4">
                        </div>
                        <div>
                            <label for="tarifa">Tarifa de energia (R$/kWh)</label>
                            <input id="tarifa" name="tarifa" value="0,92" inputmode="decimal">
                        </div>
                    </div>
                </section>

                <!-- NOTIFICAÇÕES -->
                <section id="notificacoes" class="cfg-sec card">
                    <h3>Notificações</h3>
                    <p class="desc">Escolha como quer ser avisado.</p>

                    <label class="opcao">
                        <span>Alertas críticos por e-mail<small>Painel offline, falha de inversor</small></span>
                        <input type="checkbox" class="switch" name="alerta_email" checked>
                    </label>
                    <label class="opcao">
                        <span>Notificações push<small>No navegador e no celular</small></span>
                        <input type="checkbox" class="switch" name="push" checked>
                    </label>
                    <label class="opcao">
                        <span>Resumo semanal<small>Toda segunda, às 08:00</small></span>
                        <input type="checkbox" class="switch" name="resumo">
                    </label>
                    <label class="opcao">
                        <span>Lembrete de limpeza<small>Quando a eficiência cair mais de 10%</small></span>
                        <input type="checkbox" class="switch" name="limpeza">
                    </label>
                </section>

                <!-- BOTÕES -->
                <div class="acoes">
                    <button type="reset" class="btn-ghost">Cancelar</button>
                    <button type="submit" class="btn-sol">Salvar alterações</button>
                </div>

            </div>
        </form>

    </main>

</body>
</html>
