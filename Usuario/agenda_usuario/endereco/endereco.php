<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Endereço para envio</title>
    <link rel="stylesheet" href="endereco.css">
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./sidebar.css">
</head>
<body>
<div class="d-flex">
    <?php
        include '../../Sidebar/sidebar.php'; ?>
    <main class="main-content">
        <a href="../agenda.php" class="back-button">
            <img src="../../../imagens/voltar.png" alt="Voltar" width="20" height="20">
            <span class="back-text">Voltar</span>
        </a>
        <h1 class="title-page">Endereço para envio</h1>

        <form class="form-endereco" method="POST" action="salvar_endereco.php">
            
            <label>CEP:</label>
            <input type="text" placeholder="CEP" id="cep" name="cep" class="form-control" maxlength="9" required>
            <p id="cep-feedback" class="text-danger small"></p> 

            <div class="row">
                <div class="col">
                    <label>Endereço (Rua/Av):</label>
                    <input type="text" placeholder="Rua" name="rua" id="rua" class="form-control" required>
                </div>
                <div class="col pequeno">
                    <label>Nº:</label>
                    <input type="text" placeholder="Número" name="numero" class="form-control" required>
                </div>
            </div>
            
            <label>Complemento:</label>
            <input type="text" placeholder="Complemento" name="complemento" id="complemento" class="form-control">

            <label>Ponto de referência:</label>
            <input type="text" placeholder="Ponto de referência" name="referencia" class="form-control">

            <label>Bairro:</label>
            <input type="text" placeholder="Bairro" name="bairro" id="bairro" class="form-control" required>

            <label>Cidade:</label>
            <input type="text" placeholder="Cidade" name="cidade" id="cidade" class="form-control" required>

            <label>Estado (UF):</label>
            <input type="text" placeholder="Estado" name="estado" id="estado" class="form-control" maxlength="2" required>

            <button type="submit" class="salvar">SALVAR</button>
        </form>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cepInput = document.getElementById('cep');
        const ruaInput = document.getElementById('rua');
        const bairroInput = document.getElementById('bairro');
        const cidadeInput = document.getElementById('cidade');
        const estadoInput = document.getElementById('estado');
        const numInput = document.querySelector('input[name="numero"]');
        const feedback = document.getElementById('cep-feedback');

        // função para limpar os campos de endereço
        function limparEndereco() {
            ruaInput.value = '';
            bairroInput.value = '';
            cidadeInput.value = '';
            estadoInput.value = '';
            feedback.textContent = '';
        }

        // função principal para buscar o CEP
        function buscarCep() {
            // remove caracteres não numéricos
            let cep = cepInput.value.replace(/\D/g, ''); 
            
            limparEndereco(); 
            
            // verifica se o CEP tem 8 dígitos
            if (cep.length !== 8) {
                return;
            }
            
            feedback.textContent = 'Buscando endereço...';
            
            // API de cep
            const url = `https://viacep.com.br/ws/${cep}/json/`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        feedback.textContent = 'CEP não encontrado.';
                        return;
                    }

                    // preenche os campos
                    ruaInput.value = data.logradouro || '';
                    bairroInput.value = data.bairro || '';
                    cidadeInput.value = data.localidade || '';
                    estadoInput.value = data.uf || '';
                    
                    feedback.textContent = '';
                    numInput.focus();

                })
                .catch(error => {
                    console.error('Erro na requisição ViaCEP:', error);
                    feedback.textContent = 'Erro de comunicação com o serviço de CEP.';
                });
        }
        
        cepInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 5) {
                e.target.value = value.substring(0, 5) + '-' + value.substring(5, 8);
            } else {
                e.target.value = value;
            }
        });
        
        // busca cep
        cepInput.addEventListener('blur', buscarCep);
        cepInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); 
                buscarCep();
            }
        });
    });
</script>
</body>
</html>