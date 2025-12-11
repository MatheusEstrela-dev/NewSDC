# RELATÓRIO TÉCNICO - CAMPOS E FUNÇÕES FRONTEND RAT

## ABA 1: DADOS GERAIS

### Seção: Atendimento
- **Data/Hora do Fato** *
- **Data/Hora Início da Atividade** *
- **Data/Hora Término da Atividade** *

### Seção: Natureza / COBRADE
- **Código da Ocorrência** *
- **COBRADE**

### Seção: Comunicação da Ocorrência
- **Data/Hora da Comunicação** *
- **Como foi solicitado o atendimento** *

### Seção: Unidade Responsável pelo Registro
- **Município** *
- **Código da Unidade** *
- **Nome da Unidade** *

### Seção: Local do Fato
- **País** *
- **Estado/UF** *
- **Município** *

### Seção: Endereço Detalhado
- **CEP**
- **Logradouro 1** *
- **Bairro**
- **Complemento**
- **Número**
- **KM**
- **Cruzamento**
- **Ponto de Referência**
- **Tipo de Localização** *

### Opções
- **Incluir Relatório de Vistoria**

### Funções JavaScript
- `openPicker()`
- `setDataHoje()`
- `buscarCEP()`
- `buscarCoordenadasCEP()`
- `buscarPorCEP()`
- `buscarPorEndereco()`
- `buscarPorCidade()`
- `armazenarCoordenadas()`
- `buscarCEPGenerico()`
- `validarCampoCEP()`
- `validarCampo()`
- `buscarOcorrencia()`

### Botões
- **Salvar Dados Gerais**

---

## ABA 2: RECURSOS EMPREGADOS

### Seção: Dados do Recurso
- **Tipo de Recurso** *
- **Categoria** *
- **Órgão Responsável** *
- **Identificação/Placa/Matrícula** *
- **Condutor do Veículo**
- **Descrição do Recurso**

### Seção: Dados de Deslocamento
- **Data/Hora de Saída**
- **Data/Hora de Chegada**
- **KM Percorrido**
- **Local de Origem**
- **Local de Destino**

### Seção: Dados Operacionais
- **Quantidade**
- **Capacidade/Potência**
- **Condição do Recurso**
- **Operador/Responsável**
- **Contato de Emergência**
- **Observações**

### Seção: Agentes / Integrantes da Guarnição
- **Nome Completo**
- **Matrícula / MASP**
- **PG/Cargo**
- **Condutor** (Sim/Não)
- **Órgão**
- **Unidade**
- **Função no Atendimento**

### Funções JavaScript
- `getIdentificacaoValue()`
- `coletarAgentesDoFormulario()`
- `adicionarRecurso()`
- `editarRecurso()`
- `removerRecurso()`
- `atualizarListaRecursos()`
- `getCondicaoBadgeClass()`
- `limparFormularioRecurso()`
- `adicionarAgente()`
- `removerAgente()`
- `atualizarListaAgentes()`
- `limparFormularioAgente()`

### Botões
- **Adicionar Recurso**
- **Salvar Recursos**

---

## ABA 3: ENVOLVIDOS

### Seção: Dados Pessoais
- **Tipo de Pessoa** *
- **Nome Completo / Razão Social** *
- **Nome Social**
- **Data de Nascimento**
- **Idade Aparente**
- **CPF** *
- **Nome da Mãe**
- **Nome do Pai**
- **Ocupação atual**
- **Escolaridade**
- **Sexo**
- **Estado Civil**
- **Etnia**
- **Orientação Sexual**
- **Identidade de Gênero**
- **Nacionalidade**
- **País de Origem**
- **Naturalidade/UF**
- **Indivíduo é turista?** (Sim/Não)

### Seção: Documentação de Identificação
- **Tipo**
- **Número**
- **Órgão Expedidor**
- **UF**

### Seção: Endereço
- **País**
- **Estado/UF**
- **Município**
- **CEP**
- **Bairro**
- **Logradouro**
- **Número**
- **Complemento**
- **KM (rodovias)**
- **Código IBGE**

