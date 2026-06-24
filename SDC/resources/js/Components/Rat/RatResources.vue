<template>
  <div class="animate-fade-in-up pb-6 space-y-6">

    <!-- ── Cards de formulário: visíveis somente em modo edição ─────────── -->
    <template v-if="!viewOnly || editingIndex >= 0">

    <!-- ── Card 1: Dados do Recurso ──────────────────────────────────────── -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Dados do Recurso</h3>
          <p class="text-xs text-slate-500 mt-0.5">Informações sobre viaturas, equipamentos e pessoal empregado</p>
        </div>
      </div>
      <fieldset :disabled="viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <div class="rat-grid-3">
            <FormSelect label="Tipo de Recurso" v-model="formData.tipo_recurso" :options="tipoRecursoOptions" required :error="vErros.tipo_recurso" />
            <FormSelect label="Categoria" v-model="formData.categoria" :options="categoriaOptions" required :error="vErros.categoria" />
            <FormSelect label="Órgão Responsável" v-model="formData.orgao_responsavel" :options="orgaoOptions" required :error="vErros.orgao_responsavel" />
          </div>
          <div class="rat-grid-2">
            <FormField label="Identificação / Placa / Matrícula" v-model="formData.identificacao" placeholder="Digite para buscar ou criar nova identificação" required :error="vErros.identificacao" />
            <FormField label="Descrição do Recurso" v-model="formData.descricao" />
          </div>
        </div>
      </fieldset>
    </div>

    <!-- ── Card 2: Dados de Deslocamento ─────────────────────────────────── -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Dados de Deslocamento</h3>
          <p class="text-xs text-slate-500 mt-0.5">Datas, horários e localização do recurso</p>
        </div>
      </div>
      <fieldset :disabled="viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <div class="rat-grid-2">
            <div>
              <label class="form-label">Data/Hora de Saída</label>
              <div class="flex gap-2">
                <div class="flex-1 min-w-0">
                  <DatePicker :model-value="getDate(formData.data_saida)" @update:model-value="(v) => updateSaida('date', v)" />
                </div>
                <TimePicker :model-value="getTime(formData.data_saida)" @update:model-value="(v) => updateSaida('time', v)" extra-class="w-28" />
              </div>
              <button type="button" class="hoje-btn" @click="setHojeSaida">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Hoje
              </button>
            </div>
            <div>
              <label class="form-label">Data/Hora de Chegada</label>
              <div class="flex gap-2">
                <div class="flex-1 min-w-0">
                  <DatePicker :model-value="getDate(formData.data_chegada)" @update:model-value="(v) => updateChegada('date', v)" />
                </div>
                <TimePicker :model-value="getTime(formData.data_chegada)" @update:model-value="(v) => updateChegada('time', v)" extra-class="w-28" />
              </div>
              <button type="button" class="hoje-btn" @click="setHojeChegada">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Hoje
              </button>
            </div>
          </div>
          <div class="rat-grid-3">
            <FormField label="KM Percorrido" type="number" v-model="formData.km_percorrido" />
            <FormField label="Local de Origem" v-model="formData.local_origem" />
            <FormField label="Local de Destino" v-model="formData.local_destino" />
          </div>
        </div>
      </fieldset>
    </div>

    <!-- ── Card 3: Dados Operacionais ────────────────────────────────────── -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Dados Operacionais</h3>
          <p class="text-xs text-slate-500 mt-0.5">Capacidade, condição e operador do recurso</p>
        </div>
      </div>
      <fieldset :disabled="viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <div class="rat-grid-3">
            <FormField label="Quantidade" type="number" v-model="formData.quantidade" />
            <FormField label="Capacidade / Potência" v-model="formData.capacidade" />
            <FormSelect label="Condição do Recurso" v-model="formData.condicao" :options="condicaoOptions" />
          </div>
          <div class="rat-grid-2">
            <FormField label="Operador / Responsável" v-model="formData.operador" />
            <FormField label="Contato de Emergência" v-model="formData.contato_emergencia" mask="phone" />
          </div>
          <FormField label="Observações" v-model="formData.observacoes" type="textarea" />
        </div>
      </fieldset>
    </div>

    </template><!-- /v-if="!viewOnly" forma cards 1-3 -->

    <!-- ── Botão Adicionar Recurso ────────────────────────────────────────── -->
    <div v-if="!viewOnly" class="flex">
      <button
        type="button"
        @click="adicionarRecurso"
        class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition-colors shadow-sm"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Adicionar Recurso
      </button>
    </div>

    <!-- ── Tabela: Recursos Adicionados ──────────────────────────────────── -->
    <div v-if="localRecursos.length > 0" class="rat-section-card">
      <div class="flex items-center gap-2 mb-4">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <span class="font-semibold text-sm text-slate-700 dark:text-slate-300">Recursos Adicionados</span>
        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-500/15 text-blue-600 dark:text-blue-400">{{ localRecursos.length }}</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700/50">
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Tipo</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Categoria</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Identificação</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Órgão</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Qtd</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Condição</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(r, i) in localRecursos" :key="r.id || i"
              class="border-b border-slate-100 dark:border-slate-800/40 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors"
              :class="editingIndex === i ? 'bg-amber-50 dark:bg-amber-900/10' : ''"
            >
              <td class="py-2.5 px-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold" :class="tipoBadgeClass(r.tipo_recurso)">
                  {{ tipoLabel(r.tipo_recurso) || r.tipo_recurso || '—' }}
                </span>
              </td>
              <td class="py-2.5 px-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold" :class="categoriaBadgeClass(r.categoria)">
                  {{ categoriaLabel(r.categoria) || r.categoria || '—' }}
                </span>
              </td>
              <td class="py-2.5 px-3 text-slate-800 dark:text-slate-200 font-medium">{{ r.identificacao || '—' }}</td>
              <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 text-xs max-w-[140px] truncate">{{ orgaoLabel(r.orgao_responsavel) || r.orgao_responsavel || '—' }}</td>
              <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">{{ r.quantidade || '—' }}</td>
              <td class="py-2.5 px-3">
                <span v-if="r.condicao" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold" :class="condicaoBadgeClass(r.condicao)">
                  {{ condicaoLabel(r.condicao) }}
                </span>
                <span v-else class="text-slate-400 text-xs">—</span>
              </td>
              <td class="py-2.5 px-3">
                <div class="flex items-center gap-1">
                  <button v-if="!viewOnly" type="button" @click="editarRecurso(i)"
                    class="p-1.5 rounded-lg text-slate-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 transition-colors" title="Editar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button v-if="!viewOnly" type="button" @click="removerRecurso(i)"
                    class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 transition-colors" title="Remover">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Card 4: Agentes / Integrantes da Guarnição ────────────────────── -->
    <div v-if="!viewOnly" class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Agentes / Integrantes da Guarnição</h3>
        </div>
      </div>
      <fieldset :disabled="viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <div class="rat-grid-4">
            <FormField label="Nome Completo" v-model="agentForm.nome" placeholder="Selecione ou digite o nome do agente" required />
            <FormField label="Matrícula / MASP" v-model="agentForm.masp" placeholder="Ex: 1234567" />
            <FormField label="PG/Cargo" v-model="agentForm.pg_cargo" placeholder="Ex: Sgt, Cb, etc." />
            <div>
              <label class="form-label">Condutor</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label"><input type="radio" :value="true"  v-model="agentForm.is_condutor" class="radio-input" /> Sim</label>
                <label class="radio-label"><input type="radio" :value="false" v-model="agentForm.is_condutor" class="radio-input" /> Não</label>
              </div>
            </div>
          </div>
          <div class="rat-grid-3">
            <FormField label="Órgão" v-model="agentForm.orgao" placeholder="Ex: CBMMG" />
            <FormField label="Unidade" v-model="agentForm.unidade" placeholder="Ex: 1º BBM" />
            <FormField label="Função no Atendimento" v-model="agentForm.funcao" placeholder="Ex: Comandante de Socorro, Socorrista" />
          </div>
        </div>
        <div v-if="!viewOnly" class="flex mt-6">
          <button
            type="button"
            @click="adicionarAgente"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition-colors shadow-sm"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Adicionar Agente
          </button>
        </div>
      </fieldset>
    </div>

    <!-- ── Tabela: Agentes Adicionados ───────────────────────────────────── -->
    <div v-if="localAgentes.length > 0" class="rat-section-card">
      <div class="flex items-center gap-2 mb-4">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span class="font-semibold text-sm text-slate-700 dark:text-slate-300">Agentes Adicionados</span>
        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-500/15 text-blue-600 dark:text-blue-400">{{ localAgentes.length }}</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-700/50">
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Nome</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">MASP</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">PG/Cargo</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Órgão</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Unidade</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Função</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Condutor</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(a, i) in localAgentes" :key="i"
              class="border-b border-slate-100 dark:border-slate-800/40 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
              <td class="py-2.5 px-3 text-slate-800 dark:text-slate-200 font-medium">{{ a.nome_completo || a.nome || '—' }}</td>
              <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 text-xs">{{ a.masp || '—' }}</td>
              <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 text-xs">{{ a.pg_cargo || '—' }}</td>
              <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 text-xs">{{ a.orgao || '—' }}</td>
              <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 text-xs">{{ a.unidade || '—' }}</td>
              <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 text-xs">{{ a.funcao || '—' }}</td>
              <td class="py-2.5 px-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                  :class="a.is_condutor ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400' : 'bg-red-500/15 text-red-700 dark:text-red-400'">
                  {{ a.is_condutor ? 'Sim' : 'Não' }}
                </span>
              </td>
              <td class="py-2.5 px-3">
                <button v-if="!viewOnly" type="button" @click="removerAgente(i)"
                  class="p-1.5 rounded-lg text-slate-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 transition-colors" title="Remover">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Footer de ações ──────────────────────────────────────────────────── -->
    <div v-if="!viewOnly" class="rat-actions-footer">
      <div class="flex items-center justify-end gap-3 flex-wrap">
        <button
          type="button"
          @click="handleSave"
          :disabled="loading"
          class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white shadow-sm transition-colors duration-150 disabled:opacity-60 disabled:cursor-not-allowed min-w-[140px] justify-center"
        >
          <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          {{ loading ? 'Salvando...' : 'Salvar Recursos e Agentes' }}
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { useToast } from '@/Composables/useToast';
import FormField from '@/Components/Form/FormField.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import DatePicker from '@/Components/Form/DatePicker.vue';
import TimePicker from '@/Components/Form/TimePicker.vue';

