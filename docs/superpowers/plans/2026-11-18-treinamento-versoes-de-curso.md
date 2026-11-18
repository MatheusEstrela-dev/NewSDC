# Versoes de curso EAD - Plano de implementacao

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** criar versao nova (2.0, 3.0...) de curso EAD publicado copiando a anterior, manter a anterior intacta para quem esta nela, e deixar o formado na anterior se atualizar fazendo so o que mudou.

**Architecture:** cada versao e um `treinamento` proprio no mesmo curso do catalogo (`treinamentos.curso_id`). Um service copia a arvore de conteudo apontando para os mesmos arquivos e videos e grava a origem de cada item; a vigente e a publicada de maior numero; um service de atualizacao inscreve o formado e pre-conclui os itens iguais pela cadeia de origem.

**Tech Stack:** Laravel 12 (PHP 8.4 no container), Postgres 18 (banco `sdc_treinamento`, conexao `ConexaoTreinamento`), Inertia + Vue 3, PHPUnit com `DatabaseTransactions`.

**Spec:** `docs/superpowers/specs/2026-11-18-treinamento-versoes-de-curso-design.md` (ler inteiro, inclusive "Decisoes complementares").

## Global Constraints

- Worktree `C:\Users\x24679188\Documents\Github\NewSDC\.worktrees\treinamento-versao`, branch `feat/treinamento-versao-curso`. Nunca trabalhar no checkout principal.
- Schema so na migration consolidada `SDC/database/migrations/treinamento/2026_11_05_210000_create_treinamento_schemas_tables.php`, em metodo novo `criarVersoes()` chamado no fim de `up()`, SQL idempotente (`IF NOT EXISTS`, `DROP CONSTRAINT IF EXISTS` antes de `ADD CONSTRAINT`), no padrao de `criarCatalogo()`.
- Nunca JOIN entre o banco do sdc e o `sdc_treinamento`.
- Sem emoji no codigo. DRY/SOLID, services pequenos por responsabilidade, no padrao do modulo `App\Modules\Treinamento`.
- Testes em `SDC/tests/Feature/Treinamento/Versoes/`, estendendo `Tests\Feature\Treinamento\Ead\EadTestCase`. Pasta `tests` e gitignored: arquivo de teste nunca entra em commit.
- Fixtures sem data de hoje nem valor plausivel de usuario: titulos com prefixo `TST-VERSAO`, CPFs de faixa de teste.
- Commits gitmoji pt-BR, ex. `✨ feat(treinamento): ...`, `🗃️ db(treinamento): ...`; sem trailer Co-Authored-By; `git add` por caminho; um commit por task.
- Nada de push, deploy, `migrate` em `sdc_treinamento`/`sdc`/homolog. Migration so roda no banco de teste da Task 0.
- Rotulo de versao: `"v{numero}.0"` (ex. `v2.0`), exibido so quando o curso tem mais de uma versao publicada.

## Review Focus

1. Treinamento EAD sem `curso_id` (legado nao vinculado ao catalogo) recebendo "Nova versao": deve criar/vincular o curso do catalogo e seguir, nao quebrar. Teste na Task 2.
2. Duas chamadas simultaneas de "Nova versao" no mesmo curso: so um rascunho nasce (indice unico + trava). Teste na Task 2.
3. Professor troca o PDF de um item no rascunho 2.0 que ainda e usado pela 1.0: o arquivo fisico continua no disco. Teste na Task 3.
4. Pessoa com inscricao em andamento (nao concluida) na 1.0 tenta "Atualizar": recusado com mensagem, sem criar inscricao. Teste na Task 5.
5. Aluno da 1.0 em andamento depois de publicada a 2.0: continua abrindo a sala, conclui e recebe certificado da 1.0. Teste na Task 4.

---

### Task 0: Ambiente de teste da worktree

