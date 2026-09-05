# Cedec Fase 4 — Edicao de Prefeitura, Indicadores Read-Only e Foto do Prefeito — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar a tela estadual de edicao de prefeitura do modulo Cedec (`/cedec/prefeituras/{municipio}/edit`) com painel read-only de indicadores municipais e gestao da foto do prefeito, reescrevendo o formulario de prefeitura do Compdec sobre `Molecules/Form/*` para que um unico formulario sirva aos dois donos.

**Architecture:** Tres camadas, na ordem em que o repo pratica Atomic Design. `Components/Organisms/Cedec/PrefeituraFormSections.vue` concentra os campos em quatro `CollapsibleSection` com `namespace="cedec"`, montados sobre `Molecules/Form/FormField` e `Molecules/Form/ToggleField` — nenhum atomo cru, nenhum `<style scoped>`. `Components/Organisms/Compdec/PrefeituraForm.vue` deixa de ter campos proprios e vira um invólucro fino sobre esse organismo mais `FormActions`, preservando exatamente a API (`formData`, `errors`, `loading`, `@submit`, `@cancel`) que `Tabs/PrefeituraTab.vue` ja consome. `Pages/Cedec/Prefeituras/Edit.vue` orquestra: monta o `useForm` do Inertia, decide `podeEditar` por `usePermissions`, chama `cedec.prefeituras.update` e as duas rotas de foto, e posiciona `IndicadoresMunicipaisPanel.vue` (read-only, com origem do dado em tela) ao lado do formulario.

