<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamentos</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../Sidebar/sidebar.css">
    
    <link rel="stylesheet" href="stylepagamento.css">
</head>
<body>
    <div class="d-flex">
        <?php include '../Sidebar/sidebar.php'; ?>

        <main class="main-content">
            <h1 class="title-page">Pagamentos</h1>
            
            <div class="page-content">
                <div class="content-card">
                    <!-- conteúdo do pagamento -->
                    <div class="pix-info">
                        <p class="payment-phrase">
                            FAÇA SEU PAGAMENTO COM
                        </p>
                        
                        <div class="pix-title-row">
                            <img src="../../imagens/pix.png" alt="Logo Pix" class="pix-logo">
                            <span class="payment-method-title">PIX</span>
                        </div>
                        
                        <div class="qrcode-container">
                            <img src="../../imagens/qr code.png" alt="QR Code PIX" class="qrcode-img"> 
                        </div>
                        
                        <p class="pix-details">
                            CHAVE PIX: 19 99258-3065 <br>
                            NOME: ADAMASTOR 
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>