<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import MenuCurso from '@/Components/Organisms/Treinamento/Professor/MenuCurso.vue';
import LiberarTentativaModal from '@/Components/Organisms/Treinamento/Professor/LiberarTentativaModal.vue';
import NotasProvasCell from '@/Components/Molecules/Treinamento/NotasProvasCell.vue';
import { useToast } from '@/Composables/useToast';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  curso: { type: Object, required: true },
  alunos: { type: Array, default: () => [] },
  travados: { type: Boolean, default: false },
  pendentes: { type: Number, default: 0 },
});

const { show: toast } = useToast();

const alvo = ref({ aluno: null, prova: null });
const modalAberto = ref(false);

function abrirLiberacao(aluno, prova) {
  alvo.value = { aluno, prova };
  modalAberto.value = true;
}

// A linha vem do servidor: recarrega so os alunos para refletir a trava e as tentativas.
function aposLiberar() {
  toast('Tentativa liberada.');
  router.reload({ only: ['alunos'], preserveScroll: true });
}

const temProvaTravada = (aluno) => (aluno.notas ?? []).some((n) => n.travada);
</script>

<template>
  <div>
    <PageHeader
      :title="curso.titulo"
      description="Acompanhe o andamento dos alunos aprovados no curso."
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[14rem_1fr]">
      <MenuCurso :curso="curso" ativo="alunos" :pendentes="pendentes" />

      <div class="min-w-0 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <Text color="muted">{{ alunos.length }} aluno(s){{ travados ? ' travado(s)' : '' }}</Text>
          <Button
            size="sm"
            :variant="travados ? 'primary' : 'outline'"
            :href="travados ? route('treinamentos.professor.alunos', curso.id) : route('treinamentos.professor.alunos', { treinamento: curso.id, travados: 1 })"
          >
            Travados
          </Button>
        </div>

        <CardBase v-if="alunos.length === 0">
          <Text color="muted">{{ travados ? 'Nenhum aluno travado.' : 'Nenhum aluno aprovado neste curso ainda.' }}</Text>
        </CardBase>

        <div v-else class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
          <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/60">
              <tr class="text-left text-slate-500 dark:text-slate-400">
                <th class="px-4 py-2 font-medium">Aluno</th>
                <th class="px-4 py-2 font-medium">Origem</th>
                <th class="px-4 py-2 font-medium">Progresso</th>
                <th class="px-4 py-2 font-medium">Onde está</th>
                <th class="px-4 py-2 font-medium">Provas</th>
                <th class="px-4 py-2 text-right font-medium">Situação</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="aluno in alunos" :key="aluno.inscricao_id" class="text-slate-700 dark:text-slate-300">
                <td class="px-4 py-2.5 font-medium">{{ aluno.nome }}</td>
                <td class="px-4 py-2.5">
                  <Badge :cor="aluno.origem === 'Servidor' ? 'blue' : 'purple'">{{ aluno.origem }}</Badge>
                </td>
                <td class="px-4 py-2.5">
                  <div class="flex items-center gap-2">
                    <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                      <div class="h-full bg-blue-600" :style="{ width: `${aluno.percentual}%` }" />
                    </div>
                    <span class="tabular-nums">{{ aluno.percentual }}%</span>
                  </div>
                </td>
                <td class="px-4 py-2.5">{{ aluno.onde_esta }}</td>
                <td class="px-4 py-2.5">
                  <NotasProvasCell :notas="aluno.notas ?? []" @liberar="(prova) => abrirLiberacao(aluno, prova)" />
                </td>
                <td class="px-4 py-2.5 text-right">
                  <Badge v-if="temProvaTravada(aluno)" variant="danger">Prova travada</Badge>
                  <Link
                    v-else-if="aluno.travado"
                    :href="route('treinamentos.professor.correcoes', curso.id)"
                    class="inline-flex"
                  >
                    <Badge variant="warning">Aguardando correção</Badge>
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <LiberarTentativaModal
      :show="modalAberto"
      :treinamento-id="curso.id"
      :aluno="alvo.aluno"
      :prova="alvo.prova"
      @fechar="modalAberto = false"
      @liberado="aposLiberar"
    />
  </div>
</template>
