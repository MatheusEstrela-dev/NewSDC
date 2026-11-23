<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import CertificadoItem from '@/Components/Molecules/Treinamento/CertificadoItem.vue';
import AvaliacaoCursoCard from '@/Components/Organisms/Treinamento/AvaliacaoCursoCard.vue';
import InscricaoSexoModal from '@/Components/Organisms/Treinamento/InscricaoSexoModal.vue';
import PastasDeCategoria from '@/Components/Organisms/Treinamento/PastasDeCategoria.vue';
import CabecalhoDaPasta from '@/Components/Molecules/Treinamento/CabecalhoDaPasta.vue';
import CursoEadCard from '@/Components/Molecules/Treinamento/CursoEadCard.vue';
import SecaoRecolhivel from '@/Components/Molecules/Treinamento/SecaoRecolhivel.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import CredencialQrBloco from '@/Components/Organisms/Treinamento/CredencialQrBloco.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useToast } from '@/Composables/useToast';
import { useCursosAoVivo } from '@/Composables/treinamento/useCursosAoVivo';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  sexo_padrao: { type: String, default: null },
  data_nascimento_padrao: { type: String, default: null },
  /** Ate 3 cursos aprovados e nao concluidos, do mais recente (so na raiz). */
  continuar: { type: Array, default: () => [] },
  meus_cursos: { type: Array, default: () => [] },
  disponiveis: { type: Array, default: () => [] },
  certificados: { type: Array, default: () => [] },
  credencial: { type: Object, default: null },
  /** Eventos (sem sala) com os requisitos cumpridos: falta so avaliar para concluir. */
  avaliacoes_pendentes: { type: Array, default: () => [] },
  notas_avaliacao: { type: Array, default: () => [] },
  notas_relevancia: { type: Array, default: () => [] },
  // Pastas das categorias (cursos do aluno + disponiveis); `resumo` detalha cada pasta.
  categorias: { type: Array, default: () => [] },
});

useCursosAoVivo(['continuar', 'meus_cursos', 'disponiveis', 'categorias', 'certificados', 'avaliacoes_pendentes', 'credencial']);

const categoriaAtiva = computed(() => props.categorias.find((c) => c.ativa) ?? null);

// Abrir/fechar a pasta e navegar (?categoria=), sem replace: o "voltar" do navegador retorna as pastas.
function abrirPasta(valor) {
  router.get(route('treinamentos.aluno.index'), valor ? { categoria: valor } : {}, {
    preserveState: true,
    preserveScroll: false,
    only: ['continuar', 'meus_cursos', 'disponiveis', 'categorias', 'avaliacoes_pendentes', 'certificados', 'credencial'],
  });
}

const { show: toast } = useToast();
const inscrevendo = ref(null);

const atualizando = ref(null);
const cursoEscolhido = ref(null);

function atualizar(curso) {
  atualizando.value = curso.id;
  router.post(curso.nova_versao.atualizar_url, {}, {
    onFinish: () => { atualizando.value = null; },
  });
}

// O modal fica aberto ate o sucesso (o que foi digitado nao se perde); o sucesso aparece no flash do servidor.
function inscrever(perfil) {
  const curso = cursoEscolhido.value;
  inscrevendo.value = curso.id;
  router.post(route('treinamentos.inscrever', curso.id), perfil, {
    preserveScroll: true,
    onSuccess: () => { cursoEscolhido.value = null; },
    onError: (errors) => toast(Object.values(errors)[0] || 'Não foi possível concluir a inscrição.', 'error'),
    onFinish: () => { inscrevendo.value = null; },
  });
}
</script>

