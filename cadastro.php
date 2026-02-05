<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require 'db_connect.php';

    // coleta dos dados do formulário
    $nome = $_POST['nome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $rua = $_POST['rua'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $referencia = $_POST['referencia'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // validação básica
    if (
        empty($nome) || empty($telefone) || empty($email) ||
        empty($rua) || empty($numero) || empty($bairro) ||
        empty($cidade) || empty($estado) || empty($cep) || empty($senha)
    ) {
        $error_message = "Todos os campos obrigatórios devem ser preenchidos.";
    } else {
        // cria o hash da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            // query para inserir novo cliente usando PDO
            $sql = "INSERT INTO clientes 
                (nome, telefone, email, rua, numero, complemento, referencia, bairro, cidade, estado, cep, senha_hash)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([
                $nome,
                $telefone,
                $email,
                $rua,
                $numero,
                $complemento,
                $referencia,
                $bairro,
                $cidade,
                $estado,
                $cep,
                $senha_hash
            ])) {
                header("Location: index.php?status=cadastro_sucesso");
                exit();
            }
            
        } catch (PDOException $e) {
            // se der erro
            if ($e->getCode() == 23000) { // Código para violação de unique constraint
                $error_message = "Este e-mail já está cadastrado. Tente outro.";
            } else {
                $error_message = "Erro ao cadastrar. Por favor, tente novamente. Erro: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link rel="stylesheet" href="forms.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="auth-sidebar">
                <div class="logo-container">
                    <img src="imagens/logo.png" alt="Logo" class="logo">
                </div>
                <h1>Olá, seja bem vindo(a)!</h1>
                <p>Já tem uma conta? <a href="index.php">Faça login aqui!</a></p>
            </div>
            <div class="auth-form-container cadastro">
                <h2>Dados cadastrais</h2>

                <?php if(!empty($error_message)): ?>
                    <p style="color: red; text-align: center; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </p>
                <?php endif; ?>

                <?php if(isset($_GET['status']) && $_GET['status'] == 'cadastro_sucesso'): ?>
                    <p style="color: green; text-align: center; margin-bottom: 20px;">
                        Cadastro realizado com sucesso! Faça login para continuar.
                    </p>
                <?php endif; ?>

                <form action="cadastro.php" method="POST">
                    <h3>Dados pessoais</h3>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="nome">Nome completo</label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome ?? ''); ?>" required>
                        </div>
                        <div class="form-group half">
                            <label for="telefone">Telefone</label>
                            <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>

                    <h3>Endereço</h3>
                    <div class="form-row">
                        <div class="form-group big">
                            <label for="rua">Rua</label>
                            <input type="text" id="rua" name="rua" value="<?php echo htmlspecialchars($rua ?? ''); ?>" required>
                        </div>
                        <div class="form-group small">
                            <label for="numero">Nº</label>
                            <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($numero ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label for="bairro">Bairro</label>
                            <input type="text" id="bairro" name="bairro" value="<?php echo htmlspecialchars($bairro ?? ''); ?>" required>
                        </div>
                        <div class="form-group half">
                            <label for="cidade">Cidade</label>
                            <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($cidade ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group small">
                            <label for="estado">UF</label>
                            <input type="text" id="estado" name="estado" maxlength="2" value="<?php echo htmlspecialchars($estado ?? ''); ?>" required>
                        </div>
                        <div class="form-group small">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" value="<?php echo htmlspecialchars($cep ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label for="complemento">Complemento</label>
                            <input type="text" id="complemento" name="complemento" value="<?php echo htmlspecialchars($complemento ?? ''); ?>">
                        </div>
                        <div class="form-group half">
                            <label for="referencia">Referência</label>
                            <input type="text" id="referencia" name="referencia" value="<?php echo htmlspecialchars($referencia ?? ''); ?>">
                        </div>
                    </div>

                    <h3>Senha de acesso</h3>
                    <div class="form-group">
                        <input type="password" id="senha" name="senha" placeholder="Crie sua senha" required>
                    </div>

                    <button type="submit" class="auth-button">CADASTRAR</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // máscara para telefone (força input a formatar o texto em um padrão específico enquanto o usuário digita)
    document.getElementById('telefone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 10) {
            value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else {
            value = value.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        }
        e.target.value = value;
    });

    // máscara para CEP (força input a formatar o texto em um padrão específico enquanto o usuário digita)
    document.getElementById('cep').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(\d{5})(\d{0,3})/, '$1-$2');
        e.target.value = value;
    });

    // API de CEP
    document.getElementById('cep').addEventListener('blur', function(e) {
        const cep = e.target.value.replace(/\D/g, '');
        if (cep.length === 8) {
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('rua').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('estado').value = data.uf || '';
                    }
                })
                .catch(error => console.log('Erro ao buscar CEP:', error));
        }
    });
    </script>
</body>
</html>