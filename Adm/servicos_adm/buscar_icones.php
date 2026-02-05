<?php
include '../../db_connect.php';

// receber o termo de busca
$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';

// realizar a busca no banco de dados
if (!empty($termo) && isset($pdo)) {
    // usa LIKE para buscar termos semelhantes nas colunas
    $sql = "SELECT id_icone, nome_servico, caminho_imagem 
             FROM servico_icones 
             WHERE nome_servico LIKE :termo1 OR palavras_chave LIKE :termo2 
             LIMIT 6";

    $stmt = $pdo->prepare($sql);
    $termo_like = '%' . $termo . '%';
    $stmt->bindParam(':termo1', $termo_like, PDO::PARAM_STR);
    $stmt->bindParam(':termo2', $termo_like, PDO::PARAM_STR);
    $stmt->execute();
    $icones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // gerar o HTML para as sugestões
    $html = '';
    if (count($icones) > 0) {
        $html .= '<div class="icon-suggestions-grid">';
        foreach ($icones as $icone) {
            // pega apenas o nome do arquivo
            $nome_arquivo = basename($icone['caminho_imagem']);
            
            $caminho_final_browser = '../../icones/' . $nome_arquivo;

            $html .= '<div class="suggestion-item" 
                            data-caminho-imagem="' . htmlspecialchars($nome_arquivo) . '" 
                            data-nome-servico="' . htmlspecialchars($icone['nome_servico']) . '"
                            onclick="selecionarIcone(this)">';
            
            // usa o caminho no atributo src
            $html .= '<img src="' . htmlspecialchars($caminho_final_browser) . '" 
                          alt="' . htmlspecialchars($icone['nome_servico']) . '" 
                          class="suggestion-image"
                          onerror="this.src=\'../../icones/default-icon.png\'">'; 
            $html .= '<span class="suggestion-name">' . htmlspecialchars($icone['nome_servico']) . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';
    } else {
        $html .= '<p class="no-suggestions">Nenhum ícone encontrado. Faça o upload do seu.</p>';
    }

    echo $html;

} else {
    echo '';
}
?>