**Tech Stack:** Laravel 12 / PHP 8.3, Vue 3 + Inertia 2, Vite, Tailwind 3, Ziggy, Octane/FrankenPHP, PostgreSQL, Spatie Media Library, Spatie Permission, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-09-04-cedec-cadastro-prefeitura-design.md`
**Contrato de interfaces:** `docs/superpowers/plans/2026-09-04-cedec-contrato-interfaces.md` (secoes 0, 2, 6 e 8, inclusive "Refit do formulario do Compdec")

---

## Global Constraints

### Ambiente de execucao — corrigido em 2026-09-05, medido

Estas quatro correcoes valem para TODOS os steps deste plano e substituem qualquer
comando divergente no corpo dele.

1. **O container e `newsdc_dev_app`**, imagem `newsdc-swoole-dev` (Swoole, nao FrankenPHP),
   com a aplicacao em `/var/www`. O nome `newsdc_frankenphp_local` do `.claude/kernel.py`
   NAO EXISTE. O Postgres de desenvolvimento e `newsdc_dev_db`, publicado no host em 5434.

2. **Teste roda no HOST, nunca no container.** `docker exec newsdc_dev_app php artisan test`
   falha com `Command "test" is not defined` — a imagem nao tem dev dependencies. Exporte
   uma vez por terminal, a partir de `SDC/`:

   ```bash
   export APP_CONFIG_CACHE=/nao/existe/config.php
   export DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5434 DB_DATABASE=sdc DB_USERNAME=sdc
   export DB_PASSWORD="$(grep -m1 '^DB_PASSWORD=' .env | cut -d= -f2-)"
   ```

   `APP_CONFIG_CACHE` para caminho inexistente e obrigatorio: sem ele o PHPUnit do host
   escreve no `bootstrap/cache` compartilhado com o container e derruba o Octane, que so
   volta com restart de ~3min. Os `DB_*` sao obrigatorios porque o `.env` aponta
   `DB_HOST=newsdc_db` (nome de rede Docker que o host nao resolve) e porque o
   `.env.testing` forca sqlite `:memory:` — e como `tests/TestCase.php` e vazio e nao roda
   migration, cair no sqlite da `no such table` na suite inteira.

3. **Os testes rodam contra o banco de DESENVOLVIMENTO.** Nenhum teste pode fazer `update()`
   ou `delete()` sem `where` restrito as linhas que ele mesmo criou, e assercao de contagem
   tem de ser relativa a um "antes", nunca total absoluto.

4. **`SDC/tests` esta no `.gitignore`.** Escrever o teste continua obrigatorio, mas ele NAO
   e versionado: todo `git add` dos steps leva so os arquivos de producao. Incluir caminho
   sob `SDC/tests` faz o `git add` ser recusado.

Valem para toda task deste plano. Valores copiados literalmente da spec e do contrato.

**Repositorio e comandos**

- App executavel em `NewSDC/SDC`. Todo caminho neste plano e relativo a essa pasta, salvo quando escrito por extenso.
- Testes: **PHPUnit, nao Pest.** Classe com `declare(strict_types=1)`, namespace `Tests\Feature\Cedec`, trait `Illuminate\Foundation\Testing\DatabaseTransactions`, `Inertia\Testing\AssertableInertia` para props, `Spatie\Permission\Models\Permission` para conceder slug.
- Verificacao backend: `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=<Nome>`.
- Lint PHP: `docker exec newsdc_dev_app php -l /var/www/<caminho>`.
- Verificacao frontend: `npm run build` na pasta `SDC`.
- Depois de mudar PHP: `docker exec newsdc_dev_app php artisan octane:reload` (~1s). Restart do container so para `.env`, `config/` ou extensao — custa ~3min.
- **Sem emoji dentro do codigo.** Emoji so na mensagem de commit (gitmoji).
- Commits: `<emoji> tipo(cedec): descricao` em pt-BR. Commit atomico: agrupar os arquivos que entregam UMA mudanca. **Nao incluir trailer de co-autor.**
- Arquivo de teste criado so para depuracao nao entra no commit. Os tres arquivos de teste nomeados neste plano sao entregaveis e entram.

**Regras duras de UI (`.claude/skills/frontend/03 - Layout` e `04 - Responsividade`)**

- A calha horizontal e do `<main>`; a raiz da pagina **nao** leva `p-*` nem `px-*`. A raiz leva `pb-8`.
- O ritmo vertical de 24px vem de UMA fonte: `space-y-6` no pai **ou** `mb-6` nos filhos. Usando `space-y-6`, todo filho passa `:espaco-inferior="false"`.
- `document.documentElement.scrollWidth - document.documentElement.clientWidth === 0` em 375px e em 840px. Quem transborda rola dentro de si, com `min-w-0` no pai flex.
- Breakpoint por `useMobile`, alinhado em `lg` (1024px). Nunca `window.innerWidth`.
- Dark mode por CLASSE (`dark:`), nunca `prefers-color-scheme`.
- **Nenhum `<style scoped>` novo.** So Tailwind. E o `<style scoped>` de `Organisms/Compdec/PrefeituraForm.vue` tem de SAIR.
- Atomo e molecula nao chamam API nem estado global; organismo concentra interacao; a pagina orquestra.
- `SelectInput` le `value`/`id` e `label`/`name`/`text`. Backend que manda `nome` precisa ser mapeado para `{ value, label }`.
- Atributo solto (`inputmode`, `maxlength`, `step`) passado a `FormField` cai na div raiz e NAO chega ao input: tem de ser prop declarada e repassada.

**Limites e regras de dominio**

- Foto do prefeito: colecao `foto_prefeito` (`Prefeitura::MEDIA_FOTO_PREFEITO`), `singleFile`, mimes `image/jpeg`, `image/png`, `image/webp`, conversao `thumb` `Fit::Contain` 200x200 `nonQueued`, disco `config('compdec.disk')`. **Ja existe no Model — nao reimplementar.**
- Limite de tamanho da foto: `config('compdec.upload_limits.foto_prefeito')` = `300 * 1024` bytes.
- Indicadores municipais sao **READ-ONLY na fase 1** e nao entram em regra de validacao nenhuma.
- `origem` dos indicadores e a string literal `'cedec_municipio (espelho do legado)'`, exibida em tela.
- Escopo de permissao: `compdec.prefeitura.edit` na aba do Compdec, `cedec.prefeituras.edit` na tela do Cedec. Resolvido por prop `podeEditar`, **nunca** por checagem dentro do componente de formulario.

---

## Estrutura de arquivos

| Arquivo | Responsabilidade |
| --- | --- |
| `resources/js/Components/Molecules/Form/FormField.vue` (modificar) | ganha a prop `step`, repassada ao `TextInput`. Sem ela `step` fica na div raiz e latitude/longitude/aliquota perdem o incremento decimal |
| `resources/js/Components/Organisms/Cedec/PrefeituraFormSections.vue` (criar) | os quatro blocos de campos (Prefeito, Contatos institucionais, Endereco, INSS) sobre `Molecules/Form/*` dentro de `CollapsibleSection namespace="cedec"`. Nao conhece rota, nem `useForm`, nem permissao |
| `resources/js/Components/Organisms/Compdec/PrefeituraForm.vue` (reescrever) | invólucro fino: `PrefeituraFormSections` + `FormActions`. Mantem a API que `PrefeituraTab.vue` ja usa. `<style scoped>` deletado |
| `resources/js/Components/Organisms/Compdec/Tabs/PrefeituraTab.vue` (modificar) | passa `:pode-editar="canEdit"` ao formulario. Uma linha |
| `resources/js/Components/Organisms/Cedec/IndicadoresMunicipaisPanel.vue` (criar) | painel read-only dos indicadores, com aviso de nao-editavel e a origem do dado |
| `resources/js/Pages/Cedec/Prefeituras/Edit.vue` (criar) | orquestra: `useForm`, `podeEditar`, submit, upload e remocao de foto |
| `resources/js/Support/moduleIcons.js` (modificar, idempotente) | registra `prefeituras: apartment` em `MODULE_ICONS` se ainda nao existir |
| `app/Modules/Cedec/Controllers/PrefeituraController.php` (modificar) | `uploadFoto` valida mime e tamanho antes de chamar o service |
| `tests/Feature/Cedec/CedecAbaCompdecRefitTest.php` (criar) | prova que a aba do Compdec sobreviveu ao refit e que a divida de UI saiu |
| `tests/Feature/Cedec/CedecPrefeituraEdicaoTest.php` (criar) | render da pagina, update valido, update invalido campo a campo, 403 sem `.edit` |
| `tests/Feature/Cedec/CedecPrefeituraFotoTest.php` (criar) | mime rejeitado, tamanho acima do limite, upload feliz, remocao feliz |

Os tres arquivos de teste comecam com `Cedec` de proposito: `--filter=Cedec` pega os tres de uma vez.

---

## Decisoes tomadas neste plano (leia antes de comecar)

1. **Um formulario, dois donos, sem duplicar campo.** A spec pede `PrefeituraFormSections.vue` (secao 6) e tambem diz que `PrefeituraForm.vue` passa a ser compartilhado (secao 6.1). Isso so fecha de uma forma: os campos moram em `PrefeituraFormSections.vue`; `PrefeituraForm.vue` fica sendo o invólucro que a aba do Compdec ja sabe consumir (`formData` / `errors` / `loading` / `@submit` / `@cancel`), e o `Edit.vue` do Cedec consome o organismo de secoes direto, porque a pagina ja tem `useForm` e monta as proprias acoes. Zero campo escrito duas vezes.

2. **Prop `camposInstitucionais` (adicao declarada ao contrato).** `App\Modules\Compdec\Requests\UpsertPrefeituraRequest` NAO valida `prefeito_partido`, `email_prefeitura`, `email_prefeitura_2`, `email_prefeitura_3`, `tel_prefeitura`, `tel_prefeitura_2` nem `fax_prefeitura`. Se o formulario compartilhado mostrasse esses sete campos na aba do Compdec, o usuario preencheria e o `validated()` descartaria em silencio. Por isso `PrefeituraFormSections` recebe `camposInstitucionais: Boolean` (default `true`): o Cedec deixa em `true`, o invólucro do Compdec passa `false`. E adicao explicita as props do contrato (secao 8), declarada em "Produces" da Task 1.

3. **`step` vira prop do `FormField`.** Latitude, longitude e aliquota de INSS eram `type="number" step="0.0000001"` sobre o `TextInput` cru — funcionava porque a raiz do `TextInput` E o `<input>`. Passando pelo `FormField`, cuja raiz e uma `div`, o `step` some. Nao ha alternativa por mascara: `MASCARAS.coordenada` e `MASCARAS.decimal` aceitam virgula, e a regra `numeric` do `UpdatePrefeituraRequest` recusaria `-19,91` — o helper `NormalizaEntrada` que troca virgula por ponto e do modulo Cisterna, nao e middleware global.

4. **Nenhum `SelectInput` sobra neste formulario.** Macrorregiao e territorio de desenvolvimento sao indicadores read-only nesta fase; o unico select do formulario antigo era `inss_tem_cobranca` com Sim/Nao, substituido por `ToggleField`, que e booleano de verdade. Logo a regra de mapear `{ value, label }` nao tem aplicacao aqui — ela volta a valer na fase 5.

5. **A foto nao usa `Molecules/Upload/*`.** `DropZone.vue` e `FileUploadItem.vue` tem cor cravada em tom escuro (`bg-slate-800`, `text-slate-200`, `border-slate-700`) sem par claro: no tema claro ficam ilegiveis, o que viola a regra de dark mode por classe. O cartao de foto fica na pagina, que e quem orquestra transporte, com `<input type="file" class="hidden">` e Tailwind com par claro/escuro.

---

## Task 1: Refit do formulario de prefeitura (Compdec) sobre Molecules/Form

**Files:**
- Modify: `resources/js/Components/Molecules/Form/FormField.vue`
- Create: `resources/js/Components/Organisms/Cedec/PrefeituraFormSections.vue`
- Modify (reescrita total): `resources/js/Components/Organisms/Compdec/PrefeituraForm.vue`
- Modify: `resources/js/Components/Organisms/Compdec/Tabs/PrefeituraTab.vue`
- Test: `tests/Feature/Cedec/CedecAbaCompdecRefitTest.php`

**Interfaces:**

- Consumes (fases 1 e 2, ja existentes):
  - `App\Modules\Compdec\Models\Prefeitura` com `$fillable` estendido: `prefeito_partido`, `email_prefeitura`, `email_prefeitura_2`, `email_prefeitura_3`, `tel_prefeitura`, `tel_prefeitura_2`, `fax_prefeitura`.
  - Colunas homonimas em `compdec_prefeituras`.
  - `App\Modules\Compdec\DTOs\PrefeituraDTO` estendido com as sete propriedades camelCase.
- Consumes (ja no repo hoje):
  - `resources/js/Components/Molecules/CollapsibleSection.vue` — props `namespace` (obrigatoria), `sectionId` (obrigatoria), `title`, `subtitle`, `icon`, `tom` (`info|success|warning|danger|neutro`), `statusText`, `expandidoPorPadrao`.
  - `resources/js/Components/Molecules/Form/FormField.vue` — props `modelValue`, `label`, `type`, `placeholder`, `disabled`, `readonly`, `required`, `error`, `inputmode`, `mask`, `maxlength`, `hint`, `size`, `labelSize`.
  - `resources/js/Components/Molecules/Form/ToggleField.vue` — props `modelValue` (Boolean), `label`, `description`, `icon`.
  - `resources/js/Components/Molecules/Form/FormActions.vue` — props `showCancel`, `showSubmit`, `cancelLabel`, `submitLabel`, `submitVariant`, `loading`, `disabled`, `align`; emits `cancel`, `submit`.
  - Rota `compdec.prefeitura.upsert` (`Route::match(['post','put'], '/orgaos/{orgao}/prefeitura')`), gated por `can:compdec.prefeitura.edit`.
  - `App\Policies\OrgaoPolicy::view` (slug `compdec.orgaos.view`) e `App\Policies\PrefeituraPolicy::update` (slug `compdec.prefeitura.edit`).
- Produces (as tasks 2 e 3 dependem disto):
  - `FormField.vue` passa a declarar `step: { type: [String, Number], default: undefined }` e repassa `:step="step"` ao `TextInput`.
  - `Components/Organisms/Cedec/PrefeituraFormSections.vue` com props `form: Object` (required), `errors: Object` (default `{}`), `podeEditar: Boolean` (default `false`) e **`camposInstitucionais: Boolean` (default `true`, adicao declarada ao contrato)**. Sem emits.
  - `Components/Organisms/Compdec/PrefeituraForm.vue` com props `formData: Object` (required), `errors: Object`, `loading: Boolean`, `podeEditar: Boolean` (default `true`) e emits `submit` (payload = `formData`) e `cancel` — a mesma API de antes, mais `podeEditar`.

---

- [ ] **Step 1: Escrever o teste de regressao da aba do Compdec**

Criar `tests/Feature/Cedec/CedecAbaCompdecRefitTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * A aba de prefeitura do Compdec passou a montar o MESMO organismo de campos
 * que a tela estadual do Cedec. Este teste e o contrato de que o refit nao
 * quebrou o dono antigo: a aba renderiza e o upsert continua gravando.
 */
class CedecAbaCompdecRefitTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSOES = [
        'compdec.orgaos.view',
        'compdec.prefeitura.view',
        'compdec.prefeitura.edit',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (self::PERMISSOES as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function usuarioAutorizado(): User
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo(self::PERMISSOES);

        $this->actingAs($usuario);

        return $usuario;
    }

    public function test_a_aba_de_prefeitura_do_compdec_continua_renderizando(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Ouro Preto']);
        $orgao = Orgao::factory()->compdec()->create(['municipio_id' => $municipio->id]);
        Prefeitura::factory()->create([
            'municipio_id' => $municipio->id,
            'prefeito_nome' => 'Joana Ferreira',
        ]);

        $this->usuarioAutorizado();

        $this->get(route('compdec.orgaos.show', $orgao->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Compdec/OrgaoShow')
                ->where('orgao.prefeitura.prefeito_nome', 'Joana Ferreira')
                ->where('canManage', true)
            );
    }

    public function test_o_upsert_da_aba_do_compdec_continua_persistindo(): void
    {
        $municipio = Municipio::factory()->create();
        $orgao = Orgao::factory()->compdec()->create(['municipio_id' => $municipio->id]);

        $this->usuarioAutorizado();

        $this->put(route('compdec.prefeitura.upsert', $orgao->id), [
            'prefeito_nome' => 'Carlos Andrade',
            'prefeito_telefone' => '(31) 3333-3333',
            'prefeito_celular' => '(31) 99999-9999',
            'prefeito_email' => 'prefeito@ouropreto.mg.gov.br',
            'endereco' => 'Praca Tiradentes, 20',
            'bairro' => 'Centro',
            'cep' => '35400-000',
            'latitude' => '-20.3856000',
            'longitude' => '-43.5035000',
            'inss_tem_cobranca' => true,
            'inss_aliquota' => '2.50',
            'inss_lei_cobranca' => 'Lei municipal 1234/2020',
            'inss_responsavel' => 'Setor de Tributos',
        ])->assertRedirect();

        $this->assertDatabaseHas('compdec_prefeituras', [
            'municipio_id' => $municipio->id,
            'prefeito_nome' => 'Carlos Andrade',
            'cep' => '35400-000',
            'inss_lei_cobranca' => 'Lei municipal 1234/2020',
        ]);
    }

    public function test_o_formulario_compartilhado_nao_tem_mais_divida_de_ui(): void
    {
        $arquivo = resource_path('js/Components/Organisms/Compdec/PrefeituraForm.vue');
        $fonte = (string) file_get_contents($arquivo);

        $this->assertStringNotContainsString('<style scoped>', $fonte, 'O style scoped do PrefeituraForm tinha de sair no refit.');
        $this->assertStringNotContainsString('Atoms/Input/TextInput.vue', $fonte, 'O formulario nao pode mais importar o input cru.');
        $this->assertStringNotContainsString('Atoms/Input/SelectInput.vue', $fonte, 'O formulario nao pode mais importar o select cru.');
        $this->assertStringContainsString('Organisms/Cedec/PrefeituraFormSections.vue', $fonte, 'O formulario tem de montar o organismo de secoes compartilhado.');
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que ele falha**

Run: `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=CedecAbaCompdecRefitTest`

Expected: os dois primeiros metodos passam (a aba ainda e a antiga) e `test_o_formulario_compartilhado_nao_tem_mais_divida_de_ui` FALHA com `Failed asserting that '...' does not contain "<style scoped>"`.

- [ ] **Step 3: Declarar a prop `step` no `FormField`**

Em `resources/js/Components/Molecules/Form/FormField.vue`, no `<template>`, dentro do `<TextInput>`, logo depois de `:inputmode="inputmode"`, acrescentar a linha:

```html
      :step="step"
```

E no bloco `defineProps`, logo depois da prop `maxlength`, acrescentar:

```js
  // Repassada ao input pelo mesmo motivo de `inputmode`: a raiz do FormField e
  // uma div, entao `step` solto nunca chegava ao campo. Latitude, longitude e
  // aliquota dependem dele para incrementar em casa decimal.
  step: {
    type: [String, Number],
    default: undefined,
  },
```

- [ ] **Step 4: Criar o organismo de secoes do formulario**

Criar `resources/js/Components/Organisms/Cedec/PrefeituraFormSections.vue`:

```vue
<template>
  <div class="space-y-6">
    <CollapsibleSection
      namespace="cedec"
      section-id="prefeitura-prefeito"
      title="Prefeito"
      subtitle="Nome, partido e contato pessoal do prefeito"
      :icon="UserIcon"
      tom="info"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <FormField
          v-model="form.prefeito_nome"
          label="Nome do prefeito"
          placeholder="Nome completo"
          maxlength="255"
          :readonly="!podeEditar"
          :error="errors.prefeito_nome"
        />
        <FormField
          v-if="camposInstitucionais"
          v-model="form.prefeito_partido"
          label="Partido"
          placeholder="Sigla do partido"
          maxlength="60"
          :readonly="!podeEditar"
          :error="errors.prefeito_partido"
        />
        <FormField
          v-model="form.prefeito_telefone"
          label="Telefone do prefeito"
          mask="telefone"
          inputmode="tel"
          maxlength="15"
          placeholder="(31) 3333-3333"
          :readonly="!podeEditar"
          :error="errors.prefeito_telefone"
        />
        <FormField
          v-model="form.prefeito_celular"
          label="Celular do prefeito"
          mask="telefone"
          inputmode="tel"
          maxlength="15"
          placeholder="(31) 99999-9999"
          :readonly="!podeEditar"
          :error="errors.prefeito_celular"
        />
        <FormField
          v-model="form.prefeito_email"
          label="E-mail do prefeito"
          type="email"
          inputmode="email"
          maxlength="255"
          placeholder="prefeito@municipio.mg.gov.br"
          :readonly="!podeEditar"
          :error="errors.prefeito_email"
        />
      </div>
    </CollapsibleSection>

    <!--
      Contatos institucionais sao as colunas acrescentadas na fase 1. O
      UpsertPrefeituraRequest do Compdec nao as valida, e campo que o backend
      descarta em silencio e pior que campo ausente: por isso a secao inteira
      fica atras de `camposInstitucionais`.
    -->
    <CollapsibleSection
      v-if="camposInstitucionais"
      namespace="cedec"
      section-id="prefeitura-contatos"
      title="Contatos institucionais"
      subtitle="E-mails e telefones da prefeitura, nao do prefeito"
      :icon="EnvelopeIcon"
      tom="success"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <FormField
          v-model="form.email_prefeitura"
          label="E-mail institucional 1"
          type="email"
          inputmode="email"
          maxlength="255"
          placeholder="prefeitura@municipio.mg.gov.br"
          hint="Alimenta o relatorio de contatos da CEDEC"
          :readonly="!podeEditar"
          :error="errors.email_prefeitura"
        />
        <FormField
          v-model="form.email_prefeitura_2"
          label="E-mail institucional 2"
          type="email"
          inputmode="email"
          maxlength="255"
          :readonly="!podeEditar"
          :error="errors.email_prefeitura_2"
        />
        <FormField
          v-model="form.email_prefeitura_3"
          label="E-mail institucional 3"
          type="email"
          inputmode="email"
          maxlength="255"
          :readonly="!podeEditar"
          :error="errors.email_prefeitura_3"
        />
        <FormField
          v-model="form.tel_prefeitura"
          label="Telefone da prefeitura 1"
          mask="telefone"
          inputmode="tel"
          maxlength="15"
          placeholder="(31) 3333-3333"
          :readonly="!podeEditar"
          :error="errors.tel_prefeitura"
        />
        <FormField
          v-model="form.tel_prefeitura_2"
          label="Telefone da prefeitura 2"
          mask="telefone"
          inputmode="tel"
          maxlength="15"
          :readonly="!podeEditar"
          :error="errors.tel_prefeitura_2"
        />
        <FormField
          v-model="form.fax_prefeitura"
          label="Fax da prefeitura"
          mask="telefone"
          inputmode="tel"
          maxlength="15"
          :readonly="!podeEditar"
          :error="errors.fax_prefeitura"
        />
      </div>
    </CollapsibleSection>

    <CollapsibleSection
      namespace="cedec"
      section-id="prefeitura-endereco"
      title="Endereco"
      subtitle="Logradouro, bairro, CEP e coordenada da sede"
      :icon="MapPinIcon"
      tom="info"
    >
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <FormField
          v-model="form.endereco"
          label="Logradouro"
          placeholder="Rua, numero"
          :readonly="!podeEditar"
          :error="errors.endereco"
          class="sm:col-span-2 lg:col-span-3"
        />
        <FormField
          v-model="form.bairro"
          label="Bairro"
          placeholder="Centro"
          maxlength="120"
          :readonly="!podeEditar"
          :error="errors.bairro"
        />
        <FormField
          v-model="form.cep"
          label="CEP"
          mask="cep"
          inputmode="numeric"
          maxlength="9"
          placeholder="00000-000"
          :readonly="!podeEditar"
          :error="errors.cep"
        />
        <div class="hidden lg:block"></div>
        <FormField
          v-model="form.latitude"
          label="Latitude"
          type="number"
          step="0.0000001"
          inputmode="decimal"
          placeholder="-19.9166813"
          hint="Entre -90 e 90, com ponto decimal"
          :readonly="!podeEditar"
          :error="errors.latitude"
        />
        <FormField
          v-model="form.longitude"
          label="Longitude"
          type="number"
          step="0.0000001"
          inputmode="decimal"
          placeholder="-43.9344931"
          hint="Entre -180 e 180, com ponto decimal"
          :readonly="!podeEditar"
          :error="errors.longitude"
        />
      </div>
    </CollapsibleSection>

    <CollapsibleSection
      namespace="cedec"
      section-id="prefeitura-inss"
      title="INSS"
      subtitle="Cobranca do municipio sobre servicos prestados"
      :icon="BanknotesIcon"
      tom="warning"
    >
      <div class="space-y-4">
        <div :class="podeEditar ? '' : 'pointer-events-none opacity-60'">
          <ToggleField
            v-model="form.inss_tem_cobranca"
            label="Possui cobranca de INSS"
            description="Ligue para informar aliquota, lei e responsavel"
          />
        </div>

        <div v-if="form.inss_tem_cobranca" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FormField
            v-model="form.inss_aliquota"
            label="Aliquota (%)"
            type="number"
            step="0.01"
            inputmode="decimal"
            placeholder="2.50"
            hint="Entre 0 e 100"
            :readonly="!podeEditar"
            :error="errors.inss_aliquota"
          />
          <FormField
            v-model="form.inss_lei_cobranca"
            label="Lei de cobranca"
            placeholder="Lei municipal 1234/2020"
            maxlength="120"
            :readonly="!podeEditar"
            :error="errors.inss_lei_cobranca"
          />
          <FormField
            v-model="form.inss_responsavel"
            label="Responsavel pelo recolhimento"
            placeholder="Nome do responsavel ou setor"
            maxlength="255"
            :readonly="!podeEditar"
            :error="errors.inss_responsavel"
          />
        </div>
      </div>
    </CollapsibleSection>
  </div>
</template>

<script setup>
import {
  BanknotesIcon,
  EnvelopeIcon,
  MapPinIcon,
  UserIcon,
} from '@heroicons/vue/24/outline';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';

/**
 * Campos da prefeitura, em quatro secoes. E o UNICO lugar onde esses campos
 * existem: a aba do Compdec e o Edit estadual do Cedec montam este mesmo
 * organismo.
 *
 * Nao conhece rota, nao conhece `useForm` e nao consulta permissao. Quem sabe
 * se pode editar e o consumidor, que informa em `podeEditar` -- os dois donos
 * tem slug diferente (`compdec.prefeitura.edit` contra
 * `cedec.prefeituras.edit`) e uma checagem interna teria de escolher um.
 */
defineProps({
  /** Objeto reativo de campos: `useForm` do Inertia ou `reactive` simples. */
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  podeEditar: { type: Boolean, default: false },
  /**
   * Liga as sete colunas acrescentadas na fase 1 (partido e contatos
   * institucionais). Fica em `false` na aba do Compdec, cujo
   * UpsertPrefeituraRequest ainda nao as valida.
   */
  camposInstitucionais: { type: Boolean, default: true },
});
</script>
```

- [ ] **Step 5: Reescrever o formulario do Compdec como invólucro**

Substituir o conteudo INTEIRO de `resources/js/Components/Organisms/Compdec/PrefeituraForm.vue` por:

```vue
<template>
  <form class="space-y-6" @submit.prevent="handleSubmit">
    <PrefeituraFormSections
      :form="formData"
      :errors="errors"
      :pode-editar="podeEditar"
      :campos-institucionais="false"
    />

    <FormActions
      submit-label="Salvar Prefeitura"
      :loading="loading"
      :disabled="!podeEditar"
      @cancel="handleCancel"
      @submit="handleSubmit"
    />
  </form>
</template>

<script setup>
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import PrefeituraFormSections from '@/Components/Organisms/Cedec/PrefeituraFormSections.vue';

/**
 * Formulario de prefeitura da aba do Compdec.
 *
 * Depois do refit ele nao tem campo proprio: os campos vivem em
 * `Organisms/Cedec/PrefeituraFormSections.vue`, o mesmo organismo que a tela
 * estadual do Cedec monta. Um formulario, dois donos. O que sobrou aqui e a
 * API que `Tabs/PrefeituraTab.vue` ja consumia -- por isso a aba nao precisou
 * mudar de forma.
 *
 * `camposInstitucionais` fica em `false` de proposito: o
 * `UpsertPrefeituraRequest` do Compdec nao valida partido nem os contatos
 * institucionais, e mostrar campo que o backend descarta e pior que esconder.
 */
const props = defineProps({
  formData: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  podeEditar: { type: Boolean, default: true },
});

const emit = defineEmits(['submit', 'cancel']);

function handleSubmit() {
  if (! props.podeEditar) {
    return;
  }

  emit('submit', props.formData);
}

function handleCancel() {
  emit('cancel');
}
</script>
```

- [ ] **Step 6: Passar `podeEditar` da aba para o formulario**

Em `resources/js/Components/Organisms/Compdec/Tabs/PrefeituraTab.vue`, no bloco `<PrefeituraForm ... />`, acrescentar a prop logo depois de `:loading="loading"`:

```html
        :pode-editar="canEdit"
```

O bloco fica assim:

```html
      <PrefeituraForm
        :form-data="formData"
        :errors="errors"
        :loading="loading"
        :pode-editar="canEdit"
        @submit="handleSubmit"
        @cancel="cancelEdit"
      />
```

- [ ] **Step 7: Rodar o teste e confirmar que passa**

Run: `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=CedecAbaCompdecRefitTest`

Expected: PASS, 3 testes, sem falha.

- [ ] **Step 8: Compilar o frontend**

Run (na pasta `SDC`): `npm run build`

Expected: build conclui sem erro de resolucao de import e sem aviso de `@apply` orfao vindo de `PrefeituraForm.vue`.

- [ ] **Step 9: Conferir na tela que a aba do Compdec segue de pe**

Abrir `/compdec/orgaos/{id}` de um orgao COMPDEC com municipio vinculado, ir na aba Prefeitura, clicar em "Editar Prefeitura" e conferir:
- as tres secoes (Prefeito, Endereco, INSS) aparecem colapsaveis;
- a secao "Contatos institucionais" NAO aparece;
- latitude e longitude incrementam de 0,0000001 nas setas do input (prova que a prop `step` chegou);
- salvar grava e a aba volta ao modo de leitura.

Rodar no console, com a janela em 375px e depois em 840px:

```js
document.documentElement.scrollWidth - document.documentElement.clientWidth
```

Expected: `0` nas duas larguras.

- [ ] **Step 10: Commit**

```bash
git add resources/js/Components/Molecules/Form/FormField.vue \
        resources/js/Components/Organisms/Cedec/PrefeituraFormSections.vue \
        resources/js/Components/Organisms/Compdec/PrefeituraForm.vue \
        resources/js/Components/Organisms/Compdec/Tabs/PrefeituraTab.vue
git commit -m "♻️ refactor(cedec): formulario de prefeitura sobre Molecules/Form, sem style scoped"
```

---

## Task 2: Pagina de edicao estadual e painel de indicadores read-only

**Files:**
- Create: `resources/js/Components/Organisms/Cedec/IndicadoresMunicipaisPanel.vue`
- Create: `resources/js/Pages/Cedec/Prefeituras/Edit.vue`
- Modify (idempotente): `resources/js/Support/moduleIcons.js`
- Test: `tests/Feature/Cedec/CedecPrefeituraEdicaoTest.php`

**Interfaces:**

- Consumes (Task 1): `Components/Organisms/Cedec/PrefeituraFormSections.vue` com props `form`, `errors`, `podeEditar`, `camposInstitucionais`; `FormField` com a prop `step`.
- Consumes (fase 1): colunas `prefeito_partido`, `email_prefeitura`, `email_prefeitura_2`, `email_prefeitura_3`, `tel_prefeitura`, `tel_prefeitura_2`, `fax_prefeitura` em `compdec_prefeituras`, no `$fillable` de `Prefeitura` e no `PrefeituraDTO`.
- Consumes (fase 2):
  - `App\Modules\Cedec\Services\CedecPrefeituraService::obterPorMunicipio(int $municipioId): ?Prefeitura`
  - `App\Modules\Cedec\Services\CedecPrefeituraService::indicadoresMunicipais(int $municipioId): array{populacao: ?float, pop_rural: ?int, area: ?string, macrorregiao: ?string, territorio_desenv: ?string, distancia_bh: ?float, qtd_pipa: ?int, latitude: ?string, longitude: ?string, origem: string}`
  - `App\Modules\Cedec\Services\CedecPrefeituraService::upsertPorMunicipio(int $municipioId, PrefeituraDTO $dto): Prefeitura`
  - `App\Modules\Cedec\Requests\UpdatePrefeituraRequest` — `authorize()` por `cedec.prefeituras.edit`; regras `nullable` com `cep` em `regex:/^\d{5}-?\d{3}$/`, `latitude` `numeric between:-90,90`, `longitude` `numeric between:-180,180`, `inss_aliquota` `numeric between:0,100`, os cinco e-mails com `email`; mensagem `'cep.regex' => 'CEP deve estar no formato 00000-000 ou 00000000.'`
  - `App\Modules\Cedec\Controllers\PrefeituraController::edit(Municipio $municipio): \Inertia\Response` renderizando `Cedec/Prefeituras/Edit` com as props `municipio` (`{ id, nome, codigo_ibge, uf }`), `prefeitura` (todas as colunas de `compdec_prefeituras` mais `foto_prefeito_url`) e `indicadores`
  - Rotas `cedec.prefeituras.index` (`can:cedec.prefeituras.view`), `cedec.prefeituras.edit` (`can:cedec.prefeituras.view`), `cedec.prefeituras.update` (`can:cedec.prefeituras.edit`)
- Consumes (ja no repo): `Organisms/PageHeader.vue` (props `title`, `description`, `icon`, `iconImage`, `variant`, `espacoInferior`, slot `actions`), `Atoms/Button/ActionButton.vue`, `Composables/auth` (`usePermissions().can`), `Composables/useMobile` (`useMobile().isMobile`), `Support/moduleIcons.js` (`moduleIcon`), `Layouts/AuthenticatedLayout.vue`.
- Produces (a Task 3 depende disto):
  - `Components/Organisms/Cedec/IndicadoresMunicipaisPanel.vue` com prop unica `indicadores: Object` (required).
  - `Pages/Cedec/Prefeituras/Edit.vue` com o `useForm` de 20 campos e a constante `podeEditar` derivada de `usePermissions().can('cedec.prefeituras.edit')`.
  - Entrada `prefeituras: apartment` em `MODULE_ICONS`.

---

- [ ] **Step 1: Escrever o teste de render e de update**

Criar `tests/Feature/Cedec/CedecPrefeituraEdicaoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tela estadual de edicao de prefeitura: render, persistencia dos campos
 * acrescentados na fase 1 e recusa campo a campo.
 */
class CedecPrefeituraEdicaoTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSOES = [
        'cedec.prefeituras.view',
        'cedec.prefeituras.edit',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (self::PERMISSOES as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $permissoes
     */
    private function comPermissao(array $permissoes): self
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo($permissoes);

        $this->actingAs($usuario);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadValido(): array
    {
        return [
            'prefeito_nome' => 'Ana Paula Rezende',
            'prefeito_partido' => 'PARTIDO X',
            'prefeito_telefone' => '(31) 3333-3333',
            'prefeito_celular' => '(31) 99999-9999',
            'prefeito_email' => 'prefeita@municipio.mg.gov.br',
            'email_prefeitura' => 'gabinete@municipio.mg.gov.br',
            'email_prefeitura_2' => 'protocolo@municipio.mg.gov.br',
            'email_prefeitura_3' => 'ouvidoria@municipio.mg.gov.br',
            'tel_prefeitura' => '(31) 3222-1000',
            'tel_prefeitura_2' => '(31) 3222-1001',
            'fax_prefeitura' => '(31) 3222-1002',
            'endereco' => 'Praca da Matriz, 100',
            'bairro' => 'Centro',
            'cep' => '35400-000',
            'latitude' => '-20.3856000',
            'longitude' => '-43.5035000',
            'inss_tem_cobranca' => true,
            'inss_aliquota' => '2.50',
            'inss_lei_cobranca' => 'Lei municipal 1234/2020',
            'inss_responsavel' => 'Setor de Tributos',
        ];
    }

    public function test_a_pagina_de_edicao_existe_e_traz_municipio_prefeitura_e_indicadores(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Congonhas']);
        Prefeitura::factory()->create([
            'municipio_id' => $municipio->id,
            'prefeito_nome' => 'Ana Paula Rezende',
        ]);

        $this->comPermissao(self::PERMISSOES)
            ->get(route('cedec.prefeituras.edit', $municipio->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Cedec/Prefeituras/Edit')
                ->where('municipio.nome', 'Congonhas')
                ->where('prefeitura.prefeito_nome', 'Ana Paula Rezende')
                ->has('indicadores.origem')
                ->where('indicadores.origem', 'cedec_municipio (espelho do legado)')
            );
    }

    public function test_a_pagina_abre_para_municipio_sem_linha_de_prefeitura(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Itabirito']);

        $this->comPermissao(self::PERMISSOES)
            ->get(route('cedec.prefeituras.edit', $municipio->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Cedec/Prefeituras/Edit')
                ->where('municipio.nome', 'Itabirito')
            );
    }

    public function test_update_valido_persiste_os_campos_novos(): void
    {
        $municipio = Municipio::factory()->create();

        $this->comPermissao(self::PERMISSOES)
            ->put(route('cedec.prefeituras.update', $municipio->id), $this->payloadValido())
            ->assertRedirect();

        $this->assertDatabaseHas('compdec_prefeituras', [
            'municipio_id' => $municipio->id,
            'prefeito_nome' => 'Ana Paula Rezende',
            'prefeito_partido' => 'PARTIDO X',
            'email_prefeitura' => 'gabinete@municipio.mg.gov.br',
            'email_prefeitura_2' => 'protocolo@municipio.mg.gov.br',
            'email_prefeitura_3' => 'ouvidoria@municipio.mg.gov.br',
            'tel_prefeitura' => '(31) 3222-1000',
            'tel_prefeitura_2' => '(31) 3222-1001',
            'fax_prefeitura' => '(31) 3222-1002',
        ]);
    }

    public function test_update_atualiza_a_mesma_linha_que_a_aba_do_compdec_usa(): void
    {
        $municipio = Municipio::factory()->create();
        Prefeitura::factory()->create([
            'municipio_id' => $municipio->id,
            'prefeito_nome' => 'Nome Antigo',
        ]);

        $this->comPermissao(self::PERMISSOES)
            ->put(route('cedec.prefeituras.update', $municipio->id), $this->payloadValido())
            ->assertRedirect();

        $this->assertSame(
            1,
            Prefeitura::query()->where('municipio_id', $municipio->id)->count(),
            'O upsert por municipio nao pode criar uma segunda linha para o mesmo municipio.'
        );
    }

    public function test_update_invalido_devolve_erro_por_campo(): void
    {
        $municipio = Municipio::factory()->create();

        $payload = array_merge($this->payloadValido(), [
            'cep' => '3540-00',
            'prefeito_email' => 'prefeita@@municipio',
            'email_prefeitura' => 'gabinete sem arroba',
            'latitude' => '-120.5',
            'longitude' => '200.1',
            'inss_aliquota' => '180',
        ]);

        $this->comPermissao(self::PERMISSOES)
            ->put(route('cedec.prefeituras.update', $municipio->id), $payload)
            ->assertSessionHasErrors([
                'cep',
                'prefeito_email',
                'email_prefeitura',
                'latitude',
                'longitude',
                'inss_aliquota',
            ]);

        $this->assertDatabaseMissing('compdec_prefeituras', [
            'municipio_id' => $municipio->id,
        ]);
    }

    public function test_a_mensagem_de_cep_e_a_do_contrato(): void
    {
        $municipio = Municipio::factory()->create();

        $resposta = $this->comPermissao(self::PERMISSOES)
            ->from(route('cedec.prefeituras.edit', $municipio->id))
            ->put(route('cedec.prefeituras.update', $municipio->id), array_merge($this->payloadValido(), [
                'cep' => 'abcde-fgh',
            ]));

        $resposta->assertSessionHasErrors(['cep' => 'CEP deve estar no formato 00000-000 ou 00000000.']);
    }

    public function test_quem_so_tem_view_nao_consegue_atualizar(): void
    {
        $municipio = Municipio::factory()->create();

        $this->comPermissao(['cedec.prefeituras.view'])
            ->put(route('cedec.prefeituras.update', $municipio->id), $this->payloadValido())
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que ele falha**

Run: `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=CedecPrefeituraEdicaoTest`

Expected: FAIL nos dois primeiros metodos com a mensagem do Inertia `Inertia page component file [Cedec/Prefeituras/Edit] does not exist` (`ensure_pages_exist` esta ligado em `config/inertia.php`). Os metodos de update podem ja passar, porque exercitam a fase 2 — se algum falhar, o defeito e no `UpdatePrefeituraRequest` ou no `upsertPorMunicipio` da fase 2, nao neste plano.

- [ ] **Step 3: Registrar o icone do modulo (idempotente)**

Abrir `resources/js/Support/moduleIcons.js` e conferir se `MODULE_ICONS` ja tem a chave `prefeituras` (a fase 3 pode ter registrado). Se nao tiver, acrescentar a linha logo depois de `orgaos: officeBuilding,`:

```js
  prefeituras: apartment,
```

O import de `apartment` ja existe no topo do arquivo e `apartment` ja esta no catalogo `ICONS`; nada mais muda.

- [ ] **Step 4: Criar o painel de indicadores read-only**

Criar `resources/js/Components/Organisms/Cedec/IndicadoresMunicipaisPanel.vue`:

```vue
<template>
  <CollapsibleSection
    namespace="cedec"
    section-id="indicadores-municipais"
    title="Indicadores municipais"
    subtitle="Referencia IBGE/CEDEC — nao editavel nesta tela"
    :icon="ChartBarSquareIcon"
    tom="neutro"
    :expandido-por-padrao="!isMobile"
  >
    <div class="space-y-4">
      <!--
        O formulario legado misturava dado de prefeitura com indicador do
        municipio, e a CEDEC editava os dois no mesmo lugar. Aqui o bloco e de
        leitura, e a tela diz isso em vez de deixar o usuario descobrir ao
        tentar salvar.
      -->
      <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
          <LockClosedIcon class="h-3.5 w-3.5" />
          Somente leitura
        </span>
        <span class="text-xs text-slate-500 dark:text-slate-400">
          Alterar exige correcao na base de origem.
        </span>
      </div>

      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="item in itens" :key="item.rotulo" class="min-w-0">
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ item.rotulo }}
          </dt>
          <dd class="mt-0.5 break-words text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ item.valor }}
          </dd>
        </div>
      </dl>

      <p class="border-t border-slate-200 pt-3 text-xs text-slate-500 dark:border-slate-700/50 dark:text-slate-400">
        Origem do dado: <span class="font-mono">{{ indicadores.origem }}</span>
      </p>
    </div>
  </CollapsibleSection>
</template>

<script setup>
import { computed } from 'vue';
import { ChartBarSquareIcon, LockClosedIcon } from '@heroicons/vue/24/outline';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import { useMobile } from '@/Composables/useMobile';

/**
 * Indicadores municipais em modo leitura. Recebe exatamente o retorno de
 * `CedecPrefeituraService::indicadoresMunicipais()`, incluindo a chave
 * `origem`, que vai para a tela sem reescrita: quem le precisa saber de qual
 * base o numero veio para saber onde corrigir.
 */
const props = defineProps({
  indicadores: { type: Object, required: true },
});

const { isMobile } = useMobile();

const VAZIO = 'Nao informado';

function numero(valor, casas = 0) {
  if (valor === null || valor === undefined || valor === '') {
    return VAZIO;
  }

  return Number(valor).toLocaleString('pt-BR', {
    minimumFractionDigits: casas,
    maximumFractionDigits: casas,
  });
}

function texto(valor) {
  if (valor === null || valor === undefined || String(valor).trim() === '') {
    return VAZIO;
  }

  return String(valor);
}

const itens = computed(() => [
  { rotulo: 'Populacao urbana', valor: numero(props.indicadores.populacao) },
  { rotulo: 'Populacao rural', valor: numero(props.indicadores.pop_rural) },
  { rotulo: 'Area', valor: texto(props.indicadores.area) },
  { rotulo: 'Macrorregiao', valor: texto(props.indicadores.macrorregiao) },
  { rotulo: 'Territorio de desenvolvimento', valor: texto(props.indicadores.territorio_desenv) },
  { rotulo: 'Distancia de BH (km)', valor: numero(props.indicadores.distancia_bh, 1) },
  { rotulo: 'Caminhoes pipa', valor: numero(props.indicadores.qtd_pipa) },
  { rotulo: 'Latitude do municipio', valor: texto(props.indicadores.latitude) },
  { rotulo: 'Longitude do municipio', valor: texto(props.indicadores.longitude) },
]);
</script>
```

- [ ] **Step 5: Criar a pagina de edicao**

Criar `resources/js/Pages/Cedec/Prefeituras/Edit.vue`. O cartao de foto entra so na Task 3 — por enquanto a pagina tem cabecalho, formulario e indicadores:

```vue
<template>
  <Head :title="`Prefeitura — ${municipio.nome}`" />

  <div class="w-full space-y-6 pb-8">
    <PageHeader
      :title="`Prefeitura de ${municipio.nome}`"
      :description="`${municipio.uf} — codigo IBGE ${municipio.codigo_ibge}`"
      :icon-image="moduleIcon('prefeituras')"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template #actions>
        <ActionButton action="custom" :icon="ArrowLeftIcon" label="Voltar" variant="outline" @click="voltar" />
      </template>
    </PageHeader>

    <p
      v-if="!podeEditar"
      class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
    >
      Voce tem acesso de leitura a este cadastro. Editar exige a permissao cedec.prefeituras.edit.
    </p>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <form class="min-w-0 space-y-6 lg:col-span-2" @submit.prevent="salvar">
        <PrefeituraFormSections
          :form="form"
          :errors="form.errors"
          :pode-editar="podeEditar"
        />

        <FormActions
          v-if="podeEditar"
          submit-label="Salvar prefeitura"
          :loading="form.processing"
          :disabled="form.processing"
          @cancel="voltar"
          @submit="salvar"
        />
      </form>

      <div class="min-w-0 space-y-6">
        <IndicadoresMunicipaisPanel :indicadores="indicadores" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import PrefeituraFormSections from '@/Components/Organisms/Cedec/PrefeituraFormSections.vue';
import IndicadoresMunicipaisPanel from '@/Components/Organisms/Cedec/IndicadoresMunicipaisPanel.vue';
import { usePermissions } from '@/Composables/auth';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  municipio: { type: Object, required: true },
  prefeitura: { type: Object, default: null },
  indicadores: { type: Object, required: true },
});