**Files:**
- Create: `.superpowers/sdd/2026-11-18-treinamento-versoes/dc.sh`, `.superpowers/sdd/2026-11-18-treinamento-versoes/phpunit.sh` (git-ignored)
- Copy: `SDC/.env` da worktree `treinamento-ead`; `SDC/public/build` do checkout principal; `SDC/tests/Feature/Treinamento/Ead/**` de `.worktrees/treinamento-ead`; `SDC/tests/Feature/Treinamento/{Catalogo,Reinscricao,Credencial,Indicadores,ConsultaEdital,Captura}` de `.worktrees/capacitacoes-indicadores`

- [ ] **Step 1:** criar `SDC/bootstrap/cache`, `SDC/storage/framework/{cache,views,testing}`, `SDC/storage/logs`; rodar `composer install --ignore-platform-reqs` pelo container `newsdc-swoole-dev:latest` com `COMPOSER_PROCESS_TIMEOUT=0`, saida redirecionada para arquivo; conferir que `SDC/vendor/bin/phpunit` existe.
- [ ] **Step 2:** criar o banco de teste `sdc_treinamento_versoes_teste` no container `newsdc_dev_db` copiando `sdc_treinamento` (`pg_dump -Fc sdc_treinamento | pg_restore --create` com nome novo; nao usar `TEMPLATE`, o banco original tem conexoes do DBeaver).
- [ ] **Step 3:** `dc.sh` = copia do `.worktrees/treinamento-ead/.superpowers/sdd/2026-11-12-treinamento-ead-fase3-comprovacao/dc.sh` com `SDC_DIR` desta worktree e `TREINAMENTO_DB_DATABASE` padrao `sdc_treinamento_versoes_teste`; `phpunit.sh` igual ao de la apontando para este `dc.sh`. Banco principal continua `sdc_teste_f3` (nunca `sdc`).
- [ ] **Step 4:** aplicar a migration consolidada atual no banco de teste: `bash .superpowers/sdd/2026-11-18-treinamento-versoes/dc.sh php artisan migrate --database=pgsql_treinamento --path=database/migrations/treinamento --force` (apagar antes a linha da migration principal em `sdc_treinamento_versoes_teste.migrations` para ela reaplicar).
- [ ] **Step 5:** baseline: `bash .superpowers/sdd/2026-11-18-treinamento-versoes/phpunit.sh tests/Feature/Treinamento`. Anotar no ledger a contagem e as falhas pre-existentes. Sem commit (nada versionado mudou).

### Task 1: Schema de versoes

**Files:**
- Modify: migration consolidada (novo `criarVersoes()`), `SDC/app/Modules/Treinamento/Models/Treinamento.php`, `Models/ModuloItem.php`, `Models/Inscricao.php`, `Enums/TipoEventoCurso.php`
- Test: `SDC/tests/Feature/Treinamento/Versoes/SchemaVersoesTest.php`

**Interfaces:**
- Produces (colunas): `treinamentos.versao int NOT NULL DEFAULT 1`, `treinamentos.versao_origem_id bigint NULL` (FK treinamentos ON DELETE SET NULL), `treinamentos.versao_notas text NULL` ("o que mudou"); `modulo_itens.origem_item_id bigint NULL` (FK modulo_itens ON DELETE SET NULL), `modulo_itens.alterado_desde_copia boolean NOT NULL DEFAULT false`; `inscricoes.atualizacao_de_inscricao_id bigint NULL` (FK inscricoes ON DELETE SET NULL). Indice unico parcial `treinamentos_curso_versao_unico ON (curso_id, versao) WHERE curso_id IS NOT NULL AND deleted_at IS NULL`; indices nas tres FKs novas.
- Produces (model): `Treinamento::versaoOrigem(): BelongsTo`, `Treinamento::rotuloDeVersao(): string` (`"v{$this->versao}.0"`), `ModuloItem::origem(): BelongsTo`, `Inscricao::eAtualizacao(): bool`; casts/fillable das colunas novas.
- Produces (enum): `TipoEventoCurso::VERSAO_CRIADA = 'VERSAO_CRIADA'`, `TipoEventoCurso::VERSAO_PUBLICADA = 'VERSAO_PUBLICADA'`, com labels.

