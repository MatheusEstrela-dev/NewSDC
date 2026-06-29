<script setup>
import {
  DocumentTextIcon,
  InformationCircleIcon,
  ListBulletIcon,
  PaperClipIcon,
  PaperAirplaneIcon,
  CheckCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  show: { type: Boolean, default: false },
  municipio: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close']);
const close = () => emit('close');

const TERMO_URL = '/docs/pmda/termo-compromisso-2026.pdf';

const etapas = [
  'Início (motivo do pedido)',
  'ISS / Prefeitura',
  'COMPDEC (coordenador e equipe)',
  'Ponto de Captação',
  'Locais de Distribuição',
  'Ações de Resposta',
  'Anexos e envio',
];
</script>

<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="show" class="fixed inset-0 z-[10050] flex items-center justify-center p-4 sm:p-6 md:p-10">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

      <!-- Container -->
      <div class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl transition-all dark:bg-slate-900">
        <!-- Header -->
        <div class="flex shrink-0 items-center justify-between bg-blue-700 p-6 text-white dark:bg-blue-900">
          <div class="flex items-center gap-4">
            <div class="rounded-lg bg-white/20 p-2">
              <DocumentTextIcon class="h-8 w-8" />
            </div>
            <div>
              <h2 class="text-xl font-bold leading-tight md:text-2xl">Como preencher o PMDA</h2>
              <p class="text-xs font-medium text-blue-200 md:text-sm">
                Novo PMDA — {{ municipio.nome }}<span v-if="municipio.uf"> / {{ municipio.uf }}</span>
              </p>
            </div>
          </div>
          <button class="rounded-full p-2 transition-colors hover:bg-white/10" title="Fechar" @click="close">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Conteúdo -->
        <div class="flex-1 overflow-y-auto bg-slate-50 p-6 dark:bg-slate-950 md:p-10">
          <div class="space-y-8">
            <!-- 1. O que é -->
            <div class="group">
              <div class="mb-3 flex items-center gap-3 border-b border-slate-200 pb-2 dark:border-slate-800">
                <div class="rounded-lg bg-blue-50 p-2 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                  <InformationCircleIcon class="h-5 w-5" />
                </div>
                <h3 class="text-lg font-bold uppercase tracking-tight text-slate-800 dark:text-white">1. O que é</h3>
              </div>
              <p class="pl-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300 md:text-base">
                O <strong>Plano Municipal de Defesa Agropecuária (PMDA)</strong> formaliza o pedido de apoio do
                município ao Estado para enfrentamento da estiagem/seca — especialmente a distribuição de água por
                caminhões-pipa às comunidades afetadas.
              </p>
            </div>

            <!-- 2. Passo a passo -->
            <div class="group">
              <div class="mb-3 flex items-center gap-3 border-b border-slate-200 pb-2 dark:border-slate-800">
                <div class="rounded-lg bg-blue-50 p-2 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                  <ListBulletIcon class="h-5 w-5" />
                </div>
                <h3 class="text-lg font-bold uppercase tracking-tight text-slate-800 dark:text-white">2. Passo a passo (7 etapas)</h3>
              </div>
              <div class="pl-2">
                <ol class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                  <li
                    v-for="(etapa, i) in etapas"
                    :key="i"
                    class="flex items-center gap-2 rounded border border-slate-200 bg-white p-2.5 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                  >
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{{ i + 1 }}</span>
                    {{ etapa }}
                  </li>
                </ol>
                <p class="mt-3 text-sm italic text-slate-500 dark:text-slate-400">
                  O preenchimento é salvo a cada etapa — você pode sair e retomar depois.
                </p>
              </div>
            </div>

            <!-- 3. Documentos obrigatórios -->
            <div class="group">
              <div class="mb-3 flex items-center gap-3 border-b border-slate-200 pb-2 dark:border-slate-800">
                <div class="rounded-lg bg-blue-50 p-2 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                  <PaperClipIcon class="h-5 w-5" />
                </div>
                <h3 class="text-lg font-bold uppercase tracking-tight text-slate-800 dark:text-white">3. Documentos obrigatórios</h3>
              </div>
              <div class="space-y-3 pl-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300 md:text-base">
                <p>
                  <strong>Termo de Compromisso</strong> — baixe o modelo, imprima, colha as assinaturas do
                  <strong>Prefeito</strong> e do <strong>Coordenador da COMPDEC</strong> e anexe digitalizado (PDF) na etapa <em>Anexos</em>.
                </p>
                <p>
                  <strong>Ofício de Solicitação</strong> — em papel timbrado da Prefeitura, justificando o pedido, também anexado em PDF (máx. 5MB cada).
                </p>
                <a :href="TERMO_URL" target="_blank" download
                   class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                  Baixar Termo de Compromisso (PDF)
                </a>
              </div>
            </div>

            <!-- 4. Envio -->
            <div class="group">
              <div class="mb-3 flex items-center gap-3 border-b border-slate-200 pb-2 dark:border-slate-800">
                <div class="rounded-lg bg-blue-50 p-2 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                  <PaperAirplaneIcon class="h-5 w-5" />
                </div>
                <h3 class="text-lg font-bold uppercase tracking-tight text-slate-800 dark:text-white">4. Envio e análise</h3>
              </div>
              <p class="pl-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300 md:text-base">
                Concluídas as etapas e anexados os documentos, clique em <strong>"Enviar Protocolo PMDA"</strong>.
                O plano segue para análise da <strong>CEDEC-MG</strong> e passa ao status <em>Em Análise</em>.
              </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="shrink-0 border-t border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
          <div class="mb-4 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
            <CheckCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" />
            <p class="text-sm leading-relaxed text-blue-800 dark:text-blue-200">
              O envio só é concluído com o <strong>Termo de Compromisso assinado</strong> e o <strong>Ofício</strong> anexados. Tenha os documentos em mãos antes de finalizar.
            </p>
          </div>
          <div class="flex justify-end">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
              @click="close"
            >
              Entendi, começar
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>
