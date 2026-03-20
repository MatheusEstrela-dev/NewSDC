# Análise Técnica: Lacunas de Persistência no Banco de Dados (Módulo Decretações)

Este documento detalha os motivos pelos quais certas tabelas não estão sendo populadas durante a criação de um processo no backend.

## 1. Tabelas de Dicionário (Metadados)
As seguintes tabelas estão vazias:
- `dec_cobrade`
- `dec_desastre_categorias`
- `dec_desastre_grupos`
- `dec_desastre_item_campos`
- `dec_desastre_items`
- `dec_decreto_categorias`

### Motivo:
O sistema utiliza atualmente um **Enum baseado em arquivo PHP** como fonte de verdade para as opções de desastre ([classificacao_desastres.php](file:///C:/Users/x24679188/Documents/Github/NewSDC/SDC/app/Enums/classificacao_desastres.php)). 
- **Situação:** As migrações criaram as tabelas, mas não há um `Seeder` implementado para populá-las.
- **Impacto:** O frontend lê as opções do backend (que as extrai do arquivo PHP), mas ao salvar, o banco não possui as chaves estrangeiras populadas se houver validação rígida (embora o DTO use apenas IDs numéricos).

---

## 2. Tabelas de Detalhamento de Desastre
Tabelas que permanecem vazias após o teste:
- `dec_entrada_desastres`
- `dec_entrada_categoria_desastres`

### Motivo:
**Fluxo de Dados em Etapas.** O formulário de criação inicial (`ProcessoCreate.vue`) foca apenas no cabeçalho do processo.
- O `EntradaProcessoService::createProcesso` chama `syncMunicipalities`, que cria o vínculo na tabela `dec_decreto_municipios`.
- Contudo, a persistência detalhada em `dec_entrada_desastres` depende do recebimento de um objeto complexo via `DesastreSubmissionDTO` no `DesastreDataService`, o que ocorre em uma tela de edição/detalhamento posterior, não na criação simplificada.

---

## 3. Tabela `dec_processo` vs `dec_entrada_processos`
A tabela `dec_processo` está vazia, enquanto `dec_entrada_processos` recebeu dados.

### Motivo:
**Mudança de Arquitetura (Legado vs Novo).**
- O modelo [Processo](file:///C:/Users/x24679188/Documents/Github/NewSDC/SDC/app/Modules/Decretacoes/Models/DecretacoesModels.php#L20) está explicitamente apontando para a tabela `dec_entrada_processos` (`protected $table = 'dec_entrada_processos'`).
- A tabela `dec_processo` parece ser um remanescente do sistema legado ou uma estrutura que foi substituída pela nova arquitetura do módulo de Decretações, mas a migração de criação ainda existe.

---

## 4. Tabela `dec_entrada_decretos`
Esta tabela também permaneceu vazia no teste.

### Motivo:
O `EntradaProcessoService::syncInformacoesDecreto` espera um campo `informacoes_decreto` (JSON) que não foi enviado no formulário de teste básico. Como o frontend inicial não fornece interface para selecionar categorias de decreto na primeira tela, o array chega vazio ao backend.

---

## Resumo de Recomendações
1. **Implementar Seeders:** Criar seeders para as tabelas `dec_cobrade`, `dec_desastre_grupos`, etc., baseados no arquivo `classificacao_desastres.php`.
2. **Clarificar Legado:** Confirmar se `dec_processo` pode ser removida ou se deve ser sincronizada.
3. **Fluxo de Interface:** Garantir que o usuário seja redirecionado para o preenchimento dos detalhes (FIDE/Desastres) logo após a criação do cabeçalho.
`