### Seção: Contato
- **Telefone Residencial/Celular**
- **Telefone Comercial/Celular**
- **E-mail**
- **Motivo da Ausência de Contato**

### Seção: Dados do Envolvido
- **O envolvido é pessoa em situação de rua?** (Sim/Não/Não informado)
- **É militar ou servidor público?** (Sim/Não)

### Seção: Dados Militares (quando aplicável)
- **Tipo**
- **Órgão**
- **UF**
- **Matrícula/NR**
- **Em Serviço?** (Sim/Não)

### Funções JavaScript
- `aplicarMascaraTelefone()`
- `adicionarEnvolvido()`
- `removerEnvolvido()`
- `editarEnvolvido()`
- `atualizarListaEnvolvidos()`
- `validarCEPEnvolvido()`
- `buscarCEPEnvolvido()`

### Botões
- **Adicionar Envolvido**
- **Salvar Envolvidos**

---

## ABA 4: VISTORIA

### Seção: Identificação do Solicitante
- **Nome Completo** *
- **CPF** *
- **Telefone**
- **Endereço** *
- **Bairro**
- **CEP**

### Seção: Localização do Imóvel
- **Endereço do Imóvel**
- **Bairro**
- **Município**
- **CEP**

### Seção: Tipo de Imóvel / Estrutura
- **Tipo de Imóvel** *
- **Especifique o Tipo de Imóvel** (quando "Outro")
- **Tipo de Construção**
- **Especifique o Tipo de Construção** (quando "Outro")
- **Tipo de Destinação**
- **Tipo de Edificação**
- **Tipo de Terreno / Relevo**
- **Tipo de Localização**
- **Sistema Estrutural**
- **Número de Pavimentos**
- **Estado de Conservação**
- **Regime de Ocupação**

### Seção: Caracterização dos Moradores
- **Proprietário / Morador**
- **Contato Telefone**
- **Número de Moradores**
- **Há Idosos?** (Sim/Não)
- **Há Crianças?** (Sim/Não)
- **Há Pessoas com Dificuldades de Locomoção?** (Sim/Não)

### Seção: Característica do Terreno e Infraestrutura
- **Abastecimento de Água?** (Sim/Não)
- **Esgotamento Sanitário?** (Sim/Não)
- **Drenagem Superficial?** (Sim/Não)
- **Sistema Viário de Acesso**
- **Tipo de Revestimento**
- **Condições de Acesso**
- **Número de Moradias no Terreno**
- **Distância da Encosta**
- **Material Construtivo**
- **Conservação Estrutural**

### Seção: Patologias Identificadas
- **Trincas**
- **Fissuras**
- **Rachaduras**
- **Umidade**
- **Infiltração**
- **Desplacamento de Revestimento**
- **Comprometimento das Fundações**
- **Instabilidade de Talude / Contenções**
- **Indícios de Movimentação de Solo**
- **Risco de Tombamento de Muralhas de Vedação**
- **Inundações**
- **Alagamentos**
- **Enxurradas**
- **Patologia em Elementos de Madeira**
- **Patologia em Elementos Não Estruturais**

### Funções JavaScript
- `preencherSolicitanteComEnvolvido()`
- `aplicarMascaraTelefone()`
- `aplicarMascaraCEP()`

### Botões
- **Salvar Vistoria**

---

## ABA 5: HISTÓRICO

### Campos
- **Detalhes do Histórico**

### Funções JavaScript
- Nenhuma função específica (apenas submit handler)

### Botões
- **Salvar Histórico e Finalizar**

---

## VARIÁVEIS GLOBAIS JAVASCRIPT

- `recursos` (array)
- `recursoIndex` (number)
- `envolvidos` (array)
- `envolvidoIndex` (number)
- `agentesRedecData` (object)
- `agentesTemp` (array)
- `agentesTempIndex` (number)

---

## ROTAS UTILIZADAS

- `rat.bo.dados-gerais.store`
- `rat.bo.recursos.store`
- `rat.bo.envolvidos.store`
- `rat.bo.vistoria.store`
- `rat.bo.historico.store`
