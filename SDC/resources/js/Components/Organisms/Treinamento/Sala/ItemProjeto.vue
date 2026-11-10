<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';

const props = defineProps({
  item: { type: Object, required: true },
  urlEntrega: { type: String, default: null },
  urlArquivo: { type: String, default: null },
});

const form = useForm({ arquivo: null, comentario: '' });

const COR_ENTREGA = { ENVIADA: 'info', APROVADA: 'success', DEVOLVIDA: 'warning' };

const aprovado = computed(() => props.item.entrega === 'APROVADA');
const podeEnviar = computed(() => props.urlEntrega !== null && !aprovado.value);
const rotuloEnvio = computed(() => (props.item.entrega ? 'Enviar novamente' : 'Enviar projeto'));

function escolherArquivo(event) {
  form.arquivo = event.target.files?.[0] ?? null;
}

function enviar() {
  form.post(props.urlEntrega, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}
</script>

<template>
  <div class="mx-auto w-full max-w-3xl space-y-5 p-6">
    <div>
      <Badge variant="warning">Projeto</Badge>
      <Heading :level="2" class="mt-2 text-xl font-bold text-slate-800 dark:text-slate-100">{{ item.titulo }}</Heading>
    </div>

    <Text v-if="item.descricao" size="sm" class="whitespace-pre-line">{{ item.descricao }}</Text>

    <a
      v-if="item.tem_arquivo && urlArquivo"
      :href="urlArquivo"
      class="inline-block text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
    >
      Baixar arquivo de apoio
    </a>

    <div v-if="item.entrega" class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
      <div class="flex items-center gap-2">
        <Text size="sm" weight="semibold">Ultima entrega</Text>
        <Badge :variant="COR_ENTREGA[item.entrega] ?? 'info'">{{ item.entrega_label }}</Badge>
      </div>
      <Text v-if="item.parecer" size="sm" color="muted" class="mt-2 whitespace-pre-line">Parecer do professor: {{ item.parecer }}</Text>
    </div>

    <form v-if="podeEnviar" class="space-y-3" @submit.prevent="enviar">
      <div>
        <label class="mb-1 block text-sm font-medium" for="entrega-arquivo">Arquivo do projeto</label>
        <input id="entrega-arquivo" type="file" class="block w-full text-sm" @change="escolherArquivo">
        <Text v-if="form.errors.arquivo" size="xs" color="danger" class="mt-1">{{ form.errors.arquivo }}</Text>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium" for="entrega-comentario">Comentario (opcional)</label>
        <textarea
          id="entrega-comentario"
          v-model="form.comentario"
          rows="3"
          maxlength="2000"
          class="w-full rounded-lg border border-slate-300 bg-white p-2 text-sm dark:border-slate-600 dark:bg-slate-800"
        />
        <Text v-if="form.errors.comentario" size="xs" color="danger" class="mt-1">{{ form.errors.comentario }}</Text>
      </div>

      <Button type="submit" :loading="form.processing" :disabled="!form.arquivo">{{ rotuloEnvio }}</Button>
    </form>

    <Text v-else-if="aprovado" size="sm" color="success">Projeto aprovado.</Text>
    <Text v-else size="sm" color="muted">O envio de projetos nao esta disponivel na previa.</Text>
  </div>
</template>
