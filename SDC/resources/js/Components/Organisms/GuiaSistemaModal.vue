<script setup>
import {
  BookOpenIcon,
  XMarkIcon,
  HomeIcon,
  DocumentTextIcon,
  BoltIcon,
  ClockIcon,
  ScaleIcon,
  HeartIcon,
  BuildingOffice2Icon,
  TruckIcon,
  CubeIcon,
  BeakerIcon,
  AcademicCapIcon,
  ShieldCheckIcon,
  CloudIcon,
  ClipboardDocumentCheckIcon,
  LockClosedIcon,
  ArchiveBoxIcon,
  ListBulletIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  show: Boolean,
});

const emit = defineEmits(['close']);

const close = () => emit('close');

const modulos = [
  {
    nome: 'Visao Geral',
    icon: HomeIcon,
    accent: 'from-blue-500 to-cyan-500',
    descricao: 'Painel gerencial consolidado: KPIs do exercicio, graficos de tendencia e movimentacoes em tempo real.',
    perfil: 'Todos os perfis',
    uso: 'Diario',
  },
  {
    nome: 'RAT',
    icon: DocumentTextIcon,
    accent: 'from-cyan-500 to-blue-500',
    descricao: 'Relatorio de Assistencia Tecnica. Documenta vistorias, pareceres e atendimentos de campo da Defesa Civil.',
    perfil: 'Gestor / Coordenador',
    uso: 'Diario',
  },
  {
    nome: 'PAE',
    icon: BoltIcon,
    accent: 'from-amber-500 to-orange-500',
    descricao: 'Plano de Acao de Emergencia. Cadastro e analise de empreendimentos e protocolos de barragens.',
    perfil: 'Analista PAE',
    uso: 'Frequente',
  },
  {
    nome: 'Plantao Diario',
    icon: ClockIcon,
    accent: 'from-violet-500 to-purple-500',
    descricao: 'Registro de turnos, eventos meteorologicos do dia e acionamentos de equipes operacionais.',
    perfil: 'Plantonista',
    uso: 'Diario',
  },
  {
    nome: 'Decretacoes',
    icon: ScaleIcon,
    accent: 'from-emerald-500 to-green-500',
    descricao: 'Processos de reconhecimento de Situacao de Emergencia (SE) e Estado de Calamidade Publica (ECP) por municipios.',
    perfil: 'Coordenador Estadual',
    uso: 'Frequente',
  },
  {
    nome: 'Ajuda Humanitaria',
    icon: HeartIcon,
    accent: 'from-rose-500 to-red-500',
    descricao: 'Cadastro e acompanhamento de beneficiarios e familias afetadas por desastres.',
    perfil: 'Assistente Social',
    uso: 'Frequente',
  },
  {
    nome: 'Orgaos / COMPDEC',
    icon: BuildingOffice2Icon,
    accent: 'from-slate-500 to-slate-600',
    descricao: 'Cadastro de Coordenadorias Municipais de Defesa Civil (COMPDEC) e contatos institucionais.',
    perfil: 'Administrador',
    uso: 'Eventual',
  },
  {
    nome: 'TDAP',
    icon: TruckIcon,
    accent: 'from-indigo-500 to-blue-500',
    descricao: 'Transporte de Doacoes e Ajuda Permanente: prestadores, caminhoes, atas, lotes, cronogramas, vistorias e workflow.',
    perfil: 'Operador TDAP',
    uso: 'Diario',
  },
  {
    nome: 'Estoque',
    icon: CubeIcon,
    accent: 'from-orange-500 to-amber-500',
    descricao: 'Gestao de produtos, lotes, kits e movimentacoes do almoxarifado da Defesa Civil.',
    perfil: 'Almoxarife',
    uso: 'Diario',
  },
  {
    nome: 'Cisternas',
    icon: BeakerIcon,
    accent: 'from-cyan-500 to-teal-500',
    descricao: 'Cadastro e fiscalizacao de cisternas e reservatorios de agua em regioes semiaridas.',
    perfil: 'Tecnico de Campo',
    uso: 'Frequente',
  },
  {
    nome: 'Treinamento',
    icon: AcademicCapIcon,
    accent: 'from-violet-500 to-fuchsia-500',
    descricao: 'Cursos, capacitacoes e historico de formacao dos agentes da Defesa Civil municipal e estadual.',
    perfil: 'RH / Instrutor',
    uso: 'Eventual',
  },
  {
    nome: 'Plano de Contingencia',
    icon: ShieldCheckIcon,
    accent: 'from-blue-500 to-indigo-500',
    descricao: 'Elaboracao e revisao dos PLANCONs municipais por COBRADE e situacao operacional.',
    perfil: 'Planejador',
    uso: 'Frequente',
  },
  {
    nome: 'Meteorologia',
    icon: CloudIcon,
    accent: 'from-sky-500 to-blue-500',
    descricao: 'Dados do INMET e alertas hidrometeorologicos consumidos em tempo real.',
    perfil: 'Todos os perfis',
    uso: 'Diario',
  },
  {
    nome: 'Vistoria',
    icon: ClipboardDocumentCheckIcon,
    accent: 'from-emerald-500 to-teal-500',
    descricao: 'Registro fotografico e tecnico de vistorias em areas de risco e ocorrencias.',
    perfil: 'Tecnico de Campo',
    uso: 'Frequente',
  },
  {
    nome: 'Permissionamento',
    icon: LockClosedIcon,
    accent: 'from-red-500 to-rose-500',
    descricao: 'Gestao de usuarios, perfis (roles) e permissoes granulares (RBAC) do sistema.',
    perfil: 'Administrador',
    uso: 'Eventual',
  },
  {
    nome: 'Inventario',
    icon: ArchiveBoxIcon,
    accent: 'from-amber-500 to-yellow-500',
    descricao: 'Equipamentos e emprestimos do patrimonio operacional da Defesa Civil estadual.',
    perfil: 'Administrador',
    uso: 'Eventual',
  },
  {
    nome: 'Logs',
    icon: ListBulletIcon,
    accent: 'from-slate-500 to-zinc-600',
    descricao: 'Trilha de auditoria de acoes criticas, conforme exigencia de TCE-MG e CGE.',
    perfil: 'Auditor',
    uso: 'Eventual',
  },
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
    <div
      v-if="show"
      class="fixed inset-0 z-[10050] flex items-center justify-center p-4 sm:p-6 md:p-10"
    >
      <div
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
        @click="close"
      ></div>

      <div
        class="relative bg-white dark:bg-slate-900 w-full max-w-5xl max-h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden transform transition-all"
      >
        <div class="bg-gradient-to-r from-emerald-800 to-emerald-700 p-6 text-white flex justify-between items-center shrink-0">
          <div class="flex items-center gap-4">
            <div class="bg-white/20 p-2 rounded-lg">
              <BookOpenIcon class="w-8 h-8" />
            </div>
            <div>
              <h2 class="text-xl md:text-2xl font-bold leading-tight">Guia do Sistema</h2>
              <p class="text-emerald-100 text-xs md:text-sm font-medium">
                Manual de Modulos - SDC Minas Gerais
              </p>
            </div>
          </div>
          <button
            @click="close"
            class="p-2 hover:bg-white/10 rounded-full transition-colors"
            title="Fechar"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 md:p-8 bg-slate-50 dark:bg-slate-950 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700">
          <div class="text-center mb-6 pb-6 border-b border-slate-200 dark:border-slate-700">
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-300 leading-relaxed max-w-3xl mx-auto">
              Conheca cada modulo do SDC, quem pode acessar e a frequencia tipica de uso.
              Use este guia como referencia rapida sempre que precisar.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="modulo in modulos"
              :key="modulo.nome"
              class="group p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-emerald-400 dark:hover:border-emerald-500 hover:shadow-md transition-all"
            >
              <div class="flex items-start gap-3">
                <div
                  class="p-2.5 rounded-lg bg-gradient-to-br shrink-0 text-white shadow-sm"
                  :class="modulo.accent"
                >
                  <component :is="modulo.icon" class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="text-sm md:text-base font-bold text-slate-900 dark:text-white mb-1">
                    {{ modulo.nome }}
                  </h3>
                  <p class="text-xs md:text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-3">
                    {{ modulo.descricao }}
                  </p>
                  <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                      {{ modulo.perfil }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                      {{ modulo.uso }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-8 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50">
            <p class="text-sm text-emerald-900 dark:text-emerald-200 text-center">
              <strong>Dica:</strong> Use <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-800 border border-emerald-300 dark:border-emerald-700 rounded text-[11px] font-mono">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-800 border border-emerald-300 dark:border-emerald-700 rounded text-[11px] font-mono">K</kbd> a qualquer momento para abrir a busca unificada e pular para qualquer modulo.
            </p>
          </div>
        </div>

        <div class="border-t border-slate-200 dark:border-slate-800 p-4 bg-white dark:bg-slate-900 flex justify-end">
          <button
            @click="close"
            class="px-5 py-2 text-sm font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white transition-colors shadow-sm"
          >
            Fechar Guia
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