- [ ] **Step 1: teste** `test_colunas_de_versao_existem_com_padrao` (insere treinamento EAD sem informar versao -> `versao === 1`, `alterado_desde_copia === false` em item novo) e `test_nao_permite_duas_versoes_iguais_no_mesmo_curso` (dois treinamentos com mesmo `curso_id` e `versao = 2` -> `QueryException`).
- [ ] **Step 2:** rodar `phpunit.sh tests/Feature/Treinamento/Versoes/SchemaVersoesTest.php` -> FAIL (coluna inexistente).
- [ ] **Step 3:** implementar `criarVersoes()` e o model; reaplicar a migration no banco de teste (Task 0 Step 4).
- [ ] **Step 4:** teste passa; `php -l` nos arquivos alterados.
- [ ] **Step 5: commit** `🗃️ db(treinamento): colunas de versao do curso, origem do item e inscricao de atualizacao`.

### Task 2: Criar nova versao (copia)

**Files:**
- Create: `SDC/app/Modules/Treinamento/Services/Versoes/NovaVersaoDoCursoService.php`, `SDC/app/Modules/Treinamento/Services/Versoes/CopiaDeConteudo.php`
- Test: `SDC/tests/Feature/Treinamento/Versoes/NovaVersaoTest.php`

**Interfaces:**
- Consumes: Task 1; `CatalogoService::criar(array $dados, ?int $userId): Curso`; `HistoricoDoCursoService::registrar(int $treinamentoId, TipoEventoCurso $tipo, string $descricao, array $dados)`.
- Produces: `NovaVersaoDoCursoService::criar(Treinamento $vigente, User $autor): Treinamento` (devolve o rascunho); `NovaVersaoDoCursoService::rascunhoDe(int $cursoId): ?Treinamento`; `CopiaDeConteudo::copiar(Treinamento $origem, Treinamento $destino): void` (pastas, itens, provas, questoes, alternativas; cada item com `origem_item_id` = id do item copiado, `alterado_desde_copia = false`, mesmo `video_id` e `arquivo_path`).
- Regras: so EAD publicado e vigente (vigente = sem versao publicada de numero maior no curso); `DomainException('Ja existe um rascunho da versao N deste curso.')` se houver rascunho; sem `curso_id`, vincula a curso do catalogo de mesmo nome (case-insensitive) ou cria um pelo `CatalogoService`, e grava na origem tambem. Rascunho: `versao = max(versao do curso) + 1`, `versao_origem_id = vigente.id`, `publicado_em = null`, `link_publico_slug = null`, `situacao = ATIVO`, demais campos do curso copiados (titulo, descricao, carga horaria, capa_path, criterios de conclusao, publico_alvo, tipo, aprovacao automatica); professores de `treinamento_professores` copiados. Nada de inscricao/progresso/certificado/frequencia/cronograma/eventos. Tudo numa `ConexaoTreinamento::transacao`, com `lockForUpdate` na linha do curso do catalogo. Historico `VERSAO_CRIADA` no rascunho.

- [ ] **Step 1: testes**
  - `test_copia_arvore_inteira_apontando_para_os_mesmos_arquivos` (origem com 2 pastas, 3 itens: PDF, video, prova com 2 questoes x 3 alternativas -> rascunho tem as mesmas contagens, `arquivo_path`/`video_id` iguais, `origem_item_id` preenchido, `versao === 2`, `publicado_em === null`).
  - `test_nao_altera_a_versao_de_origem` (contagens e `updated_at` dos itens da origem iguais antes e depois).
  - `test_nao_copia_inscricoes_nem_progresso`.
  - `test_recusa_segundo_rascunho` e `test_recusa_curso_nao_publicado` e `test_recusa_versao_que_nao_e_a_vigente`.
  - `test_treinamento_sem_catalogo_ganha_curso_e_versiona` (Review Focus 1).
  - `test_numero_da_versao_e_unico_sob_concorrencia`: duas chamadas em sequencia com o mesmo vigente -> a segunda recebe `DomainException` (Review Focus 2).