/*
 * A rota `cedec.prefeituras.edit` e liberada por `cedec.prefeituras.view`: quem
 * so consulta chega aqui. Quem decide se pode editar e a PAGINA, e a decisao
 * desce como prop -- o mesmo organismo de campos serve a aba do Compdec, cujo
 * slug e outro.
 */
const { can } = usePermissions();
const podeEditar = can('cedec.prefeituras.edit');

const form = useForm({
  prefeito_nome: props.prefeitura?.prefeito_nome ?? '',
  prefeito_partido: props.prefeitura?.prefeito_partido ?? '',
  prefeito_telefone: props.prefeitura?.prefeito_telefone ?? '',
  prefeito_celular: props.prefeitura?.prefeito_celular ?? '',
  prefeito_email: props.prefeitura?.prefeito_email ?? '',
  email_prefeitura: props.prefeitura?.email_prefeitura ?? '',
  email_prefeitura_2: props.prefeitura?.email_prefeitura_2 ?? '',
  email_prefeitura_3: props.prefeitura?.email_prefeitura_3 ?? '',
  tel_prefeitura: props.prefeitura?.tel_prefeitura ?? '',
  tel_prefeitura_2: props.prefeitura?.tel_prefeitura_2 ?? '',
  fax_prefeitura: props.prefeitura?.fax_prefeitura ?? '',
  endereco: props.prefeitura?.endereco ?? '',
  bairro: props.prefeitura?.bairro ?? '',
  cep: props.prefeitura?.cep ?? '',
  latitude: props.prefeitura?.latitude ?? '',
  longitude: props.prefeitura?.longitude ?? '',
  inss_tem_cobranca: Boolean(props.prefeitura?.inss_tem_cobranca ?? false),
  inss_aliquota: props.prefeitura?.inss_aliquota ?? '',
  inss_lei_cobranca: props.prefeitura?.inss_lei_cobranca ?? '',
  inss_responsavel: props.prefeitura?.inss_responsavel ?? '',
});