const props = defineProps({
  recursos: { type: [Array, Object], default: () => [] },
  viewOnly: { type: Boolean, default: false },
  loading:  { type: Boolean, default: false },
});

const emit = defineEmits(['add', 'remove', 'update', 'save']);

const { show: toast } = useToast();
const vErros = reactive({ tipo_recurso: '', categoria: '', orgao_responsavel: '', identificacao: '' });

// ── Form state ──────────────────────────────────────────────────────────────
const emptyForm = () => ({
  tipo_recurso: '', categoria: '', orgao_responsavel: '', identificacao: '',
  descricao: '', data_saida: '', data_chegada: '', km_percorrido: '',
  local_origem: '', local_destino: '', quantidade: '', capacidade: '',
  condicao: '', operador: '', contato_emergencia: '', observacoes: '',
});
const emptyAgent = () => ({
  nome: '', masp: '', pg_cargo: '', orgao: '', unidade: '', funcao: '', is_condutor: false,
});

const formData     = ref(emptyForm());
const agentForm    = ref(emptyAgent());
const editingIndex = ref(-1);

// ── Local lists ──────────────────────────────────────────────────────────────
const rawRecursos   = Array.isArray(props.recursos) ? props.recursos : [];
const localAgentes  = ref(rawRecursos.flatMap(r => r.agentes || []));
const localRecursos = ref(rawRecursos.map(({ agentes: _a, ...rest }) => rest));