- [ ] **Step 2:** rodar -> FAIL.
- [ ] **Step 3:** implementar.
- [ ] **Step 4:** rodar a pasta `Versoes` -> PASS.
- [ ] **Step 5: commit** `✨ feat(treinamento): criar nova versao do curso EAD copiando o conteudo`.

### Task 3: Marca de alteracao e arquivos compartilhados

**Files:**
- Modify: `Services/ConteudoCursoService.php` (`atualizarItem`), `Services/ProvaConteudoService.php` (`configurar`, `criarQuestao`, `atualizarQuestao`, `excluirQuestao`), `Services/Video/VideoBibliotecaService.php` (vincular/desvincular video do item), `Services/ArquivoCursoService.php` (`apagar`), `Services/CapaDoCursoService.php`
- Test: `SDC/tests/Feature/Treinamento/Versoes/AlteracaoDesdeCopiaTest.php`, `ArquivoCompartilhadoTest.php`

**Interfaces:**
- Produces: `ModuloItem::marcarAlterado(): void` (grava `alterado_desde_copia = true` so se `origem_item_id` nao for nulo); `ArquivoCursoService::apagar(?string $path)` passa a nao apagar se o caminho for referenciado por qualquer `modulo_itens.arquivo_path` (inclusive soft-deleted) ou `treinamentos.capa_path`.
- Regras (spec regra 4 e decisao 7): marcam alterado: troca de arquivo, troca/remocao de video, mudanca de `total_paginas`, de `obrigatorio`, de `tipo`; em prova, mudanca de nota minima, criar/editar/excluir questao ou alternativa. NAO marcam: titulo, descricao, ordem, mover de pasta, reprocessar video.

- [ ] **Step 1: testes** `test_trocar_arquivo_marca_alterado`, `test_editar_titulo_nao_marca`, `test_mudar_nota_minima_marca`, `test_editar_questao_marca`, `test_item_sem_origem_nunca_marca`; `test_trocar_pdf_no_rascunho_nao_apaga_arquivo_usado_pela_origem` (Storage::fake, arquivo continua existindo; Review Focus 3), `test_arquivo_sem_outra_referencia_continua_sendo_apagado`.
- [ ] **Step 2:** FAIL. **Step 3:** implementar. **Step 4:** pasta `Versoes` + `tests/Feature/Treinamento/Ead/ConteudoCursoTest.php` PASS.
- [ ] **Step 5: commit** `✨ feat(treinamento): marca item alterado na nova versao e preserva arquivo compartilhado`.

### Task 4: Vigencia e publicacao da versao

**Files:**
- Create: `SDC/app/Modules/Treinamento/Support/VersoesDoCurso.php`, `SDC/app/Modules/Treinamento/Mail/NovaVersaoDisponivelMail.php` (+ view no padrao de `EntregaCorrigidaMail`)
- Modify: `Models/Treinamento.php` (escopos), `Services/ConteudoCursoService.php` (`publicar`), `Services/InscricaoService.php` (`inscrever`), `Controllers/TreinamentoPublicarController.php` e seu Request (campo `versao_notas`, obrigatorio quando `versao > 1`, max 1000)
- Test: `SDC/tests/Feature/Treinamento/Versoes/VigenciaTest.php`

