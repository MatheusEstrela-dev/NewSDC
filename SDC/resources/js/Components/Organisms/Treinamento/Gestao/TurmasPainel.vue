<script setup>
/**
 * Turmas do curso (Area do Gestor, issue #86): lista com ocupacao e CRUD via
 * GestaoCursoComandos. Curso EAD nao usa turma - a secao mostra so o aviso
 * (GestaoCursoComandos::criarTurma ja recusa, isto e so a tela refletindo).
 */
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SecaoRecolhivel from '@/Components/Molecules/Treinamento/SecaoRecolhivel.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TurmaFormModal from '@/Components/Organisms/Treinamento/Gestao/TurmaFormModal.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  treinamentoId: { type: [Number, String], required: true },
  turmas: { type: Array, default: () => [] },
  usaTurma: { type: Boolean, default: true },
  municipios: { type: Array, default: () => [] },
  podeConfigurar: { type: Boolean, default: false },
});

const emit = defineEmits(['alterado']);

const { show: toast } = useToast();

const modalAberto = ref(false);
const turmaEmEdicao = ref(null);

function novaTurma() {
  turmaEmEdicao.value = null;
  modalAberto.value = true;
}

function editarTurma(turma) {
  turmaEmEdicao.value = turma;
  modalAberto.value = true;
}

function removerTurma(turma) {
  if (!confirm(`Remover a turma "${turma.nome}"? So e possivel quando nao houver inscricao ativa.`)) return;

  router.delete(route('treinamentos.gestor.turmas.destroy', [props.treinamentoId, turma.id]), {
    preserveScroll: true,
    onSuccess: () => toast('Turma removida.', 'success'),
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível remover a turma.', 'error'),
  });
}

function formatarData(data) {
  if (!data) return '—';
  const d = new Date(`${data}T00:00:00`);
  return Number.isNaN(d.getTime()) ? data : d.toLocaleDateString('pt-BR');
}
</script>

<template>
  <SecaoRecolhivel titulo="Turmas" :total="turmas.length" namespace="treinamento-gestor" secao-id="turmas">
    <CardBase v-if="!usaTurma" padding="md">
      <Text size="sm" color="muted">
        Curso EAD não trabalha com turmas: o aluno entra a qualquer momento enquanto o curso está publicado.
      </Text>
    </CardBase>

    <template v-else>
      <div class="mb-3 flex justify-end">
        <Button v-if="podeConfigurar" variant="primary" size="sm" @click="novaTurma">Nova turma</Button>
      </div>

      <CardBase v-if="turmas.length === 0" padding="md">
        <Text size="sm" color="muted">Nenhuma turma criada ainda.</Text>
      </CardBase>

      <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <CardBase v-for="turma in turmas" :key="turma.id" padding="md" hover>
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <Text size="sm" class="font-semibold truncate">{{ turma.nome }}</Text>
              <Text size="xs" color="muted">{{ formatarData(turma.data_inicio) }} a {{ formatarData(turma.data_fim) }}</Text>
            </div>
            <Badge v-if="turma.encerrou_inscricoes" variant="neutral" size="sm">Inscrições encerradas</Badge>
            <Badge v-else-if="turma.vagas_restantes === 0" variant="danger" size="sm">Sem vagas</Badge>
          </div>

          <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
            <span>{{ turma.vagas_ocupadas }} de {{ turma.numero_vagas }} vagas ocupadas</span>
            <span v-if="turma.inscricoes_pendentes > 0">{{ turma.inscricoes_pendentes }} pendente(s)</span>
            <span v-if="turma.local">{{ turma.local }}</span>
          </div>

          <div v-if="podeConfigurar" class="mt-3 flex justify-end gap-2 border-t border-slate-100 pt-2 dark:border-slate-700/60">
            <Button variant="outline" size="sm" @click="editarTurma(turma)">Editar</Button>
            <Button variant="danger" size="sm" @click="removerTurma(turma)">Remover</Button>
          </div>
        </CardBase>
      </div>
    </template>

    <TurmaFormModal
      :show="modalAberto"
      :treinamento-id="treinamentoId"
      :turma="turmaEmEdicao"
      :municipios="municipios"
      @fechar="modalAberto = false"
      @salvo="$emit('alterado')"
    />
  </SecaoRecolhivel>
</template>
