<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import CertificadoItem from '@/Components/Molecules/Treinamento/CertificadoItem.vue';
import AvaliacaoCursoCard from '@/Components/Organisms/Treinamento/AvaliacaoCursoCard.vue';
import InscricaoSexoModal from '@/Components/Organisms/Treinamento/InscricaoSexoModal.vue';
import CursoEadCard from '@/Components/Molecules/Treinamento/CursoEadCard.vue';
import SecaoRecolhivel from '@/Components/Molecules/Treinamento/SecaoRecolhivel.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import CredencialQrBloco from '@/Components/Organisms/Treinamento/CredencialQrBloco.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useToast } from '@/Composables/useToast';
import { useCursosAoVivo } from '@/Composables/treinamento/useCursosAoVivo';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  sexo_padrao: { type: String, default: null },
  data_nascimento_padrao: { type: String, default: null },
  meus_cursos: { type: Array, default: () => [] },
  disponiveis: { type: Array, default: () => [] },
  certificados: { type: Array, default: () => [] },
  credencial: { type: Object, default: null },
  /** Eventos (sem sala) com os requisitos cumpridos: falta so avaliar para concluir. */
  avaliacoes_pendentes: { type: Array, default: () => [] },
  notas_avaliacao: { type: Array, default: () => [] },
});

useCursosAoVivo(['meus_cursos', 'disponiveis', 'certificados', 'avaliacoes_pendentes']);

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

function inscrever(perfil) {
  const curso = cursoEscolhido.value;
  inscrevendo.value = curso.id;
  cursoEscolhido.value = null;
  router.post(route('treinamentos.inscrever', curso.id), perfil, {
    preserveScroll: true,
    onSuccess: () => toast('Inscrição realizada.', 'success'),
    onError: (errors) => toast(errors.inscricao || 'Não foi possível concluir a inscrição.', 'error'),
    onFinish: () => { inscrevendo.value = null; },
  });
}
</script>

<template>
  <div>
    <PageHeader
      title="Meus cursos"
      description="Cursos EAD em que você está inscrito e cursos abertos para inscrição"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <SecaoRecolhivel
      v-if="avaliacoes_pendentes.length"
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
          :titulo="pendente.titulo"
          :subtitulo="`${pendente.tipo_label} · Você cumpriu os requisitos. Avalie o curso para concluí-lo e receber o certificado.`"
        />
      </div>
    </SecaoRecolhivel>

    <SecaoRecolhivel class="mt-6" titulo="Meus cursos" :total="meus_cursos.length" namespace="treinamento-aluno" secao-id="meus-cursos">

      <CardBase v-if="meus_cursos.length === 0">
        <Text color="muted">Você ainda não está inscrito em nenhum curso EAD.</Text>
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

    <SecaoRecolhivel class="mt-8" titulo="Cursos disponíveis" :total="disponiveis.length" namespace="treinamento-aluno" secao-id="disponiveis">

      <CardBase v-if="disponiveis.length === 0">
        <Text color="muted">Nenhum curso aberto para inscrição no momento.</Text>
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

    <InscricaoSexoModal
      :show="cursoEscolhido !== null"
      :titulo="cursoEscolhido?.titulo"
      :padrao="sexo_padrao"
      :data-nascimento-padrao="data_nascimento_padrao"
      @fechar="cursoEscolhido = null"
      @confirmar="inscrever"
    />
  </div>
</template>
