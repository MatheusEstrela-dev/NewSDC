<script setup>
/**
 * Um par rotulo/valor em leitura, com acao rapida quando o valor e contato.
 *
 * E-mail vira mailto: e telefone vira tel:, porque quem consulta este cadastro
 * geralmente esta prestes a ligar ou escrever. O botao de copiar aparece ao passar o
 * mouse e some quando nao ha valor.
 *
 * MOLECULA que copia: diferente do ContatoBloco, aqui nao ha organismo coordenando
 * varios irmaos -- cada campo e independente e o feedback e so dele. Manter o estado
 * local e o que evita a pagina virar um controlador de N campos.
 */
import { ref } from 'vue';
import { CheckIcon, ClipboardDocumentIcon } from '@heroicons/vue/24/outline';
import { useCopiarTexto } from '@/Composables/useCopiarTexto';

const props = defineProps({
  rotulo: { type: String, required: true },
  valor: { type: [String, Number], default: null },
  /** 'texto' | 'email' | 'tel' */
  tipo: { type: String, default: 'texto' },
});

const { copiar } = useCopiarTexto();
const copiado = ref(false);

const temValor = () => props.valor !== null && props.valor !== undefined && String(props.valor).trim() !== '';

function href() {
  if (!temValor()) return null;
  if (props.tipo === 'email') return `mailto:${props.valor}`;
  // tel: nao aceita espaco, parenteses nem hifen -- o legado grava tudo isso.
  if (props.tipo === 'tel') return `tel:${String(props.valor).replace(/[^\d+]/g, '')}`;

  return null;
}

async function copiarValor() {
  if (!temValor()) return;

  const ok = await copiar(String(props.valor));

  if (!ok) return;

  copiado.value = true;
  setTimeout(() => { copiado.value = false; }, 2000);
}
</script>

<template>
  <div class="group min-w-0">
    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
      {{ rotulo }}
    </dt>
    <dd class="mt-0.5 flex min-w-0 items-center gap-2">
      <span v-if="!temValor()" class="text-sm text-slate-400 dark:text-slate-500">—</span>

      <a
        v-else-if="href()"
        :href="href()"
        class="min-w-0 break-words text-sm font-medium text-cyan-700 underline-offset-2 hover:underline dark:text-cyan-300 [overflow-wrap:anywhere]"
      >
        {{ valor }}
      </a>

      <span v-else class="min-w-0 break-words text-sm font-medium text-slate-900 dark:text-slate-100 [overflow-wrap:anywhere]">
        {{ valor }}
      </span>

      <button
        v-if="temValor()"
        type="button"
        class="shrink-0 rounded p-1 text-slate-400 opacity-0 transition-opacity hover:bg-slate-100 hover:text-slate-600 focus-visible:opacity-100 group-hover:opacity-100 dark:hover:bg-slate-800 dark:hover:text-slate-200"
        :title="`Copiar ${rotulo.toLowerCase()}`"
        @click="copiarValor"
      >
        <component :is="copiado ? CheckIcon : ClipboardDocumentIcon" class="h-3.5 w-3.5" />
      </button>
    </dd>
  </div>
</template>
