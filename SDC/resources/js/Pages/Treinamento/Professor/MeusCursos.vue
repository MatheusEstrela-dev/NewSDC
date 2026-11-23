<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import CursoEadCard from '@/Components/Molecules/Treinamento/CursoEadCard.vue';
import CapaCursoUpload from '@/Components/Molecules/Treinamento/CapaCursoUpload.vue';
import AcoesCicloCurso from '@/Components/Organisms/Treinamento/Professor/AcoesCicloCurso.vue';
import PastasDeCategoria from '@/Components/Organisms/Treinamento/PastasDeCategoria.vue';
import CabecalhoDaPasta from '@/Components/Molecules/Treinamento/CabecalhoDaPasta.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useCursosAoVivo } from '@/Composables/treinamento/useCursosAoVivo';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  cursos: { type: Array, default: () => [] },
  // So quem ja cria treinamento (admin) abre curso; o backend recusa os demais.
  pode_abrir: { type: Boolean, default: false },
  // Pastas das categorias de oferta (contagem de todos os cursos da tela).
  categorias: { type: Array, default: () => [] },
  // Gestao (cronograma, vagas, periodo) dos cursos que nao sao EAD: so quem acessa a Area de gestao.
  pode_gestao: { type: Boolean, default: false },
  // Quem administra cursos troca a foto das pastas.
  pode_editar_capa: { type: Boolean, default: false },
});

useCursosAoVivo(['cursos', 'pode_abrir', 'categorias'], { professor: true });

const categoriaAtiva = computed(() => props.categorias.find((c) => c.ativa) ?? null);

// Abrir/fechar a pasta e navegar (?categoria=), sem replace: o "voltar" do navegador retorna as pastas.
function abrirPasta(valor) {
  router.get(route('treinamentos.professor.index'), valor ? { categoria: valor } : {}, {
    preserveState: true,
    preserveScroll: false,
    only: ['cursos', 'categorias'],
  });
}

// Periodo do curso presencial/evento (datas YYYY-MM-DD, sem fuso: formata sem passar por Date).
const dataBr = (iso) => (iso ? iso.split('-').reverse().join('/') : '');

function periodoDe(curso) {
  const inicio = dataBr(curso.data_inicio);
  const fim = dataBr(curso.data_fim);
  if (inicio && fim && inicio !== fim) return `${inicio} a ${fim}`;
  return inicio || fim;
}

// A impressao e uma pagina Blade, fora do Inertia: abre em aba propria.
function abrirCertificado(url) {
  window.open(url, '_blank', 'noopener');
}
</script>

<template>
  <div class="pb-8">
    <PageHeader
      title="Meus cursos"
      :description="pode_abrir ? 'Todos os cursos e eventos' : 'Cursos em que você foi designado como professor'"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    >
      <template v-if="pode_abrir" #actions>
        <Button size="sm" :href="route('treinamentos.create', { categoria: 'CURSO', tipo: 'EAD' })">Abrir curso</Button>
      </template>
    </PageHeader>

    <PastasDeCategoria v-if="!categoriaAtiva" class="mt-6" :categorias="categorias" :pode-editar-capa="pode_editar_capa" @abrir="abrirPasta" />

    <section v-else class="mt-6">
      <CabecalhoDaPasta :categoria="categoriaAtiva" @voltar="abrirPasta(null)" />

      <CardBase v-if="cursos.length === 0">
        <Text color="muted">Nenhuma capacitação nesta categoria ainda.</Text>
      </CardBase>

      <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        <CursoEadCard v-for="curso in cursos" :key="curso.id" :curso="curso" modo="professor">
          <template v-if="curso.eh_ead" #capa-acoes>
            <CapaCursoUpload :curso="curso" />
          </template>

          <template #corpo>
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
              <Badge v-if="!curso.eh_ead" variant="info">{{ curso.tipo_label }}</Badge>
              <span>Público: {{ curso.publico_alvo_label }}</span>
              <span v-if="!curso.eh_ead && periodoDe(curso)">{{ periodoDe(curso) }}</span>
              <span v-if="!curso.eh_ead && curso.local" class="min-w-0 break-words">{{ curso.local }}</span>
              <span>{{ curso.alunos }} {{ curso.eh_ead ? 'aluno(s)' : 'inscrito(s)' }}</span>
              <Badge v-if="curso.eh_ead && curso.designado === false" variant="warning">Sem professor designado</Badge>
            </div>
          </template>

          <template #rodape>
            <div v-if="!curso.eh_ead" class="flex flex-wrap gap-2">
              <Button size="sm" :href="curso.url_abrir">Abrir</Button>
              <Button size="sm" variant="outline" :href="curso.url_inscritos">Inscritos</Button>
              <Button v-if="pode_gestao" size="sm" variant="outline" :href="curso.url_gestao">Gestão</Button>
            </div>
            <div v-else class="flex flex-wrap gap-2">
              <!-- Sem "Previa" aqui: ela ja fica no menu do curso (Estrutura da turma). -->
              <Button size="sm" :href="route('treinamentos.professor.conteudo', curso.id)">Conteúdo</Button>
              <AcoesCicloCurso :curso="curso" />
              <Button v-if="curso.certificado_instrutor_url" size="sm" variant="secondary" @click="abrirCertificado(curso.certificado_instrutor_url)">
                Certificado de instrutor
              </Button>
            </div>
          </template>
        </CursoEadCard>
      </div>
    </section>
  </div>
</template>
