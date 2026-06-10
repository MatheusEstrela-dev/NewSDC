<template>
  <Head :title="`TDAP — Cronograma ${c.numero}`" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      :title="`Cronograma ${c.numero}`"
      :description="descricao"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link :href="route('tdap.cronogramas.index')">
          <SecondaryButton>Voltar</SecondaryButton>
        </Link>
        <Link v-if="canEdit && c.estado === 'rascunho'" :href="route('tdap.cronogramas.edit', c.id)">
          <PrimaryButton>Editar</PrimaryButton>
        </Link>
        <PrimaryButton v-if="canAtivar && c.estado === 'rascunho'" :disabled="!podeAtivar || ativando" @click="ativar">
          {{ ativando ? 'Ativando...' : 'Ativar' }}
        </PrimaryButton>
        <DangerButton v-if="canAtivar && c.estado === 'ativo'" @click="encerrar">
          Encerrar
        </DangerButton>
        <DangerButton v-if="canDelete && c.estado === 'rascunho'" @click="excluir">
          Excluir
        </DangerButton>
      </template>
    </TdapPageHeader>

    <div v-if="c.estado === 'rascunho' && !podeAtivar && motivoBloqueio" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-500/30 rounded-md p-4 text-sm text-amber-800 dark:text-amber-200">
      <strong>Bloqueio de ativação:</strong> {{ motivoBloqueio }}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Dados do Cronograma</h3>
          <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div><dt class="text-slate-500">Número</dt><dd class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ c.numero }}</dd></div>
            <div><dt class="text-slate-500">Empenho</dt><dd class="text-slate-900 dark:text-slate-100">{{ c.empenho || '—' }}</dd></div>
            <div><dt class="text-slate-500">Nota de empenho</dt><dd class="text-slate-900 dark:text-slate-100">{{ c.nota_empenho || '—' }}</dd></div>
            <div><dt class="text-slate-500">Vigência</dt><dd>{{ fmtDate(c.dt_inicio) }} — {{ fmtDate(c.dt_final) }}</dd></div>
            <div v-if="c.dt_inicio_prorrogacao"><dt class="text-slate-500">Prorrogação</dt><dd>{{ fmtDate(c.dt_inicio_prorrogacao) }} — {{ fmtDate(c.dt_final_prorrogacao) }}</dd></div>
            <div><dt class="text-slate-500">Volume contratado</dt><dd class="font-mono text-blue-600 font-semibold">{{ Number(c.volume_contratado_m3).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }} m³</dd></div>
            <div><dt class="text-slate-500">Consumo diário</dt><dd class="font-mono">{{ Number(c.consumo_diario).toFixed(2) }} L</dd></div>
            <div><dt class="text-slate-500">Dias</dt><dd>{{ c.dias }}</dd></div>
            <div><dt class="text-slate-500">Fator (m³)</dt><dd>{{ Number(c.fator).toFixed(2) }}<span v-if="c.usar_fator_manual" class="ml-1 text-xs text-amber-600">(manual)</span></dd></div>
            <div v-if="c.ponto_captacao"><dt class="text-slate-500">Ponto de captação</dt><dd>{{ c.ponto_captacao.nome }} <span class="text-xs text-slate-400">({{ c.ponto_captacao.tipo_nome }})</span></dd></div>
          </dl>
        </div>

        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Vínculos</h3>
          <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <dt class="text-slate-500">Ata</dt>
              <dd v-if="c.ata">
                <Link :href="route('tdap.atas.show', c.ata.id)" class="font-mono text-blue-600 hover:text-blue-800">{{ c.ata.numero }}</Link>
              </dd>
            </div>
            <div>
              <dt class="text-slate-500">Lote</dt>
              <dd v-if="c.lote"><span class="font-mono">{{ c.lote.numero }}</span><span v-if="c.lote.nome" class="text-xs text-slate-500"> — {{ c.lote.nome }}</span></dd>
            </div>
            <div>
              <dt class="text-slate-500">Município</dt>
              <dd v-if="c.municipio">{{ c.municipio.nome }}<span v-if="c.municipio.uf">/{{ c.municipio.uf }}</span></dd>
            </div>
            <div>
              <dt class="text-slate-500">Prestador</dt>
              <dd v-if="c.prestador">
                <Link :href="route('tdap.prestadores.show', c.prestador.id)" class="text-blue-600 hover:text-blue-800">{{ c.prestador.nome }}</Link>
                <p class="text-xs text-slate-500 font-mono">{{ c.prestador.cnpj }}</p>
              </dd>
            </div>
          </dl>
        </div>

        <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/40 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Caminhões alocados ({{ c.caminhoes_count }})</h3>
            <PrimaryButton v-if="canAlocarCaminhao && c.estado !== 'encerrado'" size="sm" @click="abrirAlocar">
              + Alocar Caminhão
            </PrimaryButton>
          </div>
          <table v-if="c.caminhoes && c.caminhoes.length > 0" class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/40">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Placa</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Veículo</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Capacidade</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Previsto (m³)</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Entregue (m³)</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">%</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="cc in c.caminhoes" :key="cc.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                <td class="px-4 py-3 font-mono font-semibold">{{ cc.placa }}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ cc.marca_modelo || '—' }}</td>
                <td class="px-4 py-3 text-right font-mono">{{ Number(cc.capacidade_m3).toFixed(2) }}</td>
                <td class="px-4 py-3 text-right font-mono">{{ Number(cc.agua_prevista).toFixed(2) }}</td>
                <td class="px-4 py-3 text-right font-mono">{{ Number(cc.agua_entregue).toFixed(2) }}</td>
                <td class="px-4 py-3 text-right font-mono">{{ cc.percentual }}%</td>
                <td class="px-4 py-3 text-right space-x-2">
                  <button v-if="c.estado === 'ativo'" @click="abrirRegistrarViagem(cc)" class="text-blue-600 hover:text-blue-800 text-sm">
                    + Viagem
                  </button>
                  <button v-if="canAlocarCaminhao && c.estado !== 'encerrado'" @click="removerCaminhao(cc)" class="text-red-600 hover:text-red-800 text-sm">
                    Remover
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="px-6 py-8 text-center text-slate-400">
            Nenhum caminhão alocado. Aloque pelo menos 1 para ativar o cronograma.
          </p>
        </div>

        <div v-if="c.justificativa" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Justificativa</h3>
          <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ c.justificativa }}</p>
        </div>

        <div v-if="c.observacao" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Observação</h3>
          <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ c.observacao }}</p>
        </div>
      </div>

      <aside class="space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <p class="text-sm text-slate-500 mb-2">Estado</p>
          <EstadoBadge :estado="c.estado" />
          <p v-if="c.ativado_em" class="text-xs text-slate-500 mt-3">Ativado em {{ fmtDateTime(c.ativado_em) }}</p>
          <p v-if="c.encerrado_em" class="text-xs text-slate-500">Encerrado em {{ fmtDateTime(c.encerrado_em) }}</p>
        </div>
        <div v-if="c.user" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <p class="text-sm text-slate-500">Criado por</p>
          <p class="text-slate-900 dark:text-slate-100 font-medium mt-1">{{ c.user.name }}</p>
        </div>
      </aside>
    </div>

    <AlocarCaminhaoModal
      :show="showAlocar"
      :cronograma-id="c.id"
      :cronograma-numero="c.numero"
      :caminhoes="caminhoesPrestador"
      :ja-alocados="(c.caminhoes || []).map(cc => cc.caminhao_id)"
      @close="showAlocar = false"
    />

    <RegistrarViagemModal
      :show="showViagem"
      :crono-caminhao-id="cronoCaminhaoSelecionado?.id ?? 0"
      :placa="cronoCaminhaoSelecionado?.placa ?? ''"
      @close="showViagem = false"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import EstadoBadge from '@/Components/Organisms/Tdap/EstadoBadge.vue';
