<template>
  <Head :title="`Prefeitura — ${municipio.nome}`" />

  <div class="w-full space-y-6 pb-8">
    <PageHeader
      :title="`Prefeitura de ${municipio.nome}`"
      :description="`${municipio.uf} — código IBGE ${municipio.codigo_ibge}`"
      :icon-image="moduleIcon('prefeituras') ?? ''"
      variant="gradient"
      :espaco-inferior="false"
    >
      <!--
        Sem botao Voltar aqui: a barra de breadcrumb do layout ja traz o dela, e o
        padrao do sistema e um so. Dois botoes iguais na mesma dobra sao ruido.
      -->
      <template #actions>
        <!--
          So o lapis, sem rotulo: o mesmo desenho da coluna de acoes da listagem e do
          Decretacoes. O significado fica no tooltip.
        -->
        <ActionButton
          v-if="podeEditar"
          action="edit"
          module="cedec"
          resource="prefeituras"
          :allowed="true"
          :show-label="false"
          tooltip-text="Editar dados da prefeitura"
          @click="editar"
        />
      </template>
    </PageHeader>

    <!--
      Municipio sem linha de prefeitura: a tela existe justamente para mostrar quem
      esta em branco, entao diz isso e oferece o caminho, em vez de exibir uma ficha
      cheia de travessoes.
    -->
    <div
      v-if="!prefeitura"
      class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center dark:border-slate-700 dark:bg-slate-900/40"
    >
      <BuildingOffice2Icon class="mx-auto h-10 w-10 text-slate-400 dark:text-slate-500" />
      <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">
        Nenhum dado de prefeitura cadastrado
      </p>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
        {{ podeEditar
          ? 'Use Editar para preencher os contatos deste município.'
          : 'Ninguém preencheu os contatos deste município ainda.' }}
      </p>
    </div>

    <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="min-w-0 space-y-6 lg:col-span-2">
        <CollapsibleSection
          namespace="cedec-show"
          section-id="show-prefeito"
          title="Prefeito"
          subtitle="Nome, partido e contato pessoal"
          :icon="UserIcon"
          tom="info"
        >
          <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <CampoDeLeitura rotulo="Nome" :valor="prefeitura.prefeito_nome" />
            <CampoDeLeitura rotulo="Partido" :valor="prefeitura.prefeito_partido" />
            <CampoDeLeitura rotulo="Telefone" :valor="prefeitura.prefeito_telefone" tipo="tel" />
            <CampoDeLeitura rotulo="Celular" :valor="prefeitura.prefeito_celular" tipo="tel" />
            <CampoDeLeitura rotulo="E-mail" :valor="prefeitura.prefeito_email" tipo="email" class="sm:col-span-2" />
          </dl>
        </CollapsibleSection>

        <CollapsibleSection
          namespace="cedec-show"
          section-id="show-contatos"
          title="Contatos institucionais"
          subtitle="Da prefeitura, não do prefeito"
          :icon="EnvelopeIcon"
          tom="success"
        >
          <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <CampoDeLeitura rotulo="E-mail institucional" :valor="prefeitura.email_prefeitura" tipo="email" class="sm:col-span-2" />
            <CampoDeLeitura rotulo="E-mail 2" :valor="prefeitura.email_prefeitura_2" tipo="email" />
            <CampoDeLeitura rotulo="E-mail 3" :valor="prefeitura.email_prefeitura_3" tipo="email" />
            <CampoDeLeitura rotulo="Telefone" :valor="prefeitura.tel_prefeitura" tipo="tel" />
            <CampoDeLeitura rotulo="Telefone 2" :valor="prefeitura.tel_prefeitura_2" tipo="tel" />
            <CampoDeLeitura rotulo="Fax" :valor="prefeitura.fax_prefeitura" tipo="tel" />
          </dl>
        </CollapsibleSection>

        <CollapsibleSection
          namespace="cedec-show"
          section-id="show-endereco"
          title="Endereço"
          subtitle="Sede da prefeitura"
          :icon="MapPinIcon"
          tom="info"
        >
          <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <CampoDeLeitura rotulo="Logradouro" :valor="prefeitura.endereco" class="sm:col-span-2" />
            <CampoDeLeitura rotulo="Bairro" :valor="prefeitura.bairro" />
            <CampoDeLeitura rotulo="CEP" :valor="prefeitura.cep" />
            <CampoDeLeitura rotulo="Latitude" :valor="prefeitura.latitude" />
            <CampoDeLeitura rotulo="Longitude" :valor="prefeitura.longitude" />
          </dl>
        </CollapsibleSection>

        <CollapsibleSection
          v-if="prefeitura.inss_tem_cobranca"
          namespace="cedec-show"
          section-id="show-inss"
          title="INSS"
          subtitle="Cobrança sobre serviços prestados"
          :icon="BanknotesIcon"
          tom="warning"
        >
          <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <CampoDeLeitura rotulo="Alíquota (%)" :valor="prefeitura.inss_aliquota" />
            <CampoDeLeitura rotulo="Lei de cobrança" :valor="prefeitura.inss_lei_cobranca" />
            <CampoDeLeitura rotulo="Responsável" :valor="prefeitura.inss_responsavel" class="sm:col-span-2" />
          </dl>
        </CollapsibleSection>
      </div>

      <div class="min-w-0 space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-4 text-center shadow-sm dark:border-slate-700/50 dark:bg-slate-900/40">
          <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Foto do prefeito</h3>
          <img
            v-if="prefeitura.foto_prefeito_url"
            :src="prefeitura.foto_prefeito_url"
            alt="Foto do prefeito"
            class="mx-auto mt-4 h-32 w-32 rounded-xl border-2 border-slate-200 object-cover dark:border-slate-700"
          />
          <div
            v-else
            class="mx-auto mt-4 flex h-32 w-32 items-center justify-center rounded-xl border-2 border-dashed border-slate-300 text-slate-400 dark:border-slate-700 dark:text-slate-500"
          >
            <UserCircleIcon class="h-12 w-12" />
          </div>
        </section>

        <!--
          Vinculo com o Compdec: as duas telas editam a MESMA linha de
          compdec_prefeituras. Sem isto, duas pessoas alteram o mesmo dado por portas
          diferentes sem saber uma da outra.
        -->
        <section
          v-if="orgao"
          class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 dark:border-cyan-500/30 dark:bg-cyan-500/10"
        >
          <h3 class="flex items-center gap-2 text-sm font-bold text-cyan-900 dark:text-cyan-200">
            <LinkIcon class="h-4 w-4 shrink-0" />
            Também editável pelo COMPDEC
          </h3>
          <p class="mt-1 text-xs text-cyan-800 dark:text-cyan-300">
            O órgão <strong>{{ orgao.nome }}</strong> edita esta mesma prefeitura pela
            aba Prefeitura do módulo Órgãos.
          </p>
          <a
            :href="route('compdec.show', orgao.id)"
            class="mt-3 inline-flex h-9 items-center rounded-lg border border-cyan-300 px-3 text-xs font-semibold text-cyan-800 hover:bg-cyan-100 dark:border-cyan-500/40 dark:text-cyan-200 dark:hover:bg-cyan-500/20"
          >
            Abrir o órgão
          </a>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/40">
          <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Origem do dado</h3>
          <dl class="mt-3 space-y-2">
            <div>
              <dt class="text-xs text-slate-500 dark:text-slate-400">Procedência</dt>
              <dd class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                {{ rastreabilidade.veio_do_legado ? 'Carga do legado gestaocedec' : 'Cadastrado no sistema' }}
              </dd>
            </div>
            <div v-if="rastreabilidade.legacy_id">
              <dt class="text-xs text-slate-500 dark:text-slate-400">Registro de origem</dt>
              <dd class="font-mono text-sm text-slate-700 dark:text-slate-200">
                id_municipio {{ rastreabilidade.legacy_id }}
              </dd>
            </div>
            <div v-if="rastreabilidade.ultima_acao_etl">
              <dt class="text-xs text-slate-500 dark:text-slate-400">Última carga</dt>
              <dd class="text-sm text-slate-700 dark:text-slate-200">
                {{ rastreabilidade.ultima_acao_etl === 'inserted' ? 'Inserida' : 'Atualizada' }}
                em {{ formatarData(rastreabilidade.ultima_carga_em) }}
              </dd>
            </div>
            <div v-if="rastreabilidade.atualizado_em">
              <dt class="text-xs text-slate-500 dark:text-slate-400">Última alteração</dt>
              <dd class="text-sm text-slate-700 dark:text-slate-200">
                {{ formatarData(rastreabilidade.atualizado_em) }}
              </dd>
            </div>
          </dl>
        </section>

        <IndicadoresMunicipaisPanel :indicadores="indicadores" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import {
  BanknotesIcon,
  BuildingOffice2Icon,
  EnvelopeIcon,
  LinkIcon,
  MapPinIcon,
  UserCircleIcon,
  UserIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import CampoDeLeitura from '@/Components/Molecules/Cedec/CampoDeLeitura.vue';
import IndicadoresMunicipaisPanel from '@/Components/Organisms/Cedec/IndicadoresMunicipaisPanel.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  municipio: { type: Object, required: true },
  prefeitura: { type: Object, default: null },
  indicadores: { type: Object, required: true },
  rastreabilidade: { type: Object, required: true },
  orgao: { type: Object, default: null },
  podeEditar: { type: Boolean, default: false },
});

function formatarData(valor) {
  if (!valor) return '—';

  const data = new Date(valor);

  return Number.isNaN(data.getTime())
    ? String(valor)
    : data.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

function editar() {
  router.visit(route('cedec.prefeituras.edit', props.municipio.id));
}

</script>
