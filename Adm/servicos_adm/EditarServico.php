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

$id_servico = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$erro = '';
$sucesso = '';

if ($id_servico <= 0) {
    header('Location: ServicoDispoAdm.php?status=erro&msg=' . urlencode('ID de serviço não encontrado para edição.'));
    exit;
}

// variável para armazenar os dados do serviço
$servico_atual = null; 

// processa do formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // coleta e sanitização dos dados
    $nome_servico = trim($_POST['nome_servico']);
    
    // garante que o valor venha no formato correto
    $valor_servico = (float)str_replace(',', '.', trim($_POST['valor_servico']));
    
    $descricao_servico = trim($_POST['descricao_servico']);
    $descricao_adicional = trim($_POST['descricao_adicional']);    
    // campo oculto que guarda o nome da imagem
    $imagem_final = $_POST['imagem_atual']; 

    // query de UPDATE
    $sql = "UPDATE servicos_disponiveis 
            SET nome = :nome, valor = :valor, descricao = :descricao, 
                descricao_adicional = :descricao_adicional, 
                imagem = :imagem_final 
            WHERE id = :id";

    try {

        // seleção de imagem
        $upload_dir = '../../icones/'; 
        
        if (isset($_FILES['icon_upload']) && $_FILES['icon_upload']['error'] === UPLOAD_ERR_OK) {
            $novo_nome_imagem = basename($_FILES['icon_upload']['name']);
            $caminho_final = $upload_dir . $novo_nome_imagem;

            if (move_uploaded_file($_FILES['icon_upload']['tmp_name'], $caminho_final)) {
                $imagem_final = $novo_nome_imagem;
            } else {
                $erro = "Falha ao mover o arquivo de imagem para a pasta. Verifique as permissões.";
            }

        } elseif (!empty($_POST['icon_servico_bd_input'])) {
            $imagem_final = basename($_POST['icon_servico_bd_input']);
        }

        // executa update no banco
        if (empty($erro)) {
            if (!isset($pdo)) {
                 throw new \PDOException("Conexão com o banco de dados não estabelecida. Verifique o arquivo conexao.php.");
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id_servico, PDO::PARAM_INT);
            $stmt->bindParam(':nome', $nome_servico);
            $stmt->bindParam(':valor', $valor_servico);
            $stmt->bindParam(':descricao', $descricao_servico);
            $stmt->bindParam(':descricao_adicional', $descricao_adicional);
            $stmt->bindParam(':imagem_final', $imagem_final); 

            $stmt->execute();
            
            // redirecionamento imediato para a lista com status de edição
            header("Location: ServicoDispoAdm.php?status=editado");
            exit; 
        }

    } catch (\PDOException $e) {
        $erro = "Erro ao salvar as alterações no banco de dados: " . $e->getMessage();
    }
}


