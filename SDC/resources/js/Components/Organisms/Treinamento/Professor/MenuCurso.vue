<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
  AdjustmentsHorizontalIcon, ClipboardDocumentListIcon, DocumentCheckIcon, EyeIcon, FolderIcon, UserGroupIcon,
} from '@heroicons/vue/24/outline';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { useMobile } from '@/Composables/useMobile';
import { useToast } from '@/Composables/useToast';
import { usePermissions } from '@/Composables/usePermissions';
import { pendenciasDe } from '@/Composables/treinamento/useProvaApi';

/**
 * Menu lateral do curso na Area do Gestor (rascunho 3, P1). Compartilhado
 * pelas abas do curso: so muda a entrada ativa. RN03 separa dois grupos: a
 * estrutura da turma (o que se monta: conteudo, provas, correcoes, previa) e
 * a gestao da turma (inscritos e desenho operacional: vagas, periodo,
 * cronograma), com a Gestao so para quem abre a gestao.
 *
 * Mesmo visual da pasta do protocolo PAE (PaeProtocoloPasta): cartao com
 * titulo por grupo, icone em cada tela e a tela aberta em azul cheio; no
 * celular, faixa rolavel. Publicar nao e uma tela: fica num bloco proprio de
 * situacao, fora da lista, para nao ser confundido com uma aba.
 */

// Rotulos dos grupos centralizados: "Estrutura da turma" e provisorio (Ponto
// em Aberto 3 de issue_regras.md).
const ROTULO_GRUPO_ESTRUTURA = 'Estrutura da turma';
const ROTULO_GRUPO_GESTAO = 'Gestão da turma';

const props = defineProps({
  curso: { type: Object, required: true },
  ativo: { type: String, required: true },
  pendentes: { type: Number, default: 0 },
});

const { can } = usePermissions();
const { isMobile } = useMobile();

// `dica` vira o title de cada tela: diz o que ha nela antes do clique.
const grupos = computed(() => [
  {
    titulo: ROTULO_GRUPO_ESTRUTURA,
    entradas: [
      { chave: 'conteudo', rotulo: 'Conteúdo', icone: FolderIcon, dica: 'Pastas e itens do curso, na ordem que o aluno vê', href: route('treinamentos.professor.conteudo', props.curso.id) },
      { chave: 'provas', rotulo: 'Provas', icone: ClipboardDocumentListIcon, dica: 'Questões e configuração das provas de cada pasta', href: route('treinamentos.professor.provas', props.curso.id) },
      { chave: 'correcoes', rotulo: 'Correções', icone: DocumentCheckIcon, dica: 'Entregas de projeto aguardando correção', href: route('treinamentos.professor.correcoes', props.curso.id), contador: props.pendentes },
      { chave: 'previa', rotulo: 'Prévia', icone: EyeIcon, dica: 'Ver o curso como o aluno vê', href: route('treinamentos.professor.previa', props.curso.id) },
    ],
  },
  {
    titulo: ROTULO_GRUPO_GESTAO,
    entradas: [
      { chave: 'alunos', rotulo: 'Inscritos', icone: UserGroupIcon, dica: 'Andamento dos alunos aprovados no curso', href: route('treinamentos.professor.alunos', props.curso.id) },
      ...(can('treinamento.gestor.acessar')
        ? [{ chave: 'gestao', rotulo: 'Gestão', icone: AdjustmentsHorizontalIcon, dica: 'Vagas, período, cronograma e histórico do curso', href: route('treinamentos.professor.gestao', props.curso.id) }]
        : []),
    ],
  },
]);

const tituloGrupoClasses = 'px-3 pb-2 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400';

function classeEntrada(chave) {
  return chave === props.ativo
    ? 'bg-blue-600 text-white shadow-sm'
    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-700/60 dark:hover:text-white';
}

// O Conteudo tem o proprio "Publicar curso" ao lado das pastas: aqui o botao
// so aparece nas outras abas, para a tela nunca ter dois botoes iguais.
const mostrarPublicar = computed(() => !props.curso.publicado && props.ativo !== 'conteudo');

const { show: toast } = useToast();

const urlPublicar = () => route('treinamentos.professor.publicar', props.curso.id);

const urlConteudo = () => route('treinamentos.professor.conteudo', props.curso.id);

// Versao > 1 exige o "O que mudou": o modal vive no Conteudo, que le ?publicar=1 ao abrir.
function pedirNotasNoConteudo() {
  router.visit(urlConteudo(), { data: { publicar: 1 } });
}