function salvar() {
  if (! podeEditar) {
    return;
  }

  form.put(route('cedec.prefeituras.update', props.municipio.id), {
    preserveScroll: true,
  });
}

function voltar() {
  router.visit(route('cedec.prefeituras.index'));
}
</script>
```

- [ ] **Step 6: Rodar o teste e confirmar que passa**

Run: `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=CedecPrefeituraEdicaoTest`

Expected: PASS, 7 testes.

- [ ] **Step 7: Compilar o frontend**

Run (na pasta `SDC`): `npm run build`

Expected: build sem erro.

- [ ] **Step 8: Medir a pagina no navegador**

Abrir `/cedec/prefeituras/{municipio}/edit` de um municipio com prefeitura cadastrada e, com a janela em 375px e depois em 840px, rodar no console:

```js
(() => {
  const de = document.documentElement;
  const m = document.querySelector('main');
  const wrap = m.querySelector(':scope > div');
  const r = e => { const b = e.getBoundingClientRect(); return {
    left: Math.round(b.left), width: Math.round(b.width),
    top: Math.round(b.top), bottom: Math.round(b.bottom) }; };
  const kids = [...(wrap?.children || [])].slice(0, 3).map(r);
  return { excesso: de.scrollWidth - de.clientWidth,
    kids, gaps: kids.slice(1).map((k, i) => k.top - kids[i].bottom) };
})();
```

Expected: `excesso` igual a `0` nas duas larguras e todo valor em `gaps` igual a `24`.

Conferir tambem, com o tema claro e com o tema escuro (alternando pela classe `dark` na raiz, nao pela preferencia do sistema): o painel de indicadores mostra o selo "Somente leitura" e a linha "Origem do dado: cedec_municipio (espelho do legado)"; em 375px o painel comeca recolhido.

- [ ] **Step 9: Commit**

```bash
git add resources/js/Components/Organisms/Cedec/IndicadoresMunicipaisPanel.vue \
        resources/js/Pages/Cedec/Prefeituras/Edit.vue \
        resources/js/Support/moduleIcons.js