**Interfaces:**
- Produces: escopo `Treinamento::scopeVigente($q)` (nao existe treinamento publicado do mesmo `curso_id` com `versao` maior; sem `curso_id` = vigente), aplicado dentro de `scopeNoCatalogo`; `Treinamento::eVigente(): bool`; `VersoesDoCurso::rotulos(iterable $treinamentos): array<int, ?string>` (treinamento_id => `"v2.0"` ou null quando o curso tem uma so versao publicada; UMA consulta agrupada por `curso_id`); `VersoesDoCurso::listar(int $cursoId): Collection` (todas as versoes do curso com numero, publicado_em, versao_notas, inscritos, formados; uma consulta).
- Regras: `inscrever()` recusa nao vigente com `DomainException('Esta versao do curso foi substituida pela {rotulo}. Inscreva-se na versao atual.')`, exceto quando chamado pela atualizacao (Task 5 passa por metodo proprio). Publicar versao > 1: grava `versao_notas`, historico `VERSAO_PUBLICADA` no curso novo, e enfileira `NovaVersaoDisponivelMail` para cada pessoa com inscricao concluida em versao anterior do mesmo curso que tenha e-mail (uma consulta para os ids, envio fora da transacao).

- [ ] **Step 1: testes** `test_publicar_2_tira_a_1_do_catalogo`, `test_rascunho_nao_aparece_no_catalogo`, `test_inscricao_nova_na_1_e_recusada_depois_da_2`, `test_aluno_em_andamento_na_1_continua_e_conclui` (Review Focus 5: sala acessivel, `ReavaliacaoDeConclusaoService::avaliar` conclui), `test_publicar_2_avisa_formados_da_1` (`Mail::fake`, enviado a formado, nao a quem esta em andamento), `test_rotulo_so_aparece_com_mais_de_uma_versao_publicada`.
- [ ] **Step 2:** FAIL. **Step 3:** implementar. **Step 4:** pasta `Versoes` + `tests/Feature/Treinamento/Ead` PASS.
- [ ] **Step 5: commit** `✨ feat(treinamento): versao vigente no catalogo e aviso de nova versao`.

### Task 5: Atualizacao com aproveitamento

**Files:**
- Create: `SDC/app/Modules/Treinamento/Services/Versoes/AtualizacaoDeVersaoService.php`, `SDC/app/Modules/Treinamento/Services/Versoes/AproveitamentoDeItens.php`
- Modify: `Services/InscricaoService.php` (metodo de inscricao sem a regra das 3, reutilizando o corpo de `inscrever`), `Services/RegraDeReinscricaoService.php` (`inscricoesQueContam` ignora `atualizacao_de_inscricao_id IS NOT NULL`)
- Test: `SDC/tests/Feature/Treinamento/Versoes/AtualizacaoTest.php`

**Interfaces:**
- Consumes: Tasks 1-4; `TrilhaService::atualizarPastas(Inscricao)`, `ReavaliacaoDeConclusaoService::avaliar(Inscricao): bool`, `ProgressoService` / modelo `ProgressoItem`, `ProvaTentativa` (`aprovada`), `ProjetoEntrega` (status aprovado).
- Produces: `AtualizacaoDeVersaoService::podeAtualizar(Treinamento $vigente, Model $inscrito): ?Inscricao` (devolve a inscricao concluida de origem mais recente em versao anterior do mesmo curso, ou null); `AtualizacaoDeVersaoService::atualizar(Treinamento $vigente, Model $inscrito): Inscricao`; `AproveitamentoDeItens::itensIguais(Treinamento $vigente, Inscricao $origem): list<int>` (ids de itens da vigente cuja cadeia `origem_item_id` chega a um item concluido pela inscricao de origem sem nenhum `alterado_desde_copia = true` no caminho; prova so com tentativa aprovada; projeto so com entrega aprovada; carrega a cadeia em consultas por nivel, sem N+1).
- Regras: `DomainException` se nao for formado em versao anterior, se ja tiver inscricao na vigente, ou se tiver inscricao em andamento em qualquer versao do curso. A inscricao nasce APROVADA, com `atualizacao_de_inscricao_id = origem.id`, passando por vaga/periodo/publico como uma inscricao normal, mas NUNCA pela regra das 3. Grava `progresso_itens` CONCLUIDO para os itens iguais, chama `atualizarPastas` e `avaliar` (versao so com remocoes conclui na hora). Tudo em transacao; avaliacao depois do commit.

