<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

$possiblePaths = [
    '../../Sidebar/',       
];

$baseSidebar = '';
foreach ($possiblePaths as $path) {
    if (file_exists($path . 'sidebar.css')) {
        $baseSidebar = $path;
        break;
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);

// lista de páginas relacionadas
$paginasPerfilUsu = [
    'perfil.php', 
    'meus_agendamentos.php', 
    'reagendar.php', 
    'cancelar_agendamento.php', 
    'processar_reagendamento.php',
];

$paginasAgenda = ['agenda.php', 'endereco.php'];

$paginasServicosCliente = [
    'ServicosDispoCliente.php',
    'DetalheServico.php'
];

$paginasFeedback = [
    'indexusuario.php',
    'fazeravaliacao.php'
];

$isPagarActive = ($currentPage == 'indexpagamento.php') ? 'active' : '';
$isPerfilActive = in_array($currentPage, $paginasPerfilUsu) ? 'active' : '';
$isAgendaActive = in_array($currentPage, $paginasAgenda) ? 'active' : '';
$isFeedbackActive = in_array($currentPage, $paginasFeedback) ? 'active' : '';
$isServicosClienteActive = in_array($currentPage, $paginasServicosCliente) ? 'active' : '';
$isChatActive = ''; 

// função do wpp
$nomeUsuario = $_SESSION['user_name'] ?? 'Cliente';
$numeroADM = '5519996192031'; // número do suporte/admin
$mensagemZap = urlencode("Oi, meu nome é " . $nomeUsuario . " e gostaria de tirar uma dúvida.");
$linkWhatsApp = "https://wa.me/" . $numeroADM . "?text=" . $mensagemZap;
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
                <a href="<?= $baseSidebar ?>../Perfil_usuario/perfil.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isPerfilActive ? 'perfilSelecionado.png' : 'perfil.png' ?>" alt="Perfil">
                    <span>Perfil</span>
                </a>
            </li>

            <li class="<?= $isPagarActive ?>">
                <a href="<?= $baseSidebar ?>../Pagamento_usuario/indexpagamento.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isPagarActive ? 'pagamentoSelecionado.png' : 'pagamento.png' ?>" alt="Pagar">
                    <span>Pagar</span>
                </a>
            </li>

            <li class="<?= $isServicosClienteActive ?>">
                <a href="<?= $baseSidebar ?>../servicos_usuario/ServicosDispoCliente.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isServicosClienteActive ? 'servicoSelecionado.png' : 'servico.png' ?>" alt="Serviços">
                    <span>Serviços</span>
                </a>
            </li>

            <li class="<?= $isAgendaActive ?>">
                <a href="<?= $baseSidebar ?>../agenda_usuario/agenda.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isAgendaActive ? 'agendaSelecionado.png' : 'agenda.png' ?>" alt="Agenda">
                    <span>Agenda</span>
                </a>
            </li>

            <li class="<?= $isFeedbackActive ?>">
                <a href="<?= $baseSidebar ?>../Avaliacao_usuario/indexusuario.php"> 
                    <img src="<?= $baseSidebar ?>../../imagens/<?= $isFeedbackActive ? 'feedbackSelecionado.png' : 'feedback.png' ?>" alt="Feedback">
                    <span>Feedback</span>
                </a>
            </li>

            <!-- o chat abre o wpp -->
            <li class="<?= $isChatActive ?>">
                <a href="<?= $linkWhatsApp ?>" target="_blank"> 
                    <img src="<?= $baseSidebar ?>../../imagens/contatos.png" alt="Contato">
                    <span>Contato</span>
                </a>
            </li>
        </ul>
    </aside>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // garante que o script só rode após a página carregar completamente
        const sidebarItems = document.querySelectorAll(".sidebar li");

        // percorre todos os itens <li> da barra lateral
        sidebarItems.forEach(item => {
            const link = item.querySelector('a');
            
            // ignora o clique se for o botão do wpp 
            if (link && link.getAttribute('href').startsWith('https://wa.me')) return;

            item.addEventListener("click", function() {
                // se o item já está ativo, não faz nada e sai da função
                if (this.classList.contains('active')) return;

                // limpa o estado de todos os itens
                sidebarItems.forEach(i => {
                    // ignora o wpp também na limpeza
                    const iLink = i.querySelector('a');
                    if (iLink && iLink.getAttribute('href').startsWith('https://wa.me')) return; 

                    i.classList.remove("active"); 
                    const img = i.querySelector("img");
                    
                    // remove a palavra "Selecionado" do nome do arquivo da imagem
                    if(img) img.src = img.src.replace("Selecionado", "");
                    
                    const span = i.querySelector("span");
                    if(span) span.style.color = "#A7A7A7";
                });

                // ativa o item clicado
                this.classList.add("active"); 
                const img = this.querySelector("img");
                
                // se não tiver a palavra "Selecionado", insere ela antes da extensão do arquivo
                if(img && !img.src.includes("Selecionado")){
                    const ponto = img.src.lastIndexOf(".");
                    const novoSrc = img.src.slice(0, ponto) + "Selecionado" + img.src.slice(ponto);
                    img.src = novoSrc; // troca para a imagem que mostra como ativo
                }

                // define a cor do texto para o azul claro
                const span = this.querySelector("span");
                if(span) span.style.color = "#B1C9EF";
            });
        });
    });
</script>
</body>
</html>