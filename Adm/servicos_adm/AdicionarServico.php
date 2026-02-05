<?php
include '../../db_connect.php';

$baseSidebar = '../Sidebar/';
$baseImages = '../Sidebar/imagens/';

// URLs absolutas para funcionar de qualquer lugar
$urlPerfil = '/Site/Adm/Usuario/perfil.php';
$urlPagamentos = '/Site/Adm/Usuario/indexpagamento.php';
$urlServicos = '/Site/Adm/Servicos/ServicoDispoAdm.php'; 
$urlAgenda = '/Site/Adm/agenda_adm/agenda_visualizar.php';
$urlFeedback = '/Site/Adm/Usuario/feedback.php';
$urlChat = '/Site/Adm/Usuario/chat.php';

$erro = '';
$sucesso = '';

// processa formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // coleta dos dados do formulário
    $nome_servico = trim($_POST['nome_servico'] ?? '');
    
    // garante que o valor venha no formato correto
    $valor_servico_str = str_replace(',', '.', trim($_POST['valor_servico'] ?? '0'));
    $valor_servico = (float)$valor_servico_str; 
    
    $descricao_servico = trim($_POST['descricao_servico'] ?? '');
    $descricao_adicional = trim($_POST['descricao_adicional'] ?? '');
    
    // define um valor padrão para link_usuario se não estiver no formulário
    $link_usuario = 'detalhe_servico.php'; 
    
    // logica upload de imagem
    $imagem_final = ''; 
    $upload_dir = '../../icones/'; 

    // se uma imagem foi selecionada do bd
    if (!empty($_POST['icon_servico_bd'])) {
        $imagem_final = basename($_POST['icon_servico_bd']);

    // se um arquivo foi updado
    } elseif (isset($_FILES['icon_servico_upload']) && $_FILES['icon_servico_upload']['error'] === UPLOAD_ERR_OK) {
        $novo_nome_imagem = basename($_FILES['icon_servico_upload']['name']);
        $caminho_final = $upload_dir . $novo_nome_imagem;

        // tenta mover o arquivo upado
        if (move_uploaded_file($_FILES['icon_servico_upload']['tmp_name'], $caminho_final)) {
            $imagem_final = $novo_nome_imagem;
        } else {
            $erro = "Falha ao mover o arquivo de imagem. Verifique as permissões da pasta '../../icones/'.";
        }
    }


    // insert no bd
    if (empty($erro) && !empty($nome_servico) && $valor_servico >= 0 && !empty($imagem_final)) {
                $sql = "INSERT INTO servicos_disponiveis 
                 (nome, valor, descricao, descricao_adicional, link_usuario, imagem, ativo)
                 VALUES (:nome, :valor, :descricao, :descricao_adicional, :link_usuario, :imagem_final, 1)";
        
        try {
            // verifica se a variável $pdo está disponível
            if (!isset($pdo)) {
                 throw new \PDOException("Conexão com o banco de dados não estabelecida. Verifique o arquivo conexao.php.");
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nome', $nome_servico);
            $stmt->bindParam(':valor', $valor_servico);
            $stmt->bindParam(':descricao', $descricao_servico);
            $stmt->bindParam(':descricao_adicional', $descricao_adicional);
            $stmt->bindParam(':link_usuario', $link_usuario);
            $stmt->bindParam(':imagem_final', $imagem_final); 

            $stmt->execute();
            
            // redireciona para a lista de serviços
            header("Location: ServicoDispoAdm.php?status=adicionado");
            exit; 

        } catch (\PDOException $e) {
            $erro = "Erro ao adicionar serviço no banco de dados: " . $e->getMessage();
        }
    } elseif (empty($erro)) {
         $erro = "Por favor, preencha todos os campos obrigatórios e selecione uma imagem.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ADM | Adicionar Novo Serviço</title> 

<link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../Sidebar/sidebar.css">

<link rel="stylesheet" href="ServicoDispoAdminCss.css"> 

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="d-flex">
        <?php include '../Sidebar/sidebar.php'; ?>

          <main class="main-content">
        <div class="page-header">
            <a href="ServicoDispoAdm.php" class="back-link">
            <img src="../../imagens/voltar.png" alt="Voltar para a Lista de Serviços" class="back-icon-img">
            <span class="back-text">Voltar</span>
            </a>
            <h1 class="title-page">Adicionar Serviço</h1>
        </div>
        
        <div class="form-container-wrapper">
        
            <?php if ($erro): ?>
                <div class="alert alert-danger" role="alert"><?php echo $erro; ?></div>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <div class="alert alert-success" role="alert"><?php echo $sucesso; ?></div>
            <?php endif; ?>
        
        
            <form class="service-edit-form" enctype="multipart/form-data" method="POST"> 
            <div class="row g-4">
                
<div class="col-12 col-md-6 position-relative">
        <label class="form-label-title">Serviço:</label>
         <div class="input-group-field">
            <input type="file" id="icon-upload" name="icon_servico_upload" accept="image/*" class="form-control-file-hidden" onchange="previewImage(event); deselecionarIconeBD()">

            <label for="icon-upload" class="icon-upload-label-inline">
              <div class="icon-wrapper clickable-icon-wrapper">
                <img src="../../imagens/camera.png" alt="Ícone de Upload" class="input-icon-display" id="service-icon-preview"> 
              </div>
            </label>
            
            <input type="text" 
                class="form-control-custom" 
                placeholder="Nome do Serviço"
                id="nome-servico-input" 
                onkeyup="buscarSugestoesIcone(this.value)" name="nome_servico" required>
         </div>
        
         <input type="hidden" name="icon_servico_bd" id="icon-servico-bd-input" value="">

                  <div id="icon-suggestions">
         </div>
        </div>
                
                <div class="col-12 col-md-6">
                <label class="form-label-title">Valor (R$):</label>
                <div class="input-group-field">
                        <div class="icon-wrapper">
                        <img src="../../imagens/etiqueta.png" alt="Valor" class="input-icon-display">
                        <img src="../../imagens/editar.png" alt="Editar" class="edit-icon-grouped">
                        </div>
                    <input type="text" name="valor_servico" class="form-control-custom" placeholder="0,00" required> </div>
                </div>
                
                <div class="col-12 col-md-6">
                <label class="form-label-title">Descrição:</label>
                <div class="input-group-field">
                        <div class="icon-wrapper">
                        <img src="../../imagens/descricao.png" alt="Descrição" class="input-icon-display">
                        <img src="../../imagens/editar.png" alt="Editar" class="edit-icon-grouped">
                        </div>
                    <input type="text" name="descricao_servico" class="form-control-custom" placeholder="Descrição detalhada do serviço" required> </div>
                </div>

                <div class="col-12 col-md-6">
                <label class="form-label-title">Descrição Adicional:</label>
                <div class="input-group-field">
                        <div class="icon-wrapper">
                        <img src="../../imagens/descricao.png" alt="Descrição Adicional" class="input-icon-display">
                        <img src="../../imagens/editar.png" alt="Editar" class="edit-icon-grouped">
                        </div>
                    <input type="text" name="descricao_adicional" class="form-control-custom" placeholder="Descrição opcional (Termos, Condições)"> </div>
                </div>
                
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-form btn-save">
                <img src="../../imagens/salvar.png" alt="Salvar" class="btn-icon">
                    Salvar
                </button>
                <a href="ServicoDispoAdm.php" class="btn-form btn-cancel">
                <img src="../../imagens/cancelar.png" alt="Cancelar" class="btn-icon">
                    Cancelar
                </a>
            </div>
            </form>
        </div>
  </main>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// variável para armazenar o timeout da busca
let searchTimeout;

function buscarSugestoesIcone(termo) {
  // limpa o timeout anterior, se houver
  clearTimeout(searchTimeout);

  // configura um novo timeout para buscar após 300ms
  searchTimeout = setTimeout(() => {
    const suggestionsContainer = document.getElementById('icon-suggestions');
    
    // limpa as sugestões se o termo for muito curto
    if (termo.length < 2) {
      suggestionsContainer.innerHTML = '';
      return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'buscar_icones.php?termo=' + encodeURIComponent(termo), true);
    
    xhr.onload = function() {
      if (xhr.status === 200) {
        // insere o HTML retornado no container de sugestões
        suggestionsContainer.innerHTML = xhr.responseText;
      } else {
        suggestionsContainer.innerHTML = '<p class="error-suggestions">Erro ao buscar ícones.</p>';
      }
    };

    xhr.onerror = function() {
      suggestionsContainer.innerHTML = '<p class="error-suggestions">Erro de rede.</p>';
    };

    xhr.send();
  }, 300); // 300ms de atraso
}

// exibe a pré-visualização da imagem de upload e limpa a seleção do BD
function previewImage(event) {
  const preview = document.getElementById('service-icon-preview');
  const file = event.target.files[0];
  
  // define a imagem base se o usuário remover o arquivo de upload
  if (!file) {
    preview.src = '../../imagens/camera.png'; 

  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
    }
    reader.readAsDataURL(file);
  }
  deselecionarIconeBD();
}

function selecionarIcone(item) {
    const caminhoImagem = item.getAttribute('data-caminho-imagem');
    const nomeServico = item.getAttribute('data-nome-servico');
    
    const nomeArquivo = caminhoImagem.split('/').pop(); 
    const caminhoCompleto = '../../icones/' + nomeArquivo; 

    // define a imagem de pré-visualização
    document.getElementById('service-icon-preview').src = caminhoCompleto;

    document.getElementById('icon-servico-bd-input').value = nomeArquivo;

    document.getElementById('icon-upload').value = '';
    
    document.getElementById('nome-servico-input').value = nomeServico;

    document.getElementById('icon-suggestions').innerHTML = '';
}

function deselecionarIconeBD() {
  document.getElementById('icon-servico-bd-input').value = '';
}
</script>
</body>
</html>