- [ ] **Step 1: testes**
  - `test_formado_na_1_faz_so_os_itens_novos` (1.0 com 2 itens concluidos; 2.0 = copia + 3 itens novos -> 2 CONCLUIDO, 3 pendentes, percentual 40).
  - `test_item_alterado_precisa_ser_refeito`.
  - `test_prova_igual_conta_so_se_aprovada_na_origem` (aprovada -> concluida; so reprovada -> pendente).
  - `test_projeto_igual_conta_so_com_entrega_aprovada`.
  - `test_cadeia_1_para_3_aproveita_o_que_nao_mudou_nas_duas`.
  - `test_atualizacao_nao_conta_nas_3_tentativas` (pessoa com 3 inscricoes no curso consegue atualizar; `RegraDeReinscricaoService::situacao` nao conta a de atualizacao, nem cancelada).
  - `test_em_andamento_na_1_nao_pode_atualizar` (Review Focus 4).
  - `test_versao_so_com_remocoes_conclui_na_hora`.
  - `test_certificado_da_1_continua_valido_depois_da_2`.
- [ ] **Step 2:** FAIL. **Step 3:** implementar. **Step 4:** pastas `Versoes`, `Reinscricao`, `Ead` PASS.
- [ ] **Step 5: commit** `✨ feat(treinamento): atualizacao para a nova versao aproveitando o que nao mudou`.

### Task 6: Tela do aluno

**Files:**
- Create: `SDC/app/Modules/Treinamento/Controllers/Aluno/AtualizarVersaoController.php`
- Modify: `routes/modules/treinamento-aluno.php` (POST `treinamentos/aluno/cursos/{treinamento}/atualizar`, nome `treinamentos.aluno.atualizar`), `Controllers/Aluno/MeusCursosController.php` (`curso()` ganha `versao_rotulo` e `nova_versao: ?{id, rotulo, notas, itens_novos}`), `Controllers/Aluno/SalaDeAula*` props (`versao_rotulo`), `resources/js/Components/Molecules/Treinamento/CursoEadCard.vue`, `resources/js/Pages/Treinamento/Aluno/MeusCursos.vue`, `resources/js/Pages/Treinamento/Aluno/SalaDeAula.vue`
- Test: `SDC/tests/Feature/Treinamento/Versoes/AlunoVersaoTest.php`

**Interfaces:**
- Consumes: `VersoesDoCurso::rotulos`, `AtualizacaoDeVersaoService::atualizar`.
- Regras: rotulos de todos os cards em uma chamada (sem N+1). Card de formado com vigente mais nova mostra a linha "Versao 2.0 disponivel: +N modulos" (N = pastas da vigente sem item com origem) e botao "Atualizar" ao lado de "Revisar"; POST redireciona para a sala da vigente com flash de sucesso, ou volta com erro do `DomainException`. Layout responsivo no padrao atual do card (mobile-first).

- [ ] **Step 1: testes** `test_card_mostra_nova_versao_para_formado`, `test_card_sem_rotulo_quando_versao_unica`, `test_post_atualizar_cria_inscricao_e_redireciona_para_a_sala`, `test_meus_cursos_respeita_teto_de_queries` (contar queries com 1 e com 5 cursos: mesma contagem).
- [ ] **Step 2:** FAIL. **Step 3:** implementar backend e Vue. **Step 4:** testes PASS; `npx vite build` no container sem erro de import (caminho com case correto).
- [ ] **Step 5: commit** `✨ feat(treinamento): aluno ve a versao e se atualiza pelo card`.

### Task 7: Tela de gestao (nova versao e lista de versoes)

