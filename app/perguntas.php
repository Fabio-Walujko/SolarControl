<?php
$paginaAtual = 'perguntas.php';
$usuarioNome = 'Carlos Oliveira';
$usuarioTipo = 'Conta pessoal';
$usuarioInicial = substr($usuarioNome, 0, 1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Perguntas frequentes — Solar Control</title>
  <link rel="stylesheet" href="css/perguntas.css">
</head>

<body>
  <aside class="menu-lateral">
    <a class="marca" href="index.php">
      solar<span>control</span>
    </a>

    <nav class="menu-links" aria-label="Navegação do painel">
      <p class="titulo-menu">MENU</p>

      <a href="index.php" <?= $paginaAtual == 'index.php' ? 'aria-current="page"' : '' ?>>Visão geral</a>
      <a href="paineis.php" <?= $paginaAtual == 'paineis.php' ? 'aria-current="page"' : '' ?>>Painéis</a>
      <a href="monitoramento.php" <?= $paginaAtual == 'monitoramento.php' ? 'aria-current="page"' : '' ?>>Monitoramento</a>
      <a href="alerts.php" <?= $paginaAtual == 'alerts.php' ? 'aria-current="page"' : '' ?>>Alertas</a>
      <a href="perguntas.php" <?= $paginaAtual == 'perguntas.php' ? 'aria-current="page"' : '' ?>>FAQ</a>
    </nav>

    <div class="menu-rodape">
      <nav class="menu-links" aria-label="Navegação da conta">
        <p class="titulo-menu">GESTÃO</p>

        <a href="reports.php" <?= $paginaAtual == 'reports.php' ? 'aria-current="page"' : '' ?>>Relatórios</a>
        <a href="profile.php" <?= $paginaAtual == 'profile.php' ? 'aria-current="page"' : '' ?>>Meu perfil</a>
        <a href="config.php" <?= $paginaAtual == 'config.php' ? 'aria-current="page"' : '' ?>>Configurações</a>
      </nav>

      <div class="usuario">
        <span class="avatar"><?= $usuarioInicial ?></span>

        <div class="dados-usuario">
          <strong><?= $usuarioNome ?></strong>
          <small><?= $usuarioTipo ?></small>
        </div>

        <a class="sair" href="login.php">Sair</a>
      </div>
    </div>
  </aside>

  <main class="conteudo-principal">
    <div class="topo">
      <img src="imagens/topo.jpg" alt="Banner Solar Control">
    </div>

    <h1>Perguntas frequentes</h1>
    <h2>Encontre respostas para as suas dúvidas!</h2>

    <br>

    <div class="card-categoria">
      <h3>Geral</h3>

      <details>
        <summary>O que é energia fotovoltaica?</summary>
        <div class="conteudo">
          <p>É a eletricidade gerada a partir da luz do sol através de células fotovoltaicas presentes nos painéis solares. Quando a luz incide sobre essas células, cria um fluxo de eletrões que produz energia limpa e renovável.</p>
        </div>
      </details>

      <br><br>

      <details>
        <summary>Qual a diferença entre energia solar fotovoltaica e térmica?</summary>
        <div class="conteudo">
          <p>A energia fotovoltaica converte a luz solar diretamente em eletricidade para alimentar eletrodomésticos e lâmpadas. A energia térmica utiliza o calor do sol apenas para aquecer água (como nos banhos e piscinas).</p>
        </div>
      </details>

      <br>

      <details>
        <summary>O sistema funciona em dias nublados ou de chuva?</summary>
        <div class="conteudo">
          <p>Sim. Embora a produção diminua devido à menor incidência directa de radiação, os painéis continuam a gerar energia através da radiação difusa presente na atmosfera.</p>
        </div>
      </details>

      <br>

      <details>
        <summary>O que acontece à noite quando não há sol?</summary>
        <div class="conteudo">
          <p>À noite o sistema entra em repouso. O teu imóvel passa a consumir automaticamente a energia fornecida pela rede elétrica tradicional (ou de baterias, caso tenhas um sistema com armazenamento).</p>
        </div>
      </details>

      <br>

      <details>
        <summary>Qual a diferença entre o sistema On-Grid e Off-Grid?</summary>
        <div class="conteudo">
          <p>O sistema On-Grid funciona ligado à rede elétrica pública (permitindo gerar créditos de energia). O sistema Off-Grid é totalmente isolado e armazena a energia excedente em baterias para uso posterior.</p>
        </div>
      </details>
    </div>

    <div class="card-categoria">
      <h3>Instalação e Requisitos</h3>

      <details>
        <summary>Qualquer telhado pode receber painéis solares?</summary>
        <div class="conteudo">
          <p>A grande maioria dos telhados pode receber a instalação, desde que a estrutura de suporte esteja em bom estado e o local não tenha sombreamento excessivo. Fazemos uma avaliação técnica prévia para garantir a viabilidade.</p>
        </div>
      </details>

      <br>

      <details>
        <summary>Quanto tempo demora a instalação física do sistema?</summary>
        <div class="conteudo">
          <p>Para a maioria dos projetos residenciais, a instalação dos painéis e do inversor demora entre 1 e 3 dias úteis.</p>
        </div>
      </details>

      <br>

      <details>
        <summary>Preciso de autorização da distribuidora para instalar?</summary>
        <div class="conteudo">
          <p>Sim. Todo o sistema ligado à rede precisa de ser homologado junto da distribuidora de energia local. A equipa da Solar Control trata de todo este processo burocrático por ti.</p>
        </div>
      </details>

      <br>

      <details>
        <summary>O que acontece se houver um corte de energia na rede pública?</summary>
        <div class="conteudo">
          <p>Em sistemas On-Grid normais, o inversor desliga-se automaticamente por motivos de segurança (sistema anti-ilhamento), evitando eletrocutar os técnicos que trabalham na manutenção da rede pública.</p>
        </div>
      </details>
    </div>

    <div class="card-categoria">
      <h3>Economia e Financeiro</h3>

      <details>
        <summary>Quanto posso poupar na minha fatura de eletricidade?</summary>
        <div class="conteudo">
          <p>É possível reduzir até 95% do valor da tua fatura de energia, ficando responsável apenas pelo pagamento das taxas mínimas de disponibilidade e impostos obrigatórios da rede.</p>
        </div>
      </details>

      <br>

      <details>
        <summary>Qual é o tempo médio de retorno do investimento?</summary>
        <div class="conteudo">
          <p>O retorno financeiro ocorre geralmente entre 3 e 6 anos, dependendo do valor da tarifa local e do consumo do imóvel.</p>
        </div>
      </details>

      <br>

      <details>
        <summary>O que são os créditos de energia solar?</summary>
        <div class="conteudo">
          <p>Quando o teu sistema produz mais energia do que aquela que consomes durante o dia, o excesso é injetado na rede elétrica e transformado em créditos que podes abater no consumo noturno ou em meses futuros.</p>
        </div>
      </details>

      <br>

      <details>
        <summary>Posso transferir os meus créditos de energia para outro imóvel?</summary>
        <div class="conteudo">
          <p>Sim, desde que o outro imóvel esteja registado sob o mesmo titular (CPF/CNPJ) e dentro da área de cobertura da mesma distribuidora de energia.</p>
        </div>
      </details>

      <br>

      <details>
        <summary>A instalação do sistema solar valoriza o imóvel?</summary>
        <div class="conteudo">
          <p>Sim. Estudos do setor imobiliário indicam que imóveis com produção própria de energia limpa valorizam em média entre 10% e 15% no mercado.</p>
        </div>
      </details>
    </div>

    <div class="card-categoria">
      <h3>Manutenção e Garantia</h3>

      <details>
        <summary>Qual é a vida útil dos painéis solares?</summary>
        <div class="conteudo">
          <p>Os painéis solares têm uma vida útil superior a 25 a 30 anos, mantendo mais de 80% da sua eficiência de geração original ao fim desse período.</p>
        </div>
      </details>

      <br>

      <details>
        <summary>Que tipo de manutenção o sistema exige?</summary>
        <div class="conteudo">
          <p>A manutenção é simples e reduzida. Consiste maioritariamente na limpeza periódica das placas com água (duas vezes por ano ou conforme a acumulação de poeiras/folhas) e numa inspeção elétrica anual.</p>
        </div>
      </details>

      <br>

      <details>
        <summary>Qual é a garantia dos equipamentos?</summary>
        <div class="conteudo">
          <p>Os painéis solares contam com garantia de eficiência de 25 anos. Os inversores possuem garantia de fabrico que varia entre 5 e 10 anos (com possibilidade de extensão).</p>
        </div>
      </details>

      <br>

      <details>
        <summary>O que acontece se houver tempestades fortes ou granizo?</summary>
        <div class="conteudo">
          <p>Os painéis solares são fabricados com vidro temperado de alta resistência, projetados e testados para suportar tempestades, ventos fortes e impactos de granizo severos.</p>
        </div>
      </details>
    </div>
  </main>

  

</body>
</html>