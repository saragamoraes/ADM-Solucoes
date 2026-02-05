<?php
// fuso
date_default_timezone_set('America/Sao_Paulo'); 

session_start();

// verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// pega id do usuario logado
$cliente_logado_id = $_SESSION['user_id']; // ✅ AGORA USA O ID DA SESSÃO
// ----------------------------------------

include '../../db_connect.php';

// consulta endereço principal
$sql_endereco_principal = "
    SELECT 
        rua, numero, referencia, complemento, bairro, cidade, estado 
    FROM 
        clientes 
    WHERE 
        id_cliente = ?
"; 

$stmt_endereco_principal = $pdo->prepare($sql_endereco_principal);
$stmt_endereco_principal->execute([$cliente_logado_id]);
$endereco_principal = $stmt_endereco_principal->fetch(PDO::FETCH_ASSOC);

// consulta outros endereços
$sql_outros_enderecos = "
    SELECT 
        id_endereco, rua, numero, complemento, referencia, bairro, cidade, estado 
    FROM 
        enderecos_cliente  
    WHERE 
        id_cliente = ?
    ORDER BY 
        id_endereco DESC
";

$stmt_outros_enderecos = $pdo->prepare($sql_outros_enderecos);
$stmt_outros_enderecos->execute([$cliente_logado_id]);
$outros_enderecos = $stmt_outros_enderecos->fetchAll(PDO::FETCH_ASSOC);

// consulta agendamentos
$sql_agendados = "
    SELECT hora_inicio, hora_fim 
    FROM agendamentos 
    WHERE data_agendamento = CURDATE() 
    AND status != 'Cancelado'
";

$stmt_agendados = $pdo->query($sql_agendados);
$horarios_indisponiveis = [];

while ($row = $stmt_agendados->fetch(PDO::FETCH_ASSOC)) {
    $inicio = strtotime($row['hora_inicio']);
    $fim = strtotime($row['hora_fim']);
    for ($t = $inicio; $t < $fim; $t += 3600) {
        $horarios_indisponiveis[] = date('H:i', $t);
    }
}

// consulta serviços
$sql_servicos = "SELECT id, nome, valor, imagem, descricao FROM servicos_disponiveis WHERE ativo = 1 ORDER BY nome";
$stmt_servicos = $pdo->query($sql_servicos);

$servicos_db = [];
$valores_servicos_db = [];
$nomes_servicos_db = [];

while ($row = $stmt_servicos->fetch(PDO::FETCH_ASSOC)) {
    $servicos_db[] = $row;
    $valores_servicos_db[$row['id']] = floatval($row['valor']);
    $nomes_servicos_db[$row['id']] = $row['nome'];
}

// verifica se o array de serviços do banco de dados está vazio
if (empty($servicos_db)) {
    // se estiver vazio, define uma mensagem de aviso
    $feedback_message = '<div class="alert alert-warning text-center" role="alert">⚠️ Nenhum serviço disponível no momento. Entre em contato conosco.</div>';
}

// prepara os dados de valores e nomes dos serviços em formato JSON
$json_valores_servicos = json_encode($valores_servicos_db);
$json_nomes_servicos = json_encode($nomes_servicos_db);

// lógica para geração dos próximos 7 dias
$hoje = new DateTime();
$dias = []; 

// loop para criar objetos de data que totaliza 7 dias (uma semana)
for ($i = 0; $i < 7; $i++) {
    $dia = clone $hoje; 
    $dia->modify("+$i day"); 
    $dias[] = $dia;
}

// variável placeholder para dias específicos em que o agendamento não deve ser permitido.
$dias_indisponiveis = [""];

