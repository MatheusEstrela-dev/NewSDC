<template>
  <!-- Mesma janela da Serie Historica (CronogramaHistoricoModal), com a tramitacao no corpo. -->
  <Modal :show="open" max-width="2xl" @close="$emit('close')">
    <div class="modal-serie-corpo">
      <div class="px-6 py-5 modal-serie-header">
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-full bg-slate-900/40 border border-slate-700/40 flex items-center justify-center">
              <ArrowsRightLeftIcon class="w-5 h-5 text-slate-200" />
            </div>
            <div class="min-w-0">
              <h3 class="text-lg font-semibold text-white truncate">Acompanhamento do Cronograma</h3>
              <p class="text-sm text-slate-200/80 truncate">
                Cronograma {{ cronograma?.numero || '—' }}
                <template v-if="cronograma?.municipio"> · {{ cronograma.municipio }}</template>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <Link
              v-if="cronograma"
              :href="route('tdap.cronogramas.show', cronograma.id)"
              class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-lg text-xs text-slate-200/90 border border-white/20 hover:bg-white/10 hover:text-white transition-all"
            >
              Abrir ficha
            </Link>
            <button
              type="button"
              class="p-2 rounded-lg text-slate-200/80 hover:text-white hover:bg-white/10 transition-all"
              title="Fechar"
              @click="$emit('close')"
            >
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>

      <div class="p-6">
        <CronogramaTramitacaoPanel
          v-if="cronograma"
          :cronograma-id="cronograma.id"
          @tramitado="$emit('tramitado')"
        />
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import CronogramaTramitacaoPanel from '@/Components/Organisms/Tdap/CronogramaTramitacaoPanel.vue';

defineProps({
  open:       { type: Boolean, default: false },
  // Card do painel: { id, numero, municipio }
  cronograma: { type: Object, default: null },
});

defineEmits(['close', 'tramitado']);
</script>
