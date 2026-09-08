<script setup>
/**
 * Relatorios de contato da CEDEC.
 *
 * Substitui relbusca.php (tres botoes), rel_email.php (tabela) e rel_email_ca.php
 * (blocos de 50) do legado gestaocedec. A aba de telefones e nova: no legado era um
 * href="#" que nunca foi implementado.
 *
 * PAGINA: orquestra. Nao copia texto -- isso e do ContatoBlocosOutlook -- e nao decide
 * layout de tabela, que e do ContatoTabela.
 */
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { EnvelopeIcon, PhoneIcon, BuildingOffice2Icon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import ModuleTabs from '@/Components/Molecules/Navigation/ModuleTabs.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import ContatoBlocosOutlook from '@/Components/Organisms/Cedec/ContatoBlocosOutlook.vue';
import ContatoTabela from '@/Components/Organisms/Cedec/ContatoTabela.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/Composables/data/useExport';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  aba: { type: String, default: 'emails' },
  emails: { type: Array, default: () => [] },
  telefones: { type: Array, default: () => [] },
  blocos: { type: Array, default: () => [] },
  tamanho_bloco: { type: Number, default: 50 },
  totais: {
    type: Object,
    default: () => ({ emails_preenchidos: 0, telefones_preenchidos: 0, municipios: 0 }),
  },
});

defineOptions({ layout: AuthenticatedLayout });

const abaAtiva = ref(props.aba);

// A prop manda: depois da visita ao servidor a aba tem de refletir o que veio.
watch(() => props.aba, (valor) => { abaAtiva.value = valor; });

const abas = [
  { id: 'emails', label: 'E-mails', icon: EnvelopeIcon },
  { id: 'telefones', label: 'Telefones', icon: PhoneIcon },
];

const COLUNAS_EMAIL = [
  { key: 'email_prefeitura', label: 'E-mail institucional' },
  { key: 'email_prefeitura_2', label: 'E-mail 2' },
  { key: 'email_prefeitura_3', label: 'E-mail 3' },
];

const COLUNAS_TELEFONE = [
  { key: 'tel_prefeitura', label: 'Telefone' },
  { key: 'tel_prefeitura_2', label: 'Telefone 2' },
  { key: 'fax_prefeitura', label: 'Fax' },
  { key: 'prefeito_telefone', label: 'Telefone do prefeito' },
  { key: 'prefeito_celular', label: 'Celular do prefeito' },
];

const ehTelefones = computed(() => abaAtiva.value === 'telefones');
const colunas = computed(() => (ehTelefones.value ? COLUNAS_TELEFONE : COLUNAS_EMAIL));
const linhas = computed(() => (ehTelefones.value ? props.telefones : props.emails));

// Os blocos vem calculados do backend: recalcular no cliente duplicaria a regra de
// blocagem, que e justamente o que o service existe para centralizar.
const trocarAba = (id) => {
  if (id === abaAtiva.value) return;

  router.get(
    route('cedec.contatos.index'),
    { aba: id },
    { preserveScroll: true, replace: true },
  );
};

// O ExportCsvModal manda type/data_inicio/data_fim/all; o controller de contatos
// ignora tudo isso e le so `aba`. Relatorio de contato nao tem recorte por data.
const { showExportModal, openExportModal, closeExportModal, handleExport } =
  useExport('cedec.contatos.export');

const exportar = (params) => handleExport(params, { aba: abaAtiva.value });
</script>

<template>
  <Head title="Contatos das Prefeituras" />

  <div class="w-full pb-8">
    <PageHeader
      title="Contatos das Prefeituras"
      description="E-mails e telefones institucionais das prefeituras de Minas Gerais, prontos para envio em blocos."
      :icon="BuildingOffice2Icon"
      :icon-image="moduleIcon('prefeituras') ?? ''"
      variant="gradient"
    >
      <template #actions>
        <ActionButton
          module="cedec"
          resource="prefeituras"
          action="export"
          label="Exportar CSV"
          @click="openExportModal"
        />
      </template>
    </PageHeader>

    <StatCardsGrid :colunas="3">
      <StatCard
        title="Municípios"
        :value="totais.municipios"
        :icon="BuildingOffice2Icon"
        variant="info"
      />
      <StatCard
        title="E-mails preenchidos"
        :value="totais.emails_preenchidos"
        :icon="EnvelopeIcon"
        variant="success"
      />
      <StatCard
        title="Telefones preenchidos"
        :value="totais.telefones_preenchidos"
        :icon="PhoneIcon"
        variant="warning"
      />
    </StatCardsGrid>

    <ModuleTabs :tabs="abas" :active-tab="abaAtiva" @tab-change="trocarAba">
      <div class="space-y-6">
        <CollapsibleSection
          namespace="cedec"
          :section-id="`blocos-${abaAtiva}`"
          title="Blocos para envio"
          subtitle="Cada parte cabe em um envio do Outlook da Cidade Administrativa"
          :icon="ehTelefones ? PhoneIcon : EnvelopeIcon"
          tom="success"
        >
          <ContatoBlocosOutlook :blocos="blocos" :tamanho-bloco="tamanho_bloco" />
        </CollapsibleSection>

        <CollapsibleSection
          namespace="cedec"
          :section-id="`tabela-${abaAtiva}`"
          title="Lista por município"
          :subtitle="`${linhas.length} municípios`"
          :icon="BuildingOffice2Icon"
          tom="info"
        >
          <ContatoTabela
            :colunas="colunas"
            :linhas="linhas"
            vazio-titulo="Nenhum município encontrado"
            vazio-ajuda="A carga de prefeituras ainda não foi executada."
          />
        </CollapsibleSection>
      </div>
    </ModuleTabs>

    <ExportCsvModal
      :show="showExportModal"
      :module-name="ehTelefones ? 'Telefones das Prefeituras' : 'E-mails das Prefeituras'"
      @close="closeExportModal"
      @export="exportar"
    />
  </div>
</template>