// As pendencias so chegam na resposta do publicar e somem ao navegar: fora do
// Conteudo, e so quando o erro traz pendencias, leva o professor para la e repete a
// tentativa uma unica vez (bloqueada de novo) para a lista aparecer onde e exibida.
function publicar() {
  // Nem toda aba conhece a versao: nela, o erro de versao_notas faz o mesmo desvio abaixo.
  if ((props.curso.versao ?? 1) > 1) {
    pedirNotasNoConteudo();

    return;
  }

  router.post(urlPublicar(), {}, {
    preserveScroll: true,
    onError: (erros) => {
      if (erros?.versao_notas) {
        pedirNotasNoConteudo();

        return;
      }

      if (pendenciasDe(erros).length === 0) {
        toast(erros?.publicar ?? 'Não foi possível publicar o curso.', 'error');

        return;
      }

      toast('O curso não pode ser publicado ainda. Veja as pendências na aba Conteúdo.', 'error');
      if (props.ativo === 'conteudo') return;

      router.visit(urlConteudo(), {
        onSuccess: () => router.post(urlPublicar(), {}, { preserveScroll: true }),
      });
    },
  });
}
</script>

<template>
  <nav aria-label="Menu do curso" class="min-w-0">
    <div :class="isMobile ? 'space-y-2' : 'sticky top-4 rounded-xl border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800/50'">
      <!-- Celular: as telas viram uma faixa rolavel; quem transborda rola dentro de si. -->
      <div v-if="isMobile" class="min-w-0 overflow-x-auto rounded-xl bg-slate-100 p-1.5 [scrollbar-width:none] dark:bg-slate-800/30 [&::-webkit-scrollbar]:hidden">
        <ul class="flex w-max items-center gap-1">
          <template v-for="(grupo, indice) in grupos" :key="grupo.titulo">
            <li v-if="indice > 0" class="mx-1 h-5 w-px bg-slate-300 dark:bg-slate-600" aria-hidden="true" />
            <li v-for="entrada in grupo.entradas" :key="entrada.chave">
              <Link
                :href="entrada.href"
                class="flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                :class="classeEntrada(entrada.chave)"
                :aria-current="entrada.chave === ativo ? 'page' : undefined"
                :title="entrada.dica"
              >
                <component :is="entrada.icone" class="h-4 w-4 flex-shrink-0" />
                {{ entrada.rotulo }}
                <Badge v-if="entrada.contador > 0" variant="warning" size="sm">{{ entrada.contador }}</Badge>
              </Link>
            </li>
          </template>
        </ul>
      </div>

      <!-- Desktop: cartao lateral com um titulo por grupo, como a pasta do protocolo PAE. -->
      <template v-else>
        <template v-for="(grupo, indice) in grupos" :key="grupo.titulo">
          <p :class="[tituloGrupoClasses, indice > 0 ? 'mt-3 border-t border-slate-200 pt-3 dark:border-slate-700' : 'pt-1']">
            {{ grupo.titulo }}
          </p>
          <ul class="space-y-0.5">
            <li v-for="entrada in grupo.entradas" :key="entrada.chave">
              <Link
                :href="entrada.href"
                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                :class="classeEntrada(entrada.chave)"
                :aria-current="entrada.chave === ativo ? 'page' : undefined"
                :title="entrada.dica"
              >
                <component :is="entrada.icone" class="h-4 w-4 flex-shrink-0" />
                <span class="min-w-0 flex-1 truncate">{{ entrada.rotulo }}</span>
                <Badge v-if="entrada.contador > 0" variant="warning" size="sm">{{ entrada.contador }}</Badge>
              </Link>
            </li>
          </ul>
        </template>
      </template>

      <!-- Situacao do curso: estado e acao, fora da lista de telas. -->
      <div :class="isMobile ? 'flex flex-wrap items-center justify-between gap-2 px-1' : 'mt-3 border-t border-slate-200 pb-1 pt-3 dark:border-slate-700'">
        <p v-if="!isMobile" :class="tituloGrupoClasses">Situação do curso</p>
        <div :class="isMobile ? 'flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1' : 'space-y-1.5 px-3'">
          <Badge :variant="curso.publicado ? 'success' : 'warning'" size="sm">{{ curso.publicado ? 'Publicado' : 'Rascunho' }}</Badge>
          <p v-if="!curso.publicado" class="text-xs leading-snug text-slate-500 dark:text-slate-400">
            Os alunos só veem esta versão depois de publicada.
          </p>
        </div>
        <div v-if="mostrarPublicar" :class="isMobile ? '' : 'mt-3'">
          <Button size="sm" variant="success" :full-width="!isMobile" @click="publicar">Publicar curso</Button>
        </div>
      </div>
    </div>
  </nav>
</template>
