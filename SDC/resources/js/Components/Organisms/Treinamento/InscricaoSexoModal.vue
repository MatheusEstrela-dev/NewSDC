<script setup>
import { computed, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';
import SexoInscricaoField from '@/Components/Molecules/Treinamento/SexoInscricaoField.vue';

/*
 * Confirmacao da inscricao: sexo (sempre) e turma (so quando o curso tem
 * alguma - issue #86). `padrao` e o sexo da ultima inscricao do mesmo
 * inscrito (pre-preenche). Emite `confirmar` com { sexo, turma_id }; quem
 * abre faz o POST.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  titulo: { type: String, default: '' },
  padrao: { type: String, default: null },
  processando: { type: Boolean, default: false },
  // Turmas do curso (vazio = curso sem turma, inscricao segue sem turma).
  turmas: { type: Array, default: () => [] },
});
const emit = defineEmits(['fechar', 'confirmar']);

const sexo = ref('');
const turmaId = ref(null);

const temTurma = computed(() => props.turmas.length > 0);

// SelectInput (atomo do sistema) nao suporta <option disabled>: a
// indisponibilidade entra so no texto, e quem barra de verdade e o
// ControleDeVagas/PeriodoDeInscricao no servidor (a mensagem de erro volta
// pro formulario se a pessoa escolher mesmo assim uma turma sem vaga).
const opcoesTurma = computed(() => props.turmas.map((turma) => {
  const indisponivel = turma.encerrou_inscricoes || turma.vagas_restantes <= 0;
  const periodo = turma.data_inicio ? ` (${formatarData(turma.data_inicio)} a ${formatarData(turma.data_fim)})` : '';
  const vagas = indisponivel
    ? (turma.encerrou_inscricoes ? ' — inscrições encerradas' : ' — sem vagas')
    : ` — ${turma.vagas_restantes} vaga(s)`;

  return { value: turma.id, label: `${turma.nome}${periodo}${vagas}` };
}));

// Uma unica turma disponivel: pre-seleciona para poupar um clique.
function turmaPadrao() {
  const disponiveis = props.turmas.filter((t) => !t.encerrou_inscricoes && t.vagas_restantes > 0);
  return disponiveis.length === 1 ? disponiveis[0].id : null;
}

function formatarData(data) {
  if (!data) return '';
  const d = new Date(`${data}T00:00:00`);
  return Number.isNaN(d.getTime()) ? data : d.toLocaleDateString('pt-BR');
}

watch(() => props.show, (aberto) => {
  if (aberto) {
    sexo.value = props.padrao ?? '';
    turmaId.value = turmaPadrao();
  }
});

const podeConfirmar = computed(() => !temTurma.value || !!turmaId.value);

function confirmar() {
  if (!podeConfirmar.value) return;
  emit('confirmar', { sexo: sexo.value, turma_id: turmaId.value });
}
</script>

<template>
  <Modal :show="show" max-width="md" @close="emit('fechar')">
    <form class="space-y-4 p-6" @submit.prevent="confirmar">
      <Heading :level="3" class="text-lg font-semibold">Confirmar inscrição</Heading>
      <Text v-if="titulo" color="muted">{{ titulo }}</Text>

      <div v-if="temTurma">
        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">
          Turma <span class="text-red-500">*</span>
        </label>
        <SelectInput v-model="turmaId" :options="opcoesTurma" placeholder="Escolha a turma" />
      </div>

      <SexoInscricaoField v-model="sexo" />

      <div class="flex justify-end gap-2">
        <Button type="button" variant="outline" @click="emit('fechar')">Voltar</Button>
        <Button type="submit" variant="primary" :loading="processando" :disabled="!podeConfirmar">Inscrever-se</Button>
      </div>
    </form>
  </Modal>
</template>
