<template>
  <section class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
    <form class="space-y-4 p-6" data-proposta-catalogo @submit.prevent="enviar">
      <header>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ item ? `Propor mudança em ${item.codigo}` : 'Propor novo item' }}</h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
          A proposta só entra no catálogo depois de aprovada por <strong>outra pessoa</strong>. Tudo fica registrado com autor, data e IP.
        </p>
      </header>

      <div class="grid gap-4 sm:grid-cols-2">
        <FormSelect v-if="item" v-model="form.acao" label="O que fazer" :options="acoesDoItem" required :error="form.errors.acao" />
        <FormField v-else v-model="form.codigo" label="Código" placeholder="PERM-VIATURA" required hint="Letras maiúsculas, números e hífen." :error="form.errors.codigo" />
        <FormField v-if="item" :model-value="item.codigo" label="Código" disabled />
      </div>

      <template v-if="form.acao !== 'encerrar'">
        <div class="grid gap-4 sm:grid-cols-2">
          <FormSelect v-model="form.dados.tipo" label="Tipo" :options="tipos" required :error="form.errors['dados.tipo']" />
          <FormSelect v-model="form.dados.beneficiario" label="Beneficiário" :options="BENEFICIARIOS" required :error="form.errors['dados.beneficiario']" />
        </div>
        <FormField v-model="form.dados.titulo" label="Título" required :error="form.errors['dados.titulo']" />
        <FormTextarea v-model="form.dados.descricao" label="Descrição" :rows="3" required :error="form.errors['dados.descricao']" />
        <div class="grid gap-4 sm:grid-cols-3">
          <FormSelect v-model="form.dados.faixa_minima" label="Faixa mínima" :options="FAIXAS_OPCOES" required :error="form.errors['dados.faixa_minima']" />
          <FormField v-model="form.dados.custo_pontos" type="number" label="Custo em pontos" required hint="0 = prêmio só pela faixa." :error="form.errors['dados.custo_pontos']" />
          <FormField v-model="form.dados.limite_por_ente_temporada" type="number" label="Limite por ente/temporada" hint="Vazio = sem limite." :error="form.errors['dados.limite_por_ente_temporada']" />
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
          <FormField v-if="form.dados.tipo !== 'bem_permanente'" v-model="form.dados.quantidade" type="number" label="Quantidade/vagas" hint="Vazio = sem limite." :error="form.errors['dados.quantidade']" />
          <FormField v-model="form.dados.prazo_reserva_dias" type="number" label="Prazo da reserva (dias)" :error="form.errors['dados.prazo_reserva_dias']" />
          <FormSelect v-model="form.dados.instrumento" label="Instrumento" :options="INSTRUMENTOS" required :error="form.errors['dados.instrumento']" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <FormField v-model="form.dados.base_normativa" label="Base normativa" placeholder="Resolução CEDEC nº ..., art. ..." required :error="form.errors['dados.base_normativa']" />
          <FormField v-model="form.dados.unidade_responsavel" label="Unidade responsável pela entrega" required :error="form.errors['dados.unidade_responsavel']" />
        </div>
        <FormTextarea v-model="documentos" label="Documentos exigidos" :rows="3" hint="Um por linha." :error="form.errors['dados.documentos_exigidos']" />
        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
          <input v-model="form.dados.demonstracao" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          Item de demonstração (nunca resgatável)
        </label>
      </template>

      <FormTextarea v-model="form.justificativa" label="Justificativa" :rows="3" required hint="Por que esta mudança; fica no histórico." :error="form.errors.justificativa" />
      <p v-if="form.errors.catalogo" role="alert" class="text-sm font-semibold text-red-600 dark:text-red-400">{{ form.errors.catalogo }}</p>

      <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
        <Link :href="route('resgate.catalogo')" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancelar</Link>
        <Button type="submit" variant="primary" :loading="form.processing">Enviar para aprovação</Button>
      </div>
    </form>
  </section>
</template>

<script setup>
/**
 * Formulario de proposta do catalogo (quatro olhos), usado na pagina propria
 * Resgate/PropostaNova. Sem item: novo item. Com item: nova versao
 * (pre-preenchida com a vigente) ou encerramento. A publicacao so acontece
 * na aprovacao por outra pessoa; o backend redireciona para a fila.
 */
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import { BENEFICIARIOS, FAIXAS_OPCOES, INSTRUMENTOS } from '@/Support/resgateCatalogo';

const props = defineProps({
  item: { type: Object, default: null },
  tipos: { type: Array, default: () => [] },
});

const acoesDoItem = [
  { value: 'nova_versao', label: 'Nova versão' },
  { value: 'encerrar', label: 'Encerrar item' },
];

const CAMPOS = ['tipo', 'titulo', 'descricao', 'beneficiario', 'faixa_minima', 'custo_pontos', 'quantidade',
  'limite_por_ente_temporada', 'prazo_reserva_dias', 'instrumento', 'base_normativa', 'unidade_responsavel', 'demonstracao'];

function dadosIniciais(item) {
  const vazio = { tipo: 'servico', beneficiario: 'municipio', faixa_minima: 'bronze', custo_pontos: 0, prazo_reserva_dias: 30, demonstracao: false };
  return item ? Object.fromEntries(CAMPOS.map((campo) => [campo, item[campo] ?? null])) : vazio;
}

const form = useForm({
  acao: props.item ? 'nova_versao' : 'criar',
  codigo: props.item?.codigo ?? '',
  justificativa: '',
  dados: dadosIniciais(props.item),
});
const documentos = ref((props.item?.documentos_exigidos ?? []).join('\n'));

const vazioParaNulo = (valor) => (valor === '' || valor === undefined ? null : valor);

function enviar() {
  form.transform((dados) => ({
    acao: dados.acao,
    codigo: dados.codigo,
    justificativa: dados.justificativa,
    ...(dados.acao === 'encerrar' ? {} : {
      dados: {
        ...dados.dados,
        quantidade: dados.dados.tipo === 'bem_permanente' ? null : vazioParaNulo(dados.dados.quantidade),
        limite_por_ente_temporada: vazioParaNulo(dados.dados.limite_por_ente_temporada),
        prazo_reserva_dias: vazioParaNulo(dados.dados.prazo_reserva_dias),
        demonstracao: Boolean(dados.dados.demonstracao),
        documentos_exigidos: documentos.value.split('\n').map((linha) => linha.trim()).filter(Boolean),
      },
    }),
  })).post(route('resgate.catalogo.propor'), { preserveScroll: true });
}
</script>