git commit -m "✨ feat(cedec): tela de edicao de prefeitura com indicadores read-only"
```

---

## Task 3: Foto do prefeito — exibicao, upload e remocao

**Files:**
- Modify: `app/Modules/Cedec/Controllers/PrefeituraController.php`
- Modify: `resources/js/Pages/Cedec/Prefeituras/Edit.vue`
- Test: `tests/Feature/Cedec/CedecPrefeituraFotoTest.php`

**Interfaces:**

- Consumes (Task 2): `Pages/Cedec/Prefeituras/Edit.vue` com as props `municipio`, `prefeitura`, `indicadores` e a constante `podeEditar`.
- Consumes (fase 2):
  - `App\Modules\Cedec\Services\CedecPrefeituraService::uploadFoto(int $municipioId, \Illuminate\Http\UploadedFile $arquivo): \Spatie\MediaLibrary\MediaCollections\Models\Media`
  - `App\Modules\Cedec\Services\CedecPrefeituraService::removerFoto(int $municipioId): bool`
  - Rotas `cedec.prefeituras.foto.upload` (POST, `can:cedec.prefeituras.edit`) e `cedec.prefeituras.foto.destroy` (DELETE, `can:cedec.prefeituras.edit`)
  - A prop Inertia `prefeitura.foto_prefeito_url`, definida no contrato secao 8 para a pagina `Cedec/Prefeituras/Edit`
- Consumes (ja no repo): `App\Modules\Compdec\Models\Prefeitura::MEDIA_FOTO_PREFEITO` (`'foto_prefeito'`), colecao `singleFile` com mimes `image/jpeg`, `image/png`, `image/webp`, conversao `thumb`; `config('compdec.upload_limits.foto_prefeito')` = 307200; `config('compdec.disk')`.
- Produces: nenhuma interface nova para fases seguintes.

---

- [ ] **Step 1: Escrever o teste da foto**

Criar `tests/Feature/Cedec/CedecPrefeituraFotoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Cedec;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Municipio;
use App\Models\User;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Foto do prefeito na tela estadual. O upload em si e o do Model (colecao
 * `foto_prefeito`, singleFile, mimes jpeg/png/webp); o que este teste cobre e
 * a porta HTTP: mime recusado, tamanho acima do limite de
 * `compdec.upload_limits.foto_prefeito`, upload feliz e remocao feliz.
 */
class CedecPrefeituraFotoTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSOES = [
        'cedec.prefeituras.view',
        'cedec.prefeituras.edit',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        Storage::fake(config('compdec.disk', 'compdec'));

        foreach (self::PERMISSOES as $permissao) {
            Permission::firstOrCreate(['name' => $permissao, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $permissoes
     */
    private function comPermissao(array $permissoes): self
    {
        $usuario = User::factory()->create();
        $usuario->givePermissionTo($permissoes);

        $this->actingAs($usuario);

        return $this;
    }

    private function prefeitura(): Prefeitura
    {
        $municipio = Municipio::factory()->create();

        return Prefeitura::factory()->create(['municipio_id' => $municipio->id]);
    }

    private function limiteEmKb(): int
    {
        return (int) (config('compdec.upload_limits.foto_prefeito', 307200) / 1024);
    }

    public function test_recusa_arquivo_com_mime_fora_da_colecao(): void
    {
        $prefeitura = $this->prefeitura();

        $this->comPermissao(self::PERMISSOES)
            ->post(route('cedec.prefeituras.foto.upload', $prefeitura->municipio_id), [
                'foto' => UploadedFile::fake()->create('dossie.pdf', 40, 'application/pdf'),
            ])
            ->assertSessionHasErrors('foto');

        $this->assertCount(0, $prefeitura->fresh()->getMedia(Prefeitura::MEDIA_FOTO_PREFEITO));
    }

    public function test_recusa_arquivo_acima_do_limite_de_tamanho(): void
    {
        $prefeitura = $this->prefeitura();

        $this->comPermissao(self::PERMISSOES)
            ->post(route('cedec.prefeituras.foto.upload', $prefeitura->municipio_id), [
                'foto' => UploadedFile::fake()->image('grande.jpg', 1200, 1200)->size($this->limiteEmKb() + 64),
            ])
            ->assertSessionHasErrors('foto');

        $this->assertCount(0, $prefeitura->fresh()->getMedia(Prefeitura::MEDIA_FOTO_PREFEITO));
    }

    public function test_upload_feliz_grava_na_colecao_foto_prefeito(): void
    {
        $prefeitura = $this->prefeitura();

        $this->comPermissao(self::PERMISSOES)
            ->post(route('cedec.prefeituras.foto.upload', $prefeitura->municipio_id), [
                'foto' => UploadedFile::fake()->image('prefeito.jpg', 400, 400)->size(80),
            ])
            ->assertRedirect();

        $midia = $prefeitura->fresh()->getMedia(Prefeitura::MEDIA_FOTO_PREFEITO);

        $this->assertCount(1, $midia);
        $this->assertSame('prefeito.jpg', $midia->first()->name);
    }

    public function test_upload_novo_substitui_o_anterior_porque_a_colecao_e_single_file(): void
    {
        $prefeitura = $this->prefeitura();

        $this->comPermissao(self::PERMISSOES);

        $this->post(route('cedec.prefeituras.foto.upload', $prefeitura->municipio_id), [
            'foto' => UploadedFile::fake()->image('antiga.jpg', 300, 300)->size(60),
        ])->assertRedirect();

        $this->post(route('cedec.prefeituras.foto.upload', $prefeitura->municipio_id), [
            'foto' => UploadedFile::fake()->image('nova.jpg', 300, 300)->size(60),
        ])->assertRedirect();

        $midia = $prefeitura->fresh()->getMedia(Prefeitura::MEDIA_FOTO_PREFEITO);

        $this->assertCount(1, $midia);
        $this->assertSame('nova.jpg', $midia->first()->name);
    }

    public function test_remocao_feliz_limpa_a_colecao(): void
    {
        $prefeitura = $this->prefeitura();

        $this->comPermissao(self::PERMISSOES);

        $this->post(route('cedec.prefeituras.foto.upload', $prefeitura->municipio_id), [
            'foto' => UploadedFile::fake()->image('prefeito.jpg', 300, 300)->size(60),
        ])->assertRedirect();

        $this->delete(route('cedec.prefeituras.foto.destroy', $prefeitura->municipio_id))
            ->assertRedirect();

        $this->assertCount(0, $prefeitura->fresh()->getMedia(Prefeitura::MEDIA_FOTO_PREFEITO));
    }

    public function test_quem_so_tem_view_nao_envia_foto(): void
    {
        $prefeitura = $this->prefeitura();

        $this->comPermissao(['cedec.prefeituras.view'])
            ->post(route('cedec.prefeituras.foto.upload', $prefeitura->municipio_id), [
                'foto' => UploadedFile::fake()->image('prefeito.jpg', 300, 300)->size(60),
            ])
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que ele falha**

Run: `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=CedecPrefeituraFotoTest`

Expected: FAIL em `test_recusa_arquivo_com_mime_fora_da_colecao` e em `test_recusa_arquivo_acima_do_limite_de_tamanho` com `Session is missing expected key [errors]` — o controller da fase 2 ainda nao valida o arquivo antes de entregar ao service.

- [ ] **Step 3: Validar mime e tamanho no controller do Cedec**

Em `app/Modules/Cedec/Controllers/PrefeituraController.php`, o metodo `uploadFoto` passa a ser exatamente:

```php
    public function uploadFoto(Request $request, Municipio $municipio): RedirectResponse
    {
        // Mesmo par de regras do PrefeituraController do Compdec: os mimes sao
        // os que a colecao `foto_prefeito` aceita e o teto vem do config, para
        // os dois donos recusarem o mesmo arquivo.
        $request->validate([
            'foto' => [
                'required',
                'file',
                'mimes:jpeg,png,webp',
                'max:' . (int) (config('compdec.upload_limits.foto_prefeito', 307200) / 1024),
            ],
        ]);

        $this->service->uploadFoto($municipio->id, $request->file('foto'));

        return back()->with('success', 'Foto do prefeito atualizada.');
    }
```

Conferir que o `use Illuminate\Http\Request;` e o `use Illuminate\Http\RedirectResponse;` ja estao no topo do arquivo; se algum faltar, acrescentar.

- [ ] **Step 4: Rodar o teste e confirmar que passa**

Run: `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=CedecPrefeituraFotoTest`

Expected: PASS, 6 testes.

- [ ] **Step 5: Recarregar o worker**

Run: `docker exec newsdc_dev_app php artisan octane:reload`

Expected: `Octane workers reloaded.` (o restart de container nao e necessario: mudou PHP, nao `.env` nem `config/`).

- [ ] **Step 6: Acrescentar o cartao de foto na pagina**

Em `resources/js/Pages/Cedec/Prefeituras/Edit.vue`, dentro da coluna lateral, ANTES do `<IndicadoresMunicipaisPanel ... />`, inserir:

```html
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/40">
          <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Foto do prefeito</h3>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            JPEG, PNG ou WEBP, ate {{ limiteFotoKb }} KB.
          </p>

          <div class="mt-4 flex flex-col items-center gap-4">
            <img
              v-if="prefeitura && prefeitura.foto_prefeito_url"
              :src="prefeitura.foto_prefeito_url"
              alt="Foto do prefeito"
              class="h-32 w-32 rounded-xl border-2 border-slate-200 object-cover dark:border-slate-700"
            />
            <div
              v-else
              class="flex h-32 w-32 items-center justify-center rounded-xl border-2 border-dashed border-slate-300 text-slate-400 dark:border-slate-700 dark:text-slate-500"
            >
              <UserCircleIcon class="h-12 w-12" />
            </div>

            <div v-if="podeEditar" class="flex flex-wrap justify-center gap-2">
              <input
                ref="entradaFoto"
                type="file"
                class="hidden"
                accept="image/jpeg,image/png,image/webp"
                @change="aoEscolherFoto"
              />
              <Button
                variant="outline"
                size="sm"
                :loading="formFoto.processing"
                @click="abrirSeletorDeFoto"
              >
                {{ prefeitura && prefeitura.foto_prefeito_url ? 'Trocar foto' : 'Enviar foto' }}
              </Button>
              <Button
                v-if="prefeitura && prefeitura.foto_prefeito_url"
                variant="danger"
                size="sm"
                :disabled="formFoto.processing"
                @click="removerFoto"
              >
                Remover
              </Button>
            </div>

            <p v-if="formFoto.errors.foto" class="text-xs text-red-500">
              {{ formFoto.errors.foto }}
            </p>
          </div>
        </section>
```

- [ ] **Step 7: Ligar o cartao de foto ao Inertia**

No `<script setup>` de `resources/js/Pages/Cedec/Prefeituras/Edit.vue`:

Acrescentar aos imports:

```js
import { computed, ref } from 'vue';
import { UserCircleIcon } from '@heroicons/vue/24/outline';
import Button from '@/Components/Atoms/Button/Button.vue';
```

E, logo depois da declaracao de `form`, acrescentar:

```js
/*
 * A foto e um envio SEPARADO do formulario, e nao um campo dele: a colecao e
 * singleFile e as rotas de foto sao proprias (POST e DELETE). Misturar os dois
 * obrigaria o update inteiro a virar multipart so por causa do arquivo.
 */
const LIMITE_FOTO_BYTES = 300 * 1024;

const limiteFotoKb = computed(() => Math.round(LIMITE_FOTO_BYTES / 1024));

const entradaFoto = ref(null);
const formFoto = useForm({ foto: null });

function abrirSeletorDeFoto() {
  entradaFoto.value?.click();
}

function aoEscolherFoto(evento) {
  const arquivo = evento.target.files?.[0] ?? null;

  // Zera o input antes de enviar: sem isso, escolher o MESMO arquivo de novo
  // depois de um erro nao dispara `change` e o botao parece morto.
  evento.target.value = '';

  if (! arquivo) {
    return;
  }

  formFoto.foto = arquivo;
  formFoto.post(route('cedec.prefeituras.foto.upload', props.municipio.id), {
    preserveScroll: true,
    forceFormData: true,
    onFinish: () => {
      formFoto.foto = null;
    },
  });
}

function removerFoto() {
  formFoto.delete(route('cedec.prefeituras.foto.destroy', props.municipio.id), {
    preserveScroll: true,
  });
}
```

- [ ] **Step 8: Compilar o frontend**

Run (na pasta `SDC`): `npm run build`

Expected: build sem erro.

- [ ] **Step 9: Conferir a foto na tela e medir de novo**

Abrir `/cedec/prefeituras/{municipio}/edit` e conferir:
- sem foto, aparece o marcador tracejado e o botao "Enviar foto";
- enviando um JPEG pequeno, a foto aparece depois do redirect;
- enviando um PDF, aparece a mensagem de erro sob o cartao e a foto nao muda;
- "Remover" volta ao marcador tracejado;
- sem `cedec.prefeituras.edit`, o bloco de botoes nao aparece.

Com a janela em 375px e depois em 840px, rodar no console:

```js
document.documentElement.scrollWidth - document.documentElement.clientWidth
```

Expected: `0` nas duas larguras.

- [ ] **Step 10: Rodar a suite inteira da fase**

Run: `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=Cedec`

Expected: PASS nos tres arquivos (`CedecAbaCompdecRefitTest`, `CedecPrefeituraEdicaoTest`, `CedecPrefeituraFotoTest`), mais o que as fases 1 a 3 ja tiverem deixado com o mesmo prefixo.

- [ ] **Step 11: Lint dos arquivos PHP tocados**

Run: `docker exec newsdc_dev_app php -l /var/www/app/Modules/Cedec/Controllers/PrefeituraController.php`

Expected: `No syntax errors detected`.

- [ ] **Step 12: Commit**

```bash
git add app/Modules/Cedec/Controllers/PrefeituraController.php \
        resources/js/Pages/Cedec/Prefeituras/Edit.vue
git commit -m "✨ feat(cedec): foto do prefeito na edicao estadual de prefeitura"
```

---

## Checklist de encerramento da fase 4

- [ ] `php -d extension=pdo_pgsql vendor/bin/phpunit --filter=Cedec` — verde.
- [ ] `npm run build` na pasta `SDC` — verde.
- [ ] `grep -rn "style scoped" resources/js/Components/Organisms/Compdec/PrefeituraForm.vue` — sem resultado.
- [ ] `grep -rn "style scoped" resources/js/Components/Organisms/Cedec/ resources/js/Pages/Cedec/` — sem resultado.
- [ ] `/cedec/prefeituras/{municipio}/edit` e `/compdec/orgaos/{id}` (aba Prefeitura) com excesso horizontal `0` em 375px e 840px.
- [ ] Tres commits na branch, nesta ordem: `♻️ refactor(cedec): ...`, `✨ feat(cedec): tela de edicao ...`, `✨ feat(cedec): foto do prefeito ...` — nenhum com trailer de co-autor.
