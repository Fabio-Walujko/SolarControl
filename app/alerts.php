<?php
require_once __DIR__ . '/init.php';

$usuarioLogado = exigirLogin($pdo);
$equipe = ehEquipe($usuarioLogado);

// Alertas gerados a partir dos sistemas, registros e manutenções do banco.
// Cliente: só os dele · funcionário/adm: de todos os clientes.
$alertas = gerarAlertas($pdo, escopoUsuario($usuarioLogado));

$pendentes = array_values(array_filter($alertas, static fn ($a) => $a['situacao'] === 'pendente'));
$resolvidos = array_values(array_filter($alertas, static fn ($a) => $a['situacao'] === 'resolvido'));

$filtro = $_GET['filtro'] ?? 'pendentes';
$lista = match ($filtro) {
    'resolvidos' => $resolvidos,
    'todos' => $alertas,
    default => $pendentes,
};
if (!in_array($filtro, ['pendentes', 'resolvidos', 'todos'], true)) {
    $filtro = 'pendentes';
}

$nomesPrioridade = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Alertas | Solar Control</title>
  <link rel="stylesheet" href="CSS/alerts.css?v=<?= filemtime(__DIR__ . '/CSS/alerts.css') ?>">
</head>

<body>

  <?php include __DIR__ . '/menu.php'; ?>

  <div class="conteudo">

    <header class="textprincipal">
      <h1>Alertas</h1>
      <p>
        <?= $equipe
          ? 'Alertas automáticos de todos os clientes, gerados a partir dos dados do sistema.'
          : 'Alertas automáticos dos seus sistemas, gerados a partir dos seus dados.' ?>
      </p>
    </header>

    <main>

      </details class="formularioabaixo">

      <section>

        <article class="cartoes alerta-alta">

          <h3>Manutenção atrasada</h3>

          <span>Aberto</span>
          <span>Alta</span>

          <p>
            A manutenção de Maria Oliveira estava prevista para
            10/11/2026 e nenhuma visita foi agendada.
          </p>

          <small>
            Maria Oliveira · Manutenção atrasada · aberto há 7 dia(s)
          </small>

          <p>
             Liguei para a cliente, ela vai confirmar o melhor dia para a visita.
          </p>

          <details class="formularioabaixo">
            <summary class="verdetalhes">Ver detalhes</summary>

            <p>Cliente: Maria Oliveira</p>
            <p>Tipo: Manutenção preventiva</p>
            <p>Data prevista: 10/11/2026</p>


            <br>
          </details class="formularioabaixo">

        </article>

        <article class="cartoes alerta-alta">

          <h3>Sistema Inativo</h3>

          <span>Aberto</span>
          <span>Alta</span>

          <p>
            Mercado Bom Preço está com status "Inativo"
            e não está gerando energia.
          </p>

          <small>
            Mercado Bom Preço · Sistema parado · aberto há 48 dia(s)
          </small>

          <p>
             Alerta criado automaticamente pelo SolarControl.
          </p>

          <details class="formularioabaixo">
            <summary class="verdetalhes">Ver detalhes</summary>

            <p>Cliente: Mercado Bom Preço</p>
            <p>Tipo: Sistema parado</p>
            <p>Status: Inativo</p>


<br>
          </details class="formularioabaixo">

        </article>

        <article class="cartoes alerta-media">

          <h3>Eficiência baixa</h3>

          <span>Aberto</span>
          <span>Média</span>

          <p>
            Padaria Sol Nascente gerou só 63% do esperado
            nos últimos 7 dias.
          </p>

          <small>
            Padaria Sol Nascente · Eficiência baixa · aberto há 3 dia(s)
          </small>

          <p>
             A cliente contou que tem uma obra ao lado soltando
            muita poeira. Provável sujeira nos módulos.
          </p>

          <details class="formularioabaixo">
            <summary class="verdetalhes">Ver detalhes</summary>

            <p>Cliente: Padaria Sol Nascente</p>
            <p>Geração esperada: 100%</p>
            <p>Geração atual: 63%</p>

<br>
          </details class="formularioabaixo">

        </article>

        <article class="cartoes alerta-media">

          <h3>Sistema Em manutenção</h3>

          <span>Em andamento</span>
          <span>Média</span>

          <p>
            Escola Técnica Verde está com status "Em manutenção"
            e não está gerando energia.
          </p>

          <small>
            Escola Técnica Verde · Sistema parado · aberto há 17 dia(s)
            · Responsável: Carlos Souza · Prazo: 25/11/2026
          </small>

          <p>
             Status: Aberto → Em andamento · Responsável: Carlos Souza
            · Prazo: 25/11/2026 · Inversor com falha no MPPT 2.
          </p>

          <details class="formularioabaixo">
            <summary class="verdetalhes">Ver detalhes</summary>

            <p>Cliente: Escola Técnica Verde</p>
            <p>Responsável: Carlos Souza</p>
            <p>Prazo: 25/11/2026</p>


<br>
          </details class="formularioabaixo">

        </article>

        <article class="cartoes alerta-alta">

          <h3>Falha na comunicação</h3>

          <span>Aberto</span>
          <span>Alta</span>

          <p>
            O inversor está sem comunicação com a plataforma de monitoramento.
          </p>

          <small>
            Energia Verde · Comunicação interrompida
          </small>

          <p> Aguardando verificação da conexão.</p>

          <details class="formularioabaixo">
            <summary class="verdetalhes">Ver detalhes</summary>


<br>
          </details class="formularioabaixo">

        </article>

        <article class="cartoes alerta-resolvido">

          <h3>Limpeza dos módulos concluída</h3>

          <span>Resolvido</span>
          <span>Média</span>

          <p>
            A limpeza dos painéis foi realizada e a geração voltou ao normal.
          </p>

          <small>Fazenda Solar · Resolvido há 2 dias</small>

          <details class="formularioabaixo">
            <summary class="verdetalhes">Ver detalhes</summary>

            <p>Serviço concluído com sucesso.</p>

          </details class="formularioabaixo">

        </article>

        <article class="cartoes alerta-resolvido">

          <h3>Inversor normalizado</h3>

          <span>Resolvido</span>
          <span>Alta</span>

          <p>
            A falha do inversor foi corrigida e o sistema voltou a funcionar.
          </p>

          <small>Residência Solar · Resolvido há 5 dias</small>

          <details class="formularioabaixo">
            <summary class="verdetalhes">Ver detalhes</summary>

            <p>Equipamento verificado e funcionando normalmente.</p>

          </details class="formularioabaixo">

        </article>

>>>>>>> c20570d66507f581d89182651a036449b87660ac
      </section>
    </main>

  </div>

</body>

</html>