import AlocarCaminhaoModal from '@/Components/Organisms/Tdap/AlocarCaminhaoModal.vue';
import RegistrarViagemModal from '@/Components/Organisms/Tdap/RegistrarViagemModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  cronograma:        { type: Object, required: true },
  podeAtivar:        { type: Boolean, default: false },
  motivoBloqueio:    { type: String, default: '' },
  canEdit:           { type: Boolean, default: false },
  canDelete:         { type: Boolean, default: false },
  canAtivar:         { type: Boolean, default: false },
  canProrrogar:      { type: Boolean, default: false },
  canAlocarCaminhao: { type: Boolean, default: false },
  canValidarViagem:  { type: Boolean, default: false },
});

const c = computed(() => props.cronograma.data ?? props.cronograma).value;
const ativando = ref(false);
const showAlocar = ref(false);
const showViagem = ref(false);
const cronoCaminhaoSelecionado = ref(null);

// Caminhoes do prestador deste cronograma — alimentado quando o modal abre
const caminhoesPrestador = ref([]);

const descricao = computed(() => {
  const parts = [];
  if (c.prestador?.nome) parts.push(c.prestador.nome);
  if (c.municipio?.nome) parts.push(c.municipio.nome + (c.municipio.uf ? '/' + c.municipio.uf : ''));
  return parts.join(' — ');
});

async function abrirAlocar() {
  // Carrega caminhoes do prestador uma vez
  if (caminhoesPrestador.value.length === 0 && c.prestador_id) {
    try {
      const res = await fetch(`${route('tdap.caminhoes.index')}?prestador_id=${c.prestador_id}&ativo=1&per_page=200`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (res.ok) {
        const json = await res.json();
        caminhoesPrestador.value = (json?.props?.caminhoes?.data ?? []).map(c => ({
          id: c.id,
          placa: c.placa,
          marca: c.marca,
          modelo: c.modelo,
          capacidade_m3: c.capacidade_m3,
        }));
      }
    } catch (_) {/* silent */}
  }
  showAlocar.value = true;
}

function abrirRegistrarViagem(cc) {
  cronoCaminhaoSelecionado.value = { id: cc.id, placa: cc.placa };
  showViagem.value = true;
}

function removerCaminhao(cc) {
  if (!confirm(`Remover o caminhão ${cc.placa} deste cronograma?`)) return;
  router.delete(route('tdap.crono_caminhoes.destroy', cc.id), { preserveScroll: true });
}

function ativar() {
  if (!confirm(`Ativar o cronograma ${c.numero}? Esta acao notifica o prestador.`)) return;
  ativando.value = true;
  router.post(route('tdap.cronogramas.ativar', c.id), {}, {
    onFinish: () => { ativando.value = false; },
  });
}

function encerrar() {
  const obs = prompt('Observação do encerramento (opcional):') ?? '';
  if (obs === null) return;
  router.post(route('tdap.cronogramas.encerrar', c.id), { observacao: obs });
}

function excluir() {
  if (!confirm(`Excluir cronograma ${c.numero}?`)) return;
  router.delete(route('tdap.cronogramas.destroy', c.id));
}

function fmtDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}

function fmtDateTime(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleString('pt-BR');
}
</script>
