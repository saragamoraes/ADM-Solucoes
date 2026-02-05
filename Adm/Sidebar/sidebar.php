<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

$possiblePaths = [
    '../../Sidebar/',
    '../../../Sidebar/',
];

$baseSidebar = '';
foreach ($possiblePaths as $path) {
    if (file_exists($path . 'sidebar.css')) {
        $baseSidebar = $path;
        break;
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);

// páginas agrupadas por seções
$paginasPerfilAdm = ['perfil_adm.php', 'relatorio_adm.php', 'relatorio.php']; // ✅ ADICIONADO 'relatorio.php'
$paginasAgendaAdm = ['agenda_visualizar.php', 'cancelar_agendamento_adm.php', 'reagendar_adm.php'];
$paginasServicosAdm = ['ServicoDispoAdm.php', 'EditarServico.php', 'AdicionarServico.php', 'RemoverService.php', 'buscar_icones.php'];
$paginasPagamentosAdm = ['indexpagamento.php'];
$paginasFeedbackAdm = ['feedback.php', 'avaliacao.php'];
$paginasContatoAdm = ['contatos_adm.php'];

// estados ativos
$isPerfilActive = in_array($currentPage, $paginasPerfilAdm) ? 'active' : '';
$isPagarActive = in_array($currentPage, $paginasPagamentosAdm) ? 'active' : '';
$isServicosActive = in_array($currentPage, $paginasServicosAdm) ? 'active' : '';
$isAgendaActive = in_array($currentPage, $paginasAgendaAdm) ? 'active' : '';
$isFeedbackActive = in_array($currentPage, $paginasFeedbackAdm) ? 'active' : '';
$isContatoActive = in_array($currentPage, $paginasContatoAdm) ? 'active' : '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseSidebar ?>sidebar.css">
</head>
<body>
    <aside class="sidebar">
        <ul>
            <li class="<?= $isPerfilActive ?>">
                <a href="../Perfil_adm/perfil_adm.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isPerfilActive ? 'perfilSelecionado.png' : 'perfil.png' ?>" alt="Perfil">
                    <span>Perfil</span>
                </a>
            </li>

            <li class="<?= $isPagarActive ?>">
                <a href="<?= $baseSidebar ?>../Pagamento_adm/indexpagamento.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isPagarActive ? 'pagamentoSelecionado.png' : 'pagamento.png' ?>" alt="Pagar">
                    <span>Pagar</span>
                </a>
            </li>

            <li class="<?= $isServicosActive ?>">
                <a href="<?= $baseSidebar ?>../servicos_adm/ServicoDispoAdm.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isServicosActive ? 'servicoSelecionado.png' : 'servico.png' ?>" alt="Serviços">
                    <span>Serviços</span>
                </a>
            </li>

            <li class="<?= $isAgendaActive ?>">
                <a href="<?= $baseSidebar ?>../agenda_adm/agenda_visualizar.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isAgendaActive ? 'agendaSelecionado.png' : 'agenda.png' ?>" alt="Agenda">
                    <span>Agenda</span>
                </a>
            </li>

            <li class="<?= $isFeedbackActive ?>">
                <a href="<?= $baseSidebar ?>../Avaliacao_adm/avaliacao.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isFeedbackActive ? 'feedbackSelecionado.png' : 'feedback.png' ?>" alt="Feedback">
                    <span>Feedback</span>
                </a>
            </li>

            <li class="<?= $isContatoActive ?>">
                <a href="<?= $baseSidebar ?>../Contatos_adm/contatos_adm.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isContatoActive ? 'contatoSelecionado.png' : 'contatos.png' ?>" alt="Contatos">
                    <span>Contatos</span>
                </a>
            </li>
        </ul>
    </aside>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const sidebarItems = document.querySelectorAll(".sidebar li");

            sidebarItems.forEach(item => {
                const link = item.querySelector('a');
                
                // ignora o botão de wpp
                if (link && link.getAttribute('href').startsWith('https://wa.me')) return;

                item.addEventListener("click", function() {
                    if (this.classList.contains('active')) return;

                    sidebarItems.forEach(i => {
                        const iLink = i.querySelector('a');
                        if (iLink && iLink.getAttribute('href').startsWith('https://wa.me')) return; 
                        
                        i.classList.remove("active");
                        const img = i.querySelector("img");
                        if (img) img.src = img.src.replace("Selecionado", "");
                        const span = i.querySelector("span");
                        if (span) span.style.color = "#A7A7A7";
                    });

                    this.classList.add("active");
                    const img = this.querySelector("img");
                    if (img && !img.src.includes("Selecionado")) {
                        const ponto = img.src.lastIndexOf(".");
                        const novoSrc = img.src.slice(0, ponto) + "Selecionado" + img.src.slice(ponto);
                        img.src = novoSrc;
                    }

                    const span = this.querySelector("span");
                    if (span) span.style.color = "#B1C9EF";
                });
            });
        });
    </script>
</body>
</html>