// carrega dados p o formulário
try {
    $sql_load = "SELECT nome, valor, descricao, descricao_adicional, link_usuario, imagem 
                 FROM servicos_disponiveis 
                 WHERE id = :id";
    $stmt_load = $pdo->prepare($sql_load);
    $stmt_load->bindParam(':id', $id_servico, PDO::PARAM_INT);
    $stmt_load->execute();
    $servico_atual = $stmt_load->fetch(PDO::FETCH_ASSOC);

    if (!$servico_atual) {
        header('Location: ServicoDispoAdm.php?status=erro&msg=' . urlencode('Serviço não encontrado.'));
        exit;
    }

} catch (\PDOException $e) {
    $erro = "Erro ao carregar dados do serviço: " . $e->getMessage();
    $servico_atual = null; 
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ADM | Editar Serviço</title> 

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
        <header class="page-header">
            <a href="ServicoDispoAdm.php" class="back-link">
                <img src="../../imagens/voltar.png" alt="Voltar" class="back-icon-img">
                <span class="back-text">Voltar</span>
            </a>
            <h1 class="title-page">Editar Serviço: <?php echo htmlspecialchars($servico_atual['nome'] ?? 'Serviço'); ?></h1>
        </header>
        
        <div class="form-container-wrapper">
        
        <?php if ($erro): ?>
            <div class="alert alert-danger" role="alert"><?php echo $erro; ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="alert alert-success" role="alert"><?php echo $sucesso; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" action="EditarServico.php?id=<?php echo $id_servico; ?>" class="service-edit-form">
            <div class="row">
                
                <div class="col-12 mb-3">
                    <label for="nome-servico-input" class="form-label-title">Nome do Serviço</label>
                    <div class="input-group-field">
                        <div class="icon-wrapper">
                            <img src="../../imagens/<?php echo htmlspecialchars($servico_atual['imagem'] ?? 'default.png'); ?>" 
                                 alt="Ícone do Serviço" class="input-icon-display">
                        </div>
                        <input type="text" class="form-control-custom" id="nome-servico-input" name="nome_servico" 
                               value="<?php echo htmlspecialchars($servico_atual['nome'] ?? ''); ?>" required placeholder="Digite o nome do serviço (Ex: Vazamento)">
                    </div>
                </div>

                <div class="col-12 mb-3"> <label for="valor-servico-input" class="form-label-title">Valor do Serviço (R$)</label>
                    <div class="input-group-field">
                        <div class="icon-wrapper">
                            <img src="../../imagens/etiqueta.png" alt="Valor" class="input-icon-display">
                        </div>
                        <input type="text" class="form-control-custom" id="valor-servico-input" name="valor_servico" 
                               value="<?php echo htmlspecialchars(str_replace('.', ',', $servico_atual['valor'] ?? '')); ?>" 
                               required placeholder="Ex: 50,00">
                    </div>
                </div>
                
                <div class="col-12 mb-3">
                    <label for="descricao-servico-input" class="form-label-title">Descrição Detalhada do Serviço</label>
                    <div class="input-group-field" style="height: auto;">
                         <div class="icon-wrapper" style="align-self: flex-start; margin-top: 10px;">
                            <img src="../../imagens/descricao.png" alt="Descrição" class="input-icon-display">
                        </div>
                        <textarea class="form-control-custom" id="descricao-servico-input" name="descricao_servico" rows="4" 
                                  required placeholder="Detalhes do serviço (máximo 255 caracteres)" 
                                  maxlength="255"><?php echo htmlspecialchars($servico_atual['descricao'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <label for="descricao-adicional-input" class="form-label-title">Descrição Adicional</label>
                    <div class="input-group-field" style="height: auto;">
                        <div class="icon-wrapper" style="align-self: flex-start; margin-top: 10px;">
                            <img src="../../imagens/descricao.png" alt="Atenção" class="input-icon-display">
                        </div>
                        <textarea class="form-control-custom" id="descricao-adicional-input" name="descricao_adicional" rows="2" 
                                  placeholder="Ex: O valor pode ser ajustado, etc."><?php echo htmlspecialchars($servico_atual['descricao_adicional'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="col-12 mb-4 p-4 border rounded bg-light" style="background-color: #f7f9fd !important;">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center mb-3">
                            <label class="form-label-title">Ícone Atual:</label>
                            <img src="../../imagens/<?php echo htmlspecialchars($servico_atual['imagem'] ?? ''); ?>" 
                                 id="service-icon-preview" style="width: 80px; height: 80px; object-fit: contain; border: 1px solid #ccc; padding: 5px; background: #fff; border-radius: 5px;">
                            </div>

                        <div class="col-md-5 mb-3">
                            <label for="icon-upload" class="form-label-title">Fazer Upload de um Novo Ícone</label>
                            <input type="file" class="form-control-file-hidden" id="icon-upload" name="icon_upload" 
                                   onchange="limparSelecaoBD(); this.closest('div').querySelector('.form-text').textContent = this.files.length ? this.files[0].name : 'Selecione para substituir o ícone atual.'">
                            
                            <label for="icon-upload" class="btn btn-primary w-100" style="background-color: #B1C9EF; border-color: #B1C9EF; color: #365885;">
                                Escolher Arquivo...
                            </label>
                        </div>
                        
                        <div class="col-md-4 mb-3 position-relative">
                            <label for="icon-search-input" class="form-label-title">Buscar Ícone no Banco de Dados</label>
                            <input type="text" class="form-control" id="icon-search-input" placeholder="Digite para buscar..." 
                                   onkeyup="sugerirIcones(this.value)">
                            <div id="icon-suggestions">
                                </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="imagem_atual" value="<?php echo htmlspecialchars($servico_atual['imagem'] ?? ''); ?>">
                    <input type="hidden" name="icon_servico_bd_input" id="icon-servico-bd-input" value="">
                    
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
// sugestão de icones
let timeoutBusca = null;

function sugerirIcones(termo) {
    clearTimeout(timeoutBusca);

    if (termo.length < 2) {
        document.getElementById('icon-suggestions').innerHTML = '';
        return;
    }

    timeoutBusca = setTimeout(() => {
        // faz uma requisição AJAX para o arquivo buscar_icones.php
        fetch(`buscar_icones.php?termo=${encodeURIComponent(termo)}`)
            .then(response => response.text())
            .then(html => {
                // o HTML retornado já vem com a estrutura de sugestões
                document.getElementById('icon-suggestions').innerHTML = html;
            })
            .catch(error => {
                console.error('Erro ao buscar ícones:', error);
            });
    }, 300);
}

function selecionarIcone(item) {
    const caminhoImagemCompleto = item.getAttribute('data-caminho-imagem');
    const nomeArquivo = caminhoImagemCompleto.split('/').pop(); 

    // define a imagem de pré-visualização
    document.getElementById('service-icon-preview').src = '../../imagens/' + nomeArquivo;

    // define o valor no campo hidden, indicando que a imagem foi selecionada do BD
    document.getElementById('icon-servico-bd-input').value = nomeArquivo;

    // limpa o input de arquivo, garantindo que a imagem de upload não seja enviada
    document.getElementById('icon-upload').value = '';
    
    // limpa a lista de sugestões
    document.getElementById('icon-suggestions').innerHTML = '';
    
    // atualiza o ícone do campo
    const inputIcon = document.querySelector('.input-group-field .icon-wrapper img');
    if (inputIcon) {
        inputIcon.src = '../../imagens/' + nomeArquivo;
    }
}

function limparSelecaoBD() {
    document.getElementById('icon-servico-bd-input').value = '';
    // tenta pré-visualizar o arquivo de upload
    const [file] = document.getElementById('icon-upload').files;
    if (file) {
        document.getElementById('service-icon-preview').src = URL.createObjectURL(file);
        // tenta atualizar o ícone do campo com o novo arquivo de upload
        const inputIcon = document.querySelector('.input-group-field .icon-wrapper img');
        if (inputIcon) {
            inputIcon.src = URL.createObjectURL(file);
        }
    }
}
</script>
</body>
</html>