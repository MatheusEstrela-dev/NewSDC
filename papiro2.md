1. Estrutura Visual do Modal (UI)

Imagine o modal dividido em 3 etapas (tabs ou steps) para organizar o fluxo do caminhão chegando:
Aba 1: Dados do Transporte (Portaria)

O objetivo aqui é registrar "Quem chegou".

    Placa do Veículo: (Essencial para controle de pátio).

    Transportadora: Nome da empresa.

    Motorista: Nome e Documento (RG/CPF).

    Hora de Chegada: Automático (DateTime.Now).

    Doca de Descarga: (Opcional, se houver mais de uma porta).

Aba 2: Documentação (Fiscal)

O objetivo é vincular o que chegou ao que foi pedido.

    Número da Nota Fiscal (NF): Campo chave.

    Vincular Ordem de Compra (OC): Um dropdown ou busca que lista aquelas "Solicitações de Compra" que saíram do seu diagrama BPMN.

        Lógica: Ao selecionar a OC, o sistema já pré-carrega os itens esperados.

Aba 3: Conferência Física (O Checklist TDPA)

Aqui entra a especificidade dos seus itens. Uma tabela onde o conferente lança o que está saindo do caminhão.
Item (Pré-carregado da OC)	Qtd. Nota	Qtd. Conferida	Lote / Validade	Status / Avarias
Cesta Básica Tipo 1	100	100	[Input Data] ⚠️	[ ] Avaria (Molhado/Rasgado)
Kit Limpeza	50	50	Lote: AB2023	[ ] Vazamento
Colchão Solteiro	200	198	N/A	[x] 2 Rasgados (Devolver)
2. Regras de Negócio do Modal (Backend)

Quando o usuário clicar em "Finalizar Recebimento" neste modal, o sistema deve rodar as seguintes validações:

    Validação de Perecíveis (Cestas Básicas):

        Se Data de Validade inserida for menor que X meses (regra da empresa), o modal deve exibir um ALERTA VERMELHO: "Produto com validade curta. Aceitar mesmo assim?".

    Validação de Químicos (Kit Limpeza):

        Verificar se há vazamentos registrados. Se sim, bloquear a entrada desse item específico para não contaminar o depósito.

    Fechamento do Ciclo BPMN:

        Ao salvar, o sistema dispara o evento para a raia de Compras/PCM: "Material Disponibilizado" (o envelope no seu diagrama). Isso tira a solicitação do status "Aguardando Compra" e move para o estoque.

3. Exemplo de Estrutura de Dados (JSON Payload)

Este é o objeto que o seu Front-end (Modal) enviaria para o Back-end:
JSON

{
  "recebimento": {
    "placa_veiculo": "ABC-1234",
    "motorista": "João da Silva",
    "numero_nota_fiscal": "998877",
    "id_ordem_compra": 5021, // Vincula com o pedido original
    "itens_conferidos": [
      {
        "id_produto": 10, // Cesta Básica
        "qtd_recebida": 100,
        "lote": "L2024-JAN",
        "data_validade": "2024-12-31", // CRÍTICO PARA TDPA
        "avarias": false
      },
      {
        "id_produto": 25, // Colchão
        "qtd_recebida": 48, // Eram 50, faltaram 2
        "observacao": "2 unidades não entregues pelo motorista",
        "avarias": false
      }
    ]
  }
}

4. Sugestão de UX (Experiência do Usuário)

Para ficar "matador" no seu sistema:

    Leitura de Código de Barras: Se o operador tiver um leitor, permita que ele bipe a Nota Fiscal ou o produto direto no modal.

    Foto da Avaria: No caso do colchão rasgado ou cesta estourada, coloque um botão pequeno de "Câmera" no modal para anexar a foto da prova da recusa ali mesmo.

Gostaria que eu montasse um esboço do código HTML/CSS desse modal ou prefere focar na lógica de banco de dados para salvar essa entrada?