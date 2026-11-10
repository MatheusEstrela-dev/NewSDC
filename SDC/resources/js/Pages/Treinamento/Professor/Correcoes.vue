<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import MenuCurso from '@/Components/Organisms/Treinamento/Professor/MenuCurso.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  curso: { type: Object, required: true },
  entregas: { type: Array, default: () => [] },
  pendentes: { type: Number, default: 0 },
});

// Entrega em que o professor esta escrevendo o parecer de devolucao.
const devolvendo = ref(null);
const form = useForm({ aprovar: true, parecer: '' });

function rota(entrega, nome) {
  return route(`treinamentos.professor.entregas.${nome}`, [props.curso.id, entrega.id]);
}

function aprovar(entrega) {
  form.aprovar = true;
  form.parecer = '';
  form.post(rota(entrega, 'correcao'), { preserveScroll: true });
}

function abrirDevolucao(entrega) {
  form.reset();
  form.clearErrors();
  devolvendo.value = entrega.id;
}

function devolver(entrega) {
  form.aprovar = false;
  form.post(rota(entrega, 'correcao'), {
    preserveScroll: true,
    onSuccess: () => { devolvendo.value = null; },
  });
}

function dataHora(valor) {
  return valor ? new Date(valor).toLocaleString('pt-BR') : '';
}
</script>

<template>
  <div>
    <PageHeader
      :title="curso.titulo"
      description="Entregas de projeto aguardando correção, das mais antigas para as mais novas."
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[14rem_1fr]">
      <MenuCurso :curso="curso" ativo="correcoes" :pendentes="pendentes" />

      <div class="min-w-0 space-y-4">
        <CardBase v-if="entregas.length === 0">
          <Text color="muted">Nenhuma entrega aguardando correção.</Text>
        </CardBase>

        <CardBase v-for="entrega in entregas" :key="entrega.id">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <Heading :level="3" class="text-base font-semibold">{{ entrega.aluno }}</Heading>
              <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                <Badge :cor="entrega.origem === 'Servidor' ? 'blue' : 'purple'">{{ entrega.origem }}</Badge>
                <Text size="xs" color="muted">{{ entrega.pasta }} - {{ entrega.item }}</Text>
                <Text size="xs" color="muted">Enviada em {{ dataHora(entrega.enviada_em) }}</Text>
              </div>
            </div>
            <Button size="sm" variant="outline" :href="rota(entrega, 'arquivo') + '?download=1'">
              Baixar {{ entrega.arquivo_nome || 'arquivo' }}
            </Button>
          </div>

          <p v-if="entrega.comentario" class="mt-3 whitespace-pre-line rounded-md bg-slate-50 p-3 text-sm dark:bg-slate-800/60">
            {{ entrega.comentario }}
          </p>

          <div v-if="devolvendo === entrega.id" class="mt-4 space-y-2">
            <label class="block text-sm font-medium">Parecer para o aluno</label>
            <textarea
              v-model="form.parecer"
              rows="3"
              maxlength="2000"
              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
            />
            <p v-if="form.errors.parecer" class="text-sm text-red-600">{{ form.errors.parecer }}</p>
            <div class="flex justify-end gap-2">
              <Button size="sm" variant="outline" type="button" @click="devolvendo = null">Cancelar</Button>
              <Button size="sm" variant="danger" type="button" :loading="form.processing" @click="devolver(entrega)">
                Devolver ao aluno
              </Button>
            </div>
          </div>

          <div v-else class="mt-4 flex flex-wrap justify-end gap-2">
            <Button size="sm" variant="outline" type="button" @click="abrirDevolucao(entrega)">Devolver com parecer</Button>
            <Button size="sm" variant="success" type="button" :loading="form.processing" @click="aprovar(entrega)">Aprovar</Button>
          </div>
        </CardBase>
      </div>
    </div>
  </div>
</template>