// ── DateTime helpers ─────────────────────────────────────────────────────────
function getDate(dt) {
  if (!dt) return '';
  return dt.includes('T') ? dt.split('T')[0] : dt.includes(' ') ? dt.split(' ')[0] : dt;
}
function getTime(dt) {
  if (!dt) return '';
  return dt.includes('T') ? (dt.split('T')[1] || '').substring(0, 5)
       : dt.includes(' ') ? (dt.split(' ')[1] || '').substring(0, 5) : '';
}
function nowTime() {
  const d = new Date();
  return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}
function today() { return new Date().toISOString().split('T')[0]; }
function combine(date, time) { return date ? (time ? `${date}T${time}` : date) : ''; }
function updateSaida(part, v) {
  const cur = formData.value.data_saida || '';
  formData.value = { ...formData.value, data_saida: combine(part === 'date' ? v : getDate(cur), part === 'time' ? v : getTime(cur)) };
}
function updateChegada(part, v) {
  const cur = formData.value.data_chegada || '';
  formData.value = { ...formData.value, data_chegada: combine(part === 'date' ? v : getDate(cur), part === 'time' ? v : getTime(cur)) };
}
function setHojeSaida() {
  formData.value = { ...formData.value, data_saida: `${today()}T${nowTime()}` };
}
function setHojeChegada() {
  formData.value = { ...formData.value, data_chegada: `${today()}T${nowTime()}` };
}

