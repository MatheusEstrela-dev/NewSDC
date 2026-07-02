# Changelog

Registro das releases do NewSDC. Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/)
e nas convenções de commit [Gitmoji](https://gitmoji.dev).

---

## [RELEASE_PMDA] — 2026-07-02

Entrega completa do **Módulo PMDA** (Plano Municipal de Defesa Agropecuária) — fornecimento
emergencial de água potável por caminhão-pipa. A tag `RELEASE_PMDA` (commit `ca293ff4`) contém
todo o histórico do módulo, do commit de fundação (`0e7000e4`) ao merge da Central de Análises.

### ✨ Funcionalidades

- **Fundação e core do módulo**: provider, rotas, permissões, menu; planos, máquina de estados
  (`PmdaStatus`: RASCUNHO → COMPLETO → EM_ANÁLISE → APROVADO → ATENDIDO + terminais) e cópia com
  protocolo sequencial `{id}{AAAAMMDD}`.
- **Wizard de 7 etapas**: Início → ISS/Prefeitura → COMPDEC → Ponto de Captação → Locais de
  Distribuição → Ações de Resposta → Anexos/Envio; salva a cada etapa (criação em contexto SPA).
- **Comunidades e representantes**: vínculo ao plano (3 representantes por comunidade para completar)
  e registro **mestre** reutilizável por município.
- **Solicitação/aprovação de comunidades (CEDEC)**: município solicita inclusão; CEDEC aprova
  (promove ao registro mestre) ou rejeita com motivo.
- **Pontos de captação** (compartilhados com o TDAP) e dados de Município/COMPDEC.
- **Fichas COMPDEC**: cadastro, equipe (ativos e anteriores) e documentos (leis e decretos),
  reutilizando o módulo COMPDEC por município.
- **Central de Análises CEDEC** (tela dividida): fila de PMDA em análise (Aprovar / Pedir alteração /
  Arquivar) + fila de solicitações de comunidade (Aprovar / Rejeitar).
- **Impressão da Ficha COMPDEC** e **Série Histórica** do plano (timeline estilo PAE, com envio,
  aprovação, arquivamento e devolutiva).
- **Ações do índice**: histórico, imprimir, duplicar e excluir; downloads na aba Início (passo a
  passo, termo de compromisso, declaração ISS).
- **Envio para análise** alinhado ao legado: exige plano **COMPLETO** + Termo/Ofício e grava
  o responsável (`resp_homolog`).
- **Exclusão por perfil**: admin/super-admin excluem em qualquer status; CEDEC apenas quando
  **Atendido**.

### 🎨 UI / ♻️ Refatorações

- **Stat cards como atalho de filtro rápido** (convenção do projeto): clicar num card filtra a
  listagem; aplicado ao PMDA e a mais 9 módulos (Ajuda Humanitária, Cisterna, Compdec, Decretações,
  Demandas, Estoque, Inventário, Plantão, Treinamento).
- **Identidade visual por módulo** com ícones SVG no header.
- **Cores de status** do protocolo: Em Edição = amarelo, Em Análise = azul, Aprovado = verde,
  Arquivado = vermelho.
- Responsividade do índice + calendário padrão RAT nos filtros; padronização de paginação e datepicker.

### 🗃️ Banco de Dados

- Tabelas: `pmda_planos`, `comunidades` (mestre), `pmda_comunidades`, `pmda_representantes`,
  `pip_pmda_ponto`, `pmda_plano_ponto`, `pmda_compdec_membros`, `pmda_comunidade_solicitacoes`.
- Coluna `motivo_analise` (motivo de arquivamento/devolutiva).
- Permissões `pmda.*` (dashboard, planos, comunidades, representantes, pontos, análise, mensagens,
  anexos) concedidas a `admin` (wildcard) e ao perfil CEDEC (`manager`).

### 🐛 Correções

- Sidebar do PMDA mantém-se ativa; saturação de cor do PlanCon no modo claro.
- Protocolo/Município deixavam de aparecer a partir da aba COMPDEC (colisão do campo `data` na
  serialização do resource).
- "Piscada" ao iniciar um novo PMDA (troca de redirect full-reload por navegação SPA).
- Resolução de marcadores de conflito herdados do `origin/dev` em 7 arquivos do RAT que impediam
  o boot da aplicação.

### ✅ Qualidade

- Backend do PMDA validado por smoke tests de runtime: **25/25 aprovados** (criação, envio, análise,
  exclusão por perfil, fila, solicitação/aprovação de comunidade, cores e máquina de estados).
- `build` (Vite/bun) e boot da aplicação (`route:list`) OK.

### 📌 Pendências conhecidas (fora desta release)

- Transição **Aprovado → Atendido** (execução) via integração com o módulo TDAP — planejada.
- Retrofit dos stat-cards nos índices do TDAP (backend de filtro em WIP).