<template>
  <div class="pb-8">
    <PageHeader
      title="Meus cursos"
      description="Seus cursos EAD e os abertos para inscrição, organizados por categoria"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <SecaoRecolhivel
      v-if="!categoriaAtiva && avaliacoes_pendentes.length"
      class="mt-6"
      titulo="Avalie o curso para concluir"
      :total="avaliacoes_pendentes.length"
      namespace="treinamento-aluno"
      secao-id="avaliacoes-pendentes"
    >
      <div class="space-y-4">
        <AvaliacaoCursoCard
          v-for="pendente in avaliacoes_pendentes"
          :key="pendente.inscricao_id"
          :url="pendente.url_avaliar"
          :notas="notas_avaliacao"
          :relevancias="notas_relevancia"
          :titulo="pendente.titulo"
          :subtitulo="`${pendente.tipo_label} · Você cumpriu os requisitos. Avalie o curso para concluí-lo e receber o certificado.`"
        />
      </div>
    </SecaoRecolhivel>

    <template v-if="!categoriaAtiva">
      <section v-if="continuar.length" class="mt-6" aria-labelledby="continuar-titulo">
        <h2 id="continuar-titulo" class="mb-3 text-lg font-semibold text-slate-800 dark:text-slate-100">Continuar de onde parou</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
          <CardBase v-for="curso in continuar" :key="curso.id" class="flex flex-col gap-3 p-4">
            <Text class="font-medium">{{ curso.titulo }}</Text>
            <div class="flex items-center gap-2">
              <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" role="progressbar" :aria-valuenow="Math.round(curso.percentual)" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${curso.percentual}%` }" />
              </div>
              <Text size="sm" color="muted">{{ Math.round(curso.percentual) }}%</Text>
            </div>
            <Button v-if="curso.sala_url" :href="curso.sala_url" variant="primary" class="self-start">Continuar</Button>
          </CardBase>
        </div>
      </section>

      <PastasDeCategoria class="mt-6" :categorias="categorias" :desabilitar-vazias="true" @abrir="abrirPasta" />

      <SecaoRecolhivel v-if="credencial" class="mt-8" titulo="Minha credencial" namespace="treinamento-aluno" secao-id="credencial">
        <CredencialQrBloco :credencial="credencial" />
      </SecaoRecolhivel>

      <SecaoRecolhivel v-if="certificados.length" class="mt-8" titulo="Meus certificados" :total="certificados.length" namespace="treinamento-aluno" secao-id="certificados">
        <div class="space-y-4">
          <CertificadoItem
            v-for="certificado in certificados"
            :key="certificado.id"
            :certificado="certificado"
            :imprimir-url="route('treinamentos.certificados.imprimir', certificado.id)"
          />
        </div>
      </SecaoRecolhivel>
    </template>

    <section v-else class="mt-6">
      <CabecalhoDaPasta :categoria="categoriaAtiva" @voltar="abrirPasta(null)" />

      <SecaoRecolhivel titulo="Minhas capacitações nesta categoria" :total="meus_cursos.length" namespace="treinamento-aluno" secao-id="meus-cursos">
        <CardBase v-if="meus_cursos.length === 0">
          <Text color="muted">Você ainda não tem capacitações nesta categoria.</Text>
        </CardBase>

        <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
          <CursoEadCard
            v-for="curso in meus_cursos"
            :key="curso.id"
            :curso="curso"
            modo="inscrito"
            :atualizando="atualizando === curso.id"
            @atualizar="atualizar"
          />
        </div>
      </SecaoRecolhivel>

      <SecaoRecolhivel class="mt-8" titulo="Disponíveis para inscrição" :total="disponiveis.length" namespace="treinamento-aluno" secao-id="disponiveis">
        <CardBase v-if="disponiveis.length === 0">
          <Text color="muted">Nenhuma capacitação aberta para inscrição nesta categoria.</Text>
        </CardBase>

        <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
          <CursoEadCard
            v-for="curso in disponiveis"
            :key="curso.id"
            :curso="curso"
            modo="disponivel"
            :loading="inscrevendo === curso.id"
            @inscrever="cursoEscolhido = $event"
          />
        </div>
      </SecaoRecolhivel>
    </section>

    <InscricaoSexoModal
      :show="cursoEscolhido !== null"
      :processando="inscrevendo !== null"
      :titulo="cursoEscolhido?.titulo"
      :padrao="sexo_padrao"
      :data-nascimento-padrao="data_nascimento_padrao"
      @fechar="cursoEscolhido = null"
      @confirmar="inscrever"
    />
  </div>
</template>
