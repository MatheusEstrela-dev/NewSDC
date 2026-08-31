<template>
  <!--
    Pagina PUBLICA: a rota sai do grupo de auth com withoutMiddleware, porque e
    lida por quem escaneia o QR Code colado na cisterna. Por isso NAO usa o
    AuthenticatedLayout -- nao ha sidebar, nao ha usuario, e quem abre pode nao
    ter conta nenhuma.
  -->
  <div class="min-h-screen bg-slate-100 px-4 py-8 dark:bg-slate-950">
    <Head :title="`Cisterna Nº ${numero_instalacao}`" />

    <div class="mx-auto max-w-md">
      <header class="mb-4 flex items-center gap-3">
        <img :src="moduleIcon('cisternas')" alt="" class="h-12 w-12 object-contain">
        <div>
          <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Programa de Cisternas</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400">CEDEC — Defesa Civil de Minas Gerais</p>
        </div>
      </header>

      <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900">
        <div class="border-b border-slate-200 bg-blue-600 px-5 py-4 text-center dark:border-slate-700/50">
          <p class="text-xs font-medium uppercase tracking-wide text-blue-100">Numero de instalacao</p>
          <p class="font-mono text-3xl font-bold text-white">{{ numero_instalacao }}</p>
        </div>

        <!--
          Somente dados de LOCALIZACAO da instalacao.
          Nome, CPF, renda e criterios sociais ficam DE FORA de proposito: a
          pagina e aberta sem login, e o adesivo esta colado no imovel a vista de
          qualquer um. A ficha do legado tambem so mostrava localizacao, entao
          este recorte e a porta fiel e a escolha segura ao mesmo tempo.
        -->
        <dl class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <div v-for="item in itens" :key="item.rotulo" class="flex gap-3 px-5 py-3">
            <dt class="w-32 shrink-0 text-xs text-slate-500 dark:text-slate-400">{{ item.rotulo }}</dt>
            <dd class="flex-1 text-sm text-slate-800 dark:text-slate-100">{{ item.valor }}</dd>
          </div>
        </dl>
      </section>

      <p class="mt-4 text-center text-xs text-slate-400">
        Consulta publica da instalacao. Duvidas: procure a Defesa Civil do seu municipio.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { moduleIcon } from '@/Support/moduleIcons';

const props = defineProps({
  // snake_case: chave exata do controller. Em camelCase a prop vinha undefined,
  // e o numero -- que e o conteudo principal da ficha -- renderizava vazio.
  numero_instalacao: { type: [String, Number], required: true },
  beneficiario: { type: Object, required: true },
  instalada_em: { type: String, default: null },
});

/**
 * Campo vazio e omitido: a ficha e pequena e uma lista de tracos nao ajuda quem
 * esta no campo com o celular na mao.
 *
 * `municipio` e `comunidade` vem carregados pelo QrCodeService; os demais campos
 * saem das colunas do proprio cadastro.
 */
const itens = computed(() => {
  const b = props.beneficiario ?? {};
  const municipio = b.municipio
    ? [b.municipio.nome, b.municipio.uf].filter(Boolean).join(' / ')
    : null;

  const coordenada = b.latitude && b.longitude ? `${b.latitude}, ${b.longitude}` : null;

  return [
    { rotulo: 'Municipio', valor: municipio },
    { rotulo: 'Comunidade', valor: b.comunidade?.nome },
    { rotulo: 'Endereco', valor: b.endereco },
    { rotulo: 'Coordenada', valor: coordenada },
    { rotulo: 'Instalada em', valor: dataBr(props.instalada_em) },
  ].filter((i) => i.valor !== null && i.valor !== undefined && i.valor !== '');
});

function dataBr(iso) {
  if (!iso) return null;

  const [ano, mes, dia] = String(iso).slice(0, 10).split('-');

  return dia ? `${dia}/${mes}/${ano}` : iso;
}
</script>