if (!isset($feedback_message)) {
    $feedback_message = ''; 

    // verifica se o parâmetro status na URL indica que um endereço foi salvo
    if (isset($_GET['status']) && $_GET['status'] == 'endereco_salvo') {
        $feedback_message = '<div class="alert alert-success text-center" role="alert">📍 Novo endereço salvo com sucesso!</div>';
    }

    // verifica outros status de sucesso ou erro
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'success') {
            $feedback_message = '<div class="alert alert-success text-center" role="alert">✅ Agendamento realizado com sucesso!</div>';
        } elseif ($_GET['status'] == 'error') {
            $feedback_message = '<div class="alert alert-danger text-center" role="alert">❌ ERRO: Não foi possível realizar o agendamento. Tente novamente!</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Livvic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
    <link rel="stylesheet" href="../Sidebar/sidebar.css">
    <link rel="stylesheet" href="agenda.css">
    <style>
        .horario-btn.disabled {
            background-color: #e0e0e0 !important;
            color: #888;
            border-color: #ccc;
            cursor: not-allowed;
        }

        .horario-btn.active {
            background-color: #365885;
            color: #fff;
            border-color: #365885;
        }

        .servico-btn.active {
            background-color: #1e4570;
            color: #fff;
            border: 1px solid #1e4570;
        }
        
        .endereco-item {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            cursor: pointer;
            border-radius: 8px;
            transition: background-color 0.2s;
            position: relative;
        }
        
        .endereco-item:hover {
            background-color: #f7f7f7;
        }

        .endereco-radio-container {
            flex-shrink: 0;
            width: 30px; 
            height: 30px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .endereco-radio {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #365885;
            border-radius: 50%;
            cursor: pointer;
            position: relative;
        }

        .endereco-radio:checked {
            background-color: #365885;
            border-color: #365885;
        }

        .endereco-radio:checked::after {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: white;
            font-size: 10px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .endereco-content {
            flex-grow: 1;
        }
        
        .card-endereco {
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 0;
        }

        .card-footer {
            border-top: 1px solid #e0e0e0;
            padding: 8px 15px;
            text-align: right;
        }

        .sem-servicos {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include '../Sidebar/sidebar.php'; ?>
        <main class="main-content">
            <h1 class="title-page">Agenda</h1>
            <?= $feedback_message ?>
            
            <?php if (empty($servicos_db)): ?>
                <div class="alert alert-info text-center">
                    <p>Nenhum serviço disponível no momento.</p>
                    <p>Entre em contato conosco para mais informações.</p>
                </div>
            <?php else: ?>
            
            <form action="../../processa_agendamento.php" method="POST" id="formAgendamento">
            
            <div class="section">
                <input type="hidden" name="data_selecionada" id="data_selecionada" value="<?= $dias[0]->format("Y-m-d") ?>">
                <h2>Selecione a data:</h2>
                <div class="calendar">
                    <?php foreach ($dias as $index => $dia): 
                        $numero = $dia->format("d");
                        $semana = $dia->format("D");
                        $semana_pt = [
                            "Sun" => "D","Mon" => "S","Tue" => "T",
                            "Wed" => "Q","Thu" => "Q","Fri" => "S","Sat" => "S"
                        ][$semana];

                        $classes = "day";
                        if ($dia->format("Y-m-d") === (new DateTime())->format("Y-m-d")) $classes .= " today";
                        if (in_array($numero, $dias_indisponiveis)) $classes .= " disabled";
                        if ($index === 0) $classes .= " selected";
                    ?>
                        <div class="<?= $classes ?>" data-date="<?= $dia->format("Y-m-d") ?>">
                            <span class="weekday"><?= $dia->format("Y-m-d") === (new DateTime())->format("Y-m-d") ? "HOJE" : $semana_pt ?></span>
                            <span class="number"><?= $numero ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="section">
                <input type="hidden" name="servicos_ids_selecionados" id="servicos_ids_selecionados"> 
                <h2>Selecione o serviço:</h2>
                <div class="servicos" id="lista-servicos">
                    <?php foreach($servicos_db as $servico): ?>
                        <button type="button" class="servico-btn" data-servico-id="<?= $servico['id'] ?>" data-servico-valor="<?= $servico['valor'] ?>">
                            <?= htmlspecialchars($servico['nome']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section">
                <input type="hidden" name="endereco_id_selecionado" id="endereco_id_selecionado" value=""> 
                <h2>Selecione o endereço:</h2>
                <div class="card-endereco">
                    
                    <div class="endereco-item" data-endereco-type="principal">
                        <div class="endereco-radio-container">
                            <input type="radio" class="endereco-radio" name="endereco_selecionado" value="principal_<?= $cliente_logado_id ?>" checked>
                        </div>
                        <div class="endereco-content">
                            <p class="rua">
                                <?= htmlspecialchars($endereco_principal['rua'] ?? 'Endereço não encontrado') ?>, nº 
                                <?= htmlspecialchars($endereco_principal['numero'] ?? 'S/N') ?> – 
                                <?= htmlspecialchars($endereco_principal['complemento'] ?? 'Complemento não informado') ?> –
                                <?= htmlspecialchars($endereco_principal['bairro'] ?? 'Bairro não informado') ?>
                            </p>
                            <p class="referencia">Ponto de referência: <?= htmlspecialchars($endereco_principal['referencia'] ?? 'Não informado') ?>.</p>
                            <p class="cidade">
                                <?= htmlspecialchars($endereco_principal['cidade'] ?? 'Cidade não informada') ?>, 
                                <?= htmlspecialchars($endereco_principal['estado'] ?? 'SP') ?>
                                (Endereço de Cadastro)
                            </p>
                        </div>
                    </div>
                    
                    <?php foreach($outros_enderecos as $endereco): ?>
    <div class="endereco-separator"></div>
    
    <div class="endereco-item" data-endereco-type="secundario" id="endereco-<?= $endereco['id_endereco'] ?>">
        <div class="endereco-radio-container">
            <input type="radio" class="endereco-radio" name="endereco_selecionado" value="<?= $endereco['id_endereco'] ?>">
        </div>
        <div class="endereco-content">
            <p class="rua">
                <?= htmlspecialchars($endereco['rua']) ?>, nº 
                <?= htmlspecialchars($endereco['numero']) ?> – 
                <?= htmlspecialchars($endereco['complemento']) ?> –
                <?= htmlspecialchars($endereco['bairro']) ?>
            </p>
            <p class="referencia">Ponto de referência: <?= htmlspecialchars($endereco['referencia']) ?>.</p>
            <p class="cidade">
                <?= htmlspecialchars($endereco['cidade']) ?>, 
                <?= htmlspecialchars($endereco['estado']) ?>
            </p>
        </div>

        <button type="button" class="btn-excluir-endereco" data-id="<?= $endereco['id_endereco'] ?>">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div>
            <?php endforeach; ?>
            <div class="card-footer">
                <button type="button" class="outro-endereco" onclick="window.location.href='./endereco/endereco.php'">
                    Outro endereço
                </button>
            </div>
        </div>
    </div>

            <div class="section">
                <input type="hidden" name="horario_selecionado" id="horario_selecionado">
                <h2>Horários disponíveis:</h2>
                <div class="horarios" id="listaHorarios">
                    <?php 
                    $slots = [];
                    for ($h = 7; $h <= 11; $h++) $slots[] = sprintf('%02d:00', $h);
                    for ($h = 14; $h <= 17; $h++) $slots[] = sprintf('%02d:00', $h);
                    
                    foreach ($slots as $hora) {
                        $classe = in_array($hora, $horarios_indisponiveis) ? "disabled" : "";
                        echo "<button type='button' class='horario-btn $classe' ".($classe ? "disabled" : "").">$hora</button>";
                    }
                    ?> 
                </div>
            </div>

            <div class="section">
                <p><strong>Valor:</strong> <span id="valorServicos">R$ 0,00</span></p>
            </div>

             <div class="agendamento-contato">
                <p class="label">Conversar com Adamastor: </p>
                <a href="https://wa.me/55<?= preg_replace('/\D/', '', $agendamento['telefone_cliente'] ?? '') ?>?text=Olá Adamastor, tudo bem? Gostaria de falar sobre meu agendamento." 
                target="_blank" class="btn-whatsapp">
                <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            </div>

            <div class="section">
                <button type="submit" class="agendar" id="btnAgendar">AGENDAR</button>
            </div>
        </form> 
        <?php endif; ?>
        </main>
    </div>

    <script>
    // puxa valores do bd
    const valoresServicos = <?php echo $json_valores_servicos; ?>; 
    
    const valorElemento = document.getElementById('valorServicos');
    const servicos = document.querySelectorAll(".servico-btn");
    const servicosIdsInput = document.getElementById('servicos_ids_selecionados'); 
    const horarioInput = document.getElementById('horario_selecionado');
    const listaHorarios = document.getElementById('listaHorarios');
    
    // variáveis do endereço
    const enderecoRadios = document.querySelectorAll('.endereco-radio');
    const enderecoIdInput = document.getElementById('endereco_id_selecionado');
    const enderecoItems = document.querySelectorAll('.endereco-item');


    // função de cálculo de valor
    function calcularValorTotal() {
        const servicosSelecionadosIds = Array.from(servicos)
            .filter(btn => btn.classList.contains("active"))
            .map(btn => btn.dataset.servicoId); 

        let valorTotal = 0;

        if (servicosSelecionadosIds.length > 0) {
            // soma os valores dos serviços selecionados
            servicosSelecionadosIds.forEach(id => {
                valorTotal += parseFloat(valoresServicos[id] || 0); 
            });
        }

        // formata para R$
        const valorFormatado = valorTotal.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        valorElemento.textContent = valorFormatado;
    }

    // seleção de serviços
    function atualizarServicosSelecionados() {
        const ativos = Array.from(servicos)
            .filter(btn => btn.classList.contains("active"))
            .map(btn => btn.dataset.servicoId); 
        
        servicosIdsInput.value = ativos.join(',');
        
        // atualiza o valor e horários
        calcularValorTotal(); 
        solicitarHorarios(); 
    }
    
    servicos.forEach(btn => {
        btn.addEventListener("click", () => {
            btn.classList.toggle("active");
            atualizarServicosSelecionados();
        });
    });
    
    // inicializa o valor ao carregar a página
    calcularValorTotal();

    // seleção de dia
    document.querySelectorAll(".calendar .day:not(.disabled)").forEach(day => {
        day.addEventListener("click", () => {
            document.querySelectorAll(".calendar .day").forEach(d => d.classList.remove("selected"));
            day.classList.add("selected");
            document.getElementById('data_selecionada').value = day.dataset.date;
            solicitarHorarios();
        });
    });

    // seleção de horário
    function aplicarSelecaoHorarios() {
        const horarios = document.querySelectorAll("#listaHorarios .horario-btn:not(.disabled)");
        horarios.forEach(btn => {
            btn.addEventListener("click", () => {
                horarios.forEach(b => b.classList.remove("active"));
                btn.classList.add("active");
                horarioInput.value = btn.textContent;
            });
        });
    }
    aplicarSelecaoHorarios();

    // buscar horários disponíveis
    function solicitarHorarios() {
        const data = document.getElementById('data_selecionada').value;
        const servicosSelecionados = servicosIdsInput.value;
        
        // limpa a seleção anterior de horário
        horarioInput.value = '';
        listaHorarios.innerHTML = '<p>Carregando horários...</p>'; // feedback de carregamento
        
        fetch('get_horarios_disponiveis.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `data=${encodeURIComponent(data)}&servicos=${encodeURIComponent(servicosSelecionados)}`
        })
        .then(r => r.json())
        .then(json => {
            const lista = document.getElementById('listaHorarios');
            lista.innerHTML = ''; 
            const slots = ['07:00','08:00','09:00','10:00','11:00','14:00','15:00','16:00','17:00'];
            
            // erro
            if (json.error) {
                lista.innerHTML = `<p class="alert alert-danger">Erro no servidor: ${json.error}</p>`;
                return;
            }

            slots.forEach(h => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'horario-btn';
                btn.textContent = h;
                
                if (json.indisponiveis && json.indisponiveis.includes(h)) {
                    btn.classList.add('disabled');
                    btn.disabled = true;
                }
                lista.appendChild(btn);
            });
            aplicarSelecaoHorarios();
        })
        .catch(err => {
            console.error("Erro na requisição AJAX:", err);
            // mensagem de erro
            listaHorarios.innerHTML = '<div class="alert alert-danger text-center">Erro de comunicação ao carregar horários.</div>';
        });
    }

    // seleção de endereços
    function atualizarEnderecoSelecionado() {
        const radioSelecionado = Array.from(enderecoRadios).find(radio => radio.checked);
        
        if (radioSelecionado) {
            enderecoIdInput.value = radioSelecionado.value;
        }
    }

    // rádio buttons
    enderecoRadios.forEach(radio => {
        radio.addEventListener('change', atualizarEnderecoSelecionado);
    });

    // clicar no item inteiro e selecionar o rádio
    enderecoItems.forEach(item => {
        item.addEventListener('click', (event) => {
            // Ignora o clique se for no botão de lixeira
            if (event.target.closest('.btn-excluir-endereco')) return; 
            
            const radio = item.querySelector('.endereco-radio');
            if (radio && !radio.checked) {
                radio.checked = true;
                atualizarEnderecoSelecionado();
            }
        });
    });

    // inicializa a seleção ao carregar a página
    document.addEventListener('DOMContentLoaded', () => {
        atualizarEnderecoSelecionado();
        // carregamento de horários para a data de hoje e sem serviços
        solicitarHorarios(); 
    });

    // validação
    document.getElementById('formAgendamento').addEventListener('submit', function(event) {
        if (servicosIdsInput.value.length === 0) {
            alert("Por favor, selecione pelo menos um serviço.");
            event.preventDefault(); 
            return;
        }
        if (horarioInput.value.length === 0) {
            alert("Por favor, selecione um horário disponível.");
            event.preventDefault(); 
            return;
        }
        if (enderecoIdInput.value.length === 0) {
            alert("Por favor, selecione um endereço para o serviço.");
            event.preventDefault(); 
            return;
        }
    });

    // função para deletar endereço
    document.querySelectorAll('.btn-excluir-endereco').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            if (!confirm("Deseja realmente excluir este endereço?")) return;

            fetch('./endereco/deletar_endereco.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_endereco=' + encodeURIComponent(id)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const enderecoItem = document.getElementById('endereco-' + id);
                    if(enderecoItem) {
                        // remove o separador antes de remover o item
                        const separator = enderecoItem.previousElementSibling;
                        if (separator && separator.classList.contains('endereco-separator')) {
                            separator.remove();
                        }
                        enderecoItem.remove();
                        // seleciona o principal novamente se o removido estava ativo
                        if (!document.querySelector('.endereco-radio:checked')) {
                            document.querySelector('.endereco-radio[value^="principal_"]').checked = true;
                        }
                        atualizarEnderecoSelecionado();
                        alert('Endereço excluído com sucesso!');
                    }
                } else {
                    alert('Erro ao excluir endereço: ' + (data.error || 'Desconhecido'));
                }
            })
            .catch(err => alert('Erro de conexão: ' + err));
        });
    });
</script>
</body>
</html>