**Files:**
- Create: `SDC/app/Modules/Treinamento/Controllers/Versoes/NovaVersaoStoreController.php`, `resources/js/Components/Organisms/Treinamento/Versoes/ListaDeVersoes.vue`, `resources/js/Components/Organisms/Treinamento/Versoes/PublicarVersaoModal.vue`
- Modify: `routes/modules/treinamento.php` (POST `treinamentos/{treinamento}/versoes`, nome `treinamentos.versoes.store`, middleware de permissao `treinamento.cursos.edit`), `Controllers/TreinamentoShowController.php` (prop `versoes` via `VersoesDoCurso::listar`, `pode_criar_versao`, `rascunho_id`), `resources/js/Pages/Treinamento/TreinamentoShow.vue`, tela de publicacao atual (passa `versao_notas`)
- Test: `SDC/tests/Feature/Treinamento/Versoes/GestaoVersaoTest.php`

**Interfaces:**
- Consumes: `NovaVersaoDoCursoService::criar`, `VersoesDoCurso::listar`.
- Regras: botao "Nova versao" so na vigente publicada, sem rascunho e com permissao; cria e redireciona para a edicao de conteudo do rascunho. Lista de versoes no visual da lista de versoes das Notas Tecnicas (`v3.0 VIGENTE`, data, inscritos/formados, "Rascunho" para o nao publicado), cada linha leva ao treinamento da versao. Publicar um rascunho de versao > 1 abre o modal pedindo "O que mudou".

- [ ] **Step 1: testes** `test_sem_permissao_recebe_403`, `test_cria_rascunho_e_redireciona`, `test_show_traz_lista_de_versoes`, `test_publicar_versao_exige_o_que_mudou`, `test_excluir_rascunho_nao_afeta_a_origem` (spec decisao 9: origem, itens e arquivos intactos).
- [ ] **Step 2:** FAIL. **Step 3:** implementar. **Step 4:** PASS + `npx vite build`.
- [ ] **Step 5: commit** `✨ feat(treinamento): botao nova versao e lista de versoes na tela do curso`.

### Task 8: Versao no certificado, validador e credencial QR

**Files:**
- Modify: `Services/CertificadoService.php` ou a view do PDF do certificado (texto "Primeiros S.O.S - v1.0" quando houver rotulo), `Controllers/ValidarCertificadoController.php` e sua pagina, `Services/CredencialQrService.php` (`certificados()` e `emAndamento()` com `versao_rotulo`), `resources/js/Components/Organisms/Treinamento/ValidarParticipante/*` e `CredencialQrBloco.vue` onde o titulo do curso aparece
- Test: `SDC/tests/Feature/Treinamento/Versoes/VersaoNoCertificadoTest.php`

**Interfaces:**
- Consumes: `VersoesDoCurso::rotulos` (uma chamada por lista).
- Regras: PDFs ja emitidos nao sao regerados; rotulo so com mais de uma versao publicada.

- [ ] **Step 1: testes** `test_validador_mostra_versao_quando_ha_duas`, `test_validador_sem_versao_quando_ha_uma`, `test_credencial_lista_versao_por_curso`.
- [ ] **Step 2:** FAIL. **Step 3:** implementar. **Step 4:** pastas `Versoes`, `Credencial` PASS.
- [ ] **Step 5: commit** `✨ feat(treinamento): versao do curso no certificado, validador e credencial`.

### Task 9: Fechamento

- [ ] **Step 1:** rodar `phpunit.sh tests/Feature/Treinamento` inteiro e comparar com o baseline da Task 0 (nenhuma falha nova).
- [ ] **Step 2:** commitar o spec e este plano: `📝 docs(treinamento): spec e plano de versoes de curso`.
- [ ] **Step 3:** listar ao usuario os artefatos de teste a limpar (banco `sdc_treinamento_versoes_teste`, `vendor`, scripts em `.superpowers/sdd/`). Nada de merge/push sem OK.