// ── Recurso actions ──────────────────────────────────────────────────────────
function validarRecurso() {
  vErros.tipo_recurso    = formData.value.tipo_recurso    ? '' : 'Campo obrigatório';
  vErros.categoria       = formData.value.categoria       ? '' : 'Campo obrigatório';
  vErros.orgao_responsavel = formData.value.orgao_responsavel ? '' : 'Campo obrigatório';
  vErros.identificacao   = formData.value.identificacao   ? '' : 'Campo obrigatório';
  const campos = Object.entries(vErros).filter(([, v]) => v).map(([k]) => ({
    tipo_recurso: 'Tipo de Recurso', categoria: 'Categoria',
    orgao_responsavel: 'Órgão Responsável', identificacao: 'Identificação',
  }[k]));
  if (campos.length) { toast(`Preencha os campos obrigatórios: ${campos.join(', ')}`, 'error'); return false; }
  return true;
}

function adicionarRecurso() {
  if (!validarRecurso()) return;
  if (editingIndex.value >= 0) {
    localRecursos.value[editingIndex.value] = { ...formData.value };
  } else {
    localRecursos.value.push({ ...formData.value, id: Date.now() });
  }
  editingIndex.value = -1;
  formData.value = emptyForm();
  Object.keys(vErros).forEach(k => { vErros[k] = ''; });
  emitUpdate();
}
function editarRecurso(index) {
  formData.value = { ...localRecursos.value[index] };
  editingIndex.value = index;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
function removerRecurso(index) {
  localRecursos.value.splice(index, 1);
  if (editingIndex.value === index) { editingIndex.value = -1; formData.value = emptyForm(); }
  else if (editingIndex.value > index) editingIndex.value--;
  emitUpdate();
}

// ── Agente actions ────────────────────────────────────────────────────────────
function adicionarAgente() {
  if (!agentForm.value.nome) return;
  localAgentes.value.push({ ...agentForm.value });
  agentForm.value = emptyAgent();
  emitUpdate();
}
function removerAgente(index) {
  localAgentes.value.splice(index, 1);
  emitUpdate();
}

// ── Emit ─────────────────────────────────────────────────────────────────────
function emitUpdate() {
  emit('update', localRecursos.value.map((r, i) => ({
    ...r, agentes: i === 0 ? [...localAgentes.value] : [],
  })));
}
function handleSave() {
  const anyRequiredFilled = formData.value.tipo_recurso || formData.value.categoria ||
                             formData.value.orgao_responsavel || formData.value.identificacao;
  if (anyRequiredFilled && !validarRecurso()) return;
  emitUpdate();
  emit('save');
}

// ── Sync from parent ──────────────────────────────────────────────────────────
watch(() => props.recursos, (nv) => {
  const arr = Array.isArray(nv) ? nv : [];
  localAgentes.value  = arr.flatMap(r => r.agentes || []);
  localRecursos.value = arr.map(({ agentes: _a, ...rest }) => rest);
}, { deep: false });

// ── Options ───────────────────────────────────────────────────────────────────
const tipoRecursoOptions = [
  { value: 'viatura',     label: 'Viatura' },
  { value: 'aeronave',    label: 'Aeronave' },
  { value: 'aquatico',    label: 'Aquático' },
  { value: 'embarcacao',  label: 'Embarcação' },
  { value: 'equipamento', label: 'Equipamento' },
  { value: 'material',    label: 'Material' },
  { value: 'pessoal',     label: 'Pessoal' },
  { value: 'a_pe',        label: 'A Pé' },
  { value: 'outros',      label: 'Outros' },
];
const categoriaOptions = [
  { value: 'operacional', label: 'Operacional' },
  { value: 'apoio',       label: 'Apoio' },
  { value: 'logistica',   label: 'Logística' },
  { value: 'saude',       label: 'Saúde' },
  { value: 'resgate',     label: 'Resgate' },
  { value: 'suporte',     label: 'Suporte' },
  { value: 'combate',     label: 'Combate a Incêndio' },
  { value: 'busca',       label: 'Busca e Salvamento' },
];
const orgaoOptions = [
  { value: 'cbmmg',          label: 'Corpo de Bombeiros Militar de MG' },
  { value: 'pmmg',           label: 'Polícia Militar de MG' },
  { value: 'pcmg',           label: 'Polícia Civil de Minas Gerais' },
  { value: 'prf',            label: 'Polícia Rodoviária Federal (PRF)' },
  { value: 'defesa_civil',   label: 'Defesa Civil' },
  { value: 'samu',           label: 'SAMU' },
  { value: 'der',            label: 'DER' },
  { value: 'dnit',           label: 'DNIT' },
  { value: 'concessionaria', label: 'Concessionária de Rodovias' },
  { value: 'prefeitura',     label: 'Prefeitura Municipal' },
  { value: 'outros',         label: 'Outros' },
];
const condicaoOptions = [
  { value: 'otima',      label: 'Ótima' },
  { value: 'boa',        label: 'Boa' },
  { value: 'regular',    label: 'Regular' },
  { value: 'ruim',       label: 'Ruim' },
  { value: 'inoperante', label: 'Inoperante' },
];

const tipoBadgeClasses = {
  viatura:     'bg-indigo-500/15 text-indigo-700 dark:text-indigo-400',
  aeronave:    'bg-sky-500/15 text-sky-700 dark:text-sky-400',
  aquatico:    'bg-blue-500/15 text-blue-700 dark:text-blue-400',
  embarcacao:  'bg-cyan-500/15 text-cyan-700 dark:text-cyan-400',
  equipamento: 'bg-orange-500/15 text-orange-700 dark:text-orange-400',
  material:    'bg-amber-500/15 text-amber-700 dark:text-amber-400',
  pessoal:     'bg-violet-500/15 text-violet-700 dark:text-violet-400',
  a_pe:        'bg-slate-500/15 text-slate-700 dark:text-slate-400',
  outros:      'bg-gray-500/15 text-gray-700 dark:text-gray-400',
};
const categoriaBadgeClasses = {
  operacional: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
  apoio:       'bg-blue-500/15 text-blue-700 dark:text-blue-400',
  logistica:   'bg-orange-500/15 text-orange-700 dark:text-orange-400',
  saude:       'bg-red-500/15 text-red-700 dark:text-red-400',
  resgate:     'bg-green-500/15 text-green-700 dark:text-green-400',
  suporte:     'bg-teal-500/15 text-teal-700 dark:text-teal-400',
  combate:     'bg-rose-500/15 text-rose-700 dark:text-rose-400',
  busca:       'bg-purple-500/15 text-purple-700 dark:text-purple-400',
};
const condicaoBadgeClasses = {
  otima:      'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
  boa:        'bg-green-500/15 text-green-700 dark:text-green-400',
  regular:    'bg-amber-500/15 text-amber-700 dark:text-amber-400',
  ruim:       'bg-orange-500/15 text-orange-700 dark:text-orange-400',
  inoperante: 'bg-slate-900/90 text-white dark:bg-slate-100 dark:text-slate-900',
};
function tipoBadgeClass(v)      { return tipoBadgeClasses[v]      || 'bg-gray-500/15 text-gray-700'; }
function categoriaBadgeClass(v) { return categoriaBadgeClasses[v] || 'bg-gray-500/15 text-gray-700'; }
function condicaoBadgeClass(v)  { return condicaoBadgeClasses[v]  || 'bg-gray-500/15 text-gray-700'; }
function tipoLabel(v)      { return tipoRecursoOptions.find(o => o.value === v)?.label || ''; }
function categoriaLabel(v) { return categoriaOptions.find(o => o.value === v)?.label || ''; }
function orgaoLabel(v)     { return orgaoOptions.find(o => o.value === v)?.label || ''; }
function condicaoLabel(v)  { return condicaoOptions.find(o => o.value === v)?.label || ''; }
</script>

<style scoped>
.form-label {
  @apply block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2;
}
.hoje-btn {
  @apply mt-2 inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded
    border border-emerald-500/50
    text-emerald-600 dark:text-emerald-400
    bg-emerald-50 dark:bg-emerald-900/20
    hover:bg-emerald-100 dark:hover:bg-emerald-900/30
    transition-colors duration-150;
}
.radio-label {
  @apply flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer select-none;
}
.radio-input {
  @apply h-4 w-4 text-blue-600 border-slate-300 dark:border-slate-600 focus:ring-blue-500 cursor-pointer;
}
</style>
