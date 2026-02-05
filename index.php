<?php
session_start();

// ativar display de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include("db_connect.php");
    
    if (!isset($pdo)) {
        throw new Exception("Conexão PDO não estabelecida");
    }
} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// se ja estiver logado, redireciona conforme o tipo de usuário
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] == 'admin') {
        header("Location: Adm/Perfil_adm/perfil_adm.php");
    } else {
        header("Location: Usuario/Perfil_usuario/perfil.php");
    }
    exit();
}

$error_message = '';

// função para login bem sucedido
function login_success($user) {
    $_SESSION['user_id'] = $user['id_cliente'];
    $_SESSION['user_name'] = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_type'] = $user['tipo_usuario'];
    
    error_log("Login bem-sucedido para: " . $user['email'] . " - Tipo: " . $user['tipo_usuario']);
    
    if ($user['tipo_usuario'] == 'admin') {
        header("Location: Adm/Perfil_adm/perfil_adm.php");
    } else {
        header("Location: Usuario/Perfil_usuario/perfil.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $error_message = "Por favor, preencha todos os campos.";
    } else {
        try {
            $sql = "SELECT id_cliente, nome, email, senha_hash, tipo_usuario FROM clientes WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Erro ao preparar a consulta");
            }
            
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                
                // verificar a senha
                if (password_verify($senha, $user['senha_hash'])) {
                    // login bem sucedido
                    login_success($user);
                } else {
                    // tentar verificar como MD5 (para compatibilidade)
                    if (md5($senha) === $user['senha_hash']) {
                        // Se for MD5, converter para Bcrypt
                        $novo_hash = password_hash($senha, PASSWORD_BCRYPT);
                        $update_sql = "UPDATE clientes SET senha_hash = ? WHERE id_cliente = ?";
                        $update_stmt = $pdo->prepare($update_sql);
                        $update_stmt->execute([$novo_hash, $user['id_cliente']]);
                        
                        login_success($user);
                    } else {
                        // tentar como texto plano
                        if ($user['senha_hash'] === $senha) {
                            // converter para Bcrypt (armazena senha dos usuarios de forma segura no bd)
                            $novo_hash = password_hash($senha, PASSWORD_BCRYPT);
                            $update_sql = "UPDATE clientes SET senha_hash = ? WHERE id_cliente = ?";
                            $update_stmt = $pdo->prepare($update_sql);
                            $update_stmt->execute([$novo_hash, $user['id_cliente']]);
                            
                            login_success($user);
                        } else {
                            $error_message = "E-mail ou senha inválidos.";
                        }
                    }
                }
            } else {
                $error_message = "E-mail ou senha inválidos.";
            }
        } catch (PDOException $e) {
            $error_message = "Erro no sistema. Tente novamente.";
            error_log("ERRO PDO no login: " . $e->getMessage());
        } catch (Exception $e) {
            $error_message = "Erro no sistema. Tente novamente.";
            error_log("ERRO Geral no login: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="forms.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-wrapper">

        <div class="auth-sidebar">
            <div class="logo-container">
                <img src="imagens/logo.png" alt="Logo" class="logo">
            </div>
            <h1>Olá, seja bem-vindo(a) de volta!</h1>
            <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se aqui!</a></p>
        </div>

        <div class="auth-form-container login">

            <h2>Entrar</h2>

            <?php if(!empty($error_message)): ?>
                <p class="msg-error"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>

            <?php if(isset($_GET['status']) && $_GET['status'] == 'cadastro_sucesso'): ?>
                <p style="color: green; text-align: center; margin-bottom: 20px;">
                    Cadastro realizado com sucesso! Faça login para continuar.
                </p>
            <?php endif; ?>

            <form action="index.php" method="POST">

                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" placeholder="Digite seu e-mail" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="senha" placeholder="Digite sua senha" required>
                </div>

                <button type="submit" class="auth-button">ENTRAR</button>

            </form>
        </div>
    </div>
</div>
</body>
</html>