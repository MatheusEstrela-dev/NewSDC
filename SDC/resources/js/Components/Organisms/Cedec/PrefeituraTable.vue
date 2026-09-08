<template>
  <!--
    Tabela a partir de lg; abaixo disso, BLOCO. O corte e o mesmo da sidebar e do
    resto do sistema: useMobile().isDesktop le matchMedia (min-width: 1024px), a
    MESMA medida das media queries do Tailwind. Decidir por window.innerWidth
    divergiria da largura da barra de rolagem justamente na borda.

    Nao se usa aqui o Organisms/Table/ResponsiveTable.vue: ele corta em md (768px) e
    carrega style scoped -- as duas coisas que esta fase nao pode ter.
  -->
  <div
    v-if="isDesktop"
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
  >
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-900/50">
          <tr>
            <th scope="col" :class="TH">Município</th>
            <th scope="col" :class="TH">REDEC</th>
            <th scope="col" :class="TH">Prefeito</th>
            <th scope="col" :class="TH">E-mail institucional</th>
            <th scope="col" :class="TH">Telefone</th>
            <th scope="col" :class="TH">Foto</th>
            <th scope="col" class="table-actions-head w-20 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              Ações
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <tr
            v-for="p in prefeituras"
            :key="p.municipio_id"
            class="table-row-solid transition-colors"
          >
            <td :class="TD_FORTE">{{ p.municipio_nome }}</td>
            <td :class="TD">{{ p.redec ?? '—' }}</td>
            <td :class="TD">{{ p.prefeito_nome ?? '—' }}</td>

            <!--
              O e-mail institucional passa de 40 caracteres em boa parte das 853
              linhas e e ele que empurrava a tabela. Receita de tres partes:
              container com min-w-0 e largura maxima, overflow-x-auto nele, e w-max
              no filho -- sem o w-max o span se comprime em vez de rolar. A celula
              rola dentro de si; a pagina, nao.
            -->
            <td class="px-3 py-2 text-sm text-slate-700 dark:text-slate-200">
              <div class="min-w-0 max-w-[18rem] overflow-x-auto">
                <span class="block w-max whitespace-nowrap">{{ p.email_prefeitura ?? '—' }}</span>
              </div>
            </td>

            <td :class="TD">{{ p.tel_prefeitura ?? '—' }}</td>

            <td :class="TD">
              <span :class="p.tem_foto ? PILULA_SIM : PILULA_NAO">
                {{ p.tem_foto ? 'Sim' : 'Não' }}
              </span>
            </td>

            <!-- Coluna fixa no canto direito. Depende de .table-row-solid na tr
                 para o fundo opaco. -->
            <td class="table-actions-cell w-20 whitespace-nowrap px-3 py-2 text-right">
              <div class="flex items-center justify-end">
                <ActionButton
                  action="view"
                  module="cedec"
                  resource="prefeituras"
                  :allowed="true"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Ver dados da prefeitura"
                  @click="abrir(p.municipio_id)"
                />
              </div>
            </td>
          </tr>

          <tr v-if="prefeituras.length === 0">
            <td colspan="7" class="px-3 py-10">
              <ListEmptyState
                :icon="BuildingOffice2Icon"
                title="Nenhum município encontrado"
                helper="Ajuste a busca, a REDEC ou a macrorregião, ou use Limpar para ver a lista inteira."
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Abaixo de lg: um bloco por municipio. Titulo, pares rotulo/valor e a acao no
       pe -- o mesmo desenho de card do RAT, que e a referencia. -->
  <div v-else class="space-y-3">
    <article
      v-for="p in prefeituras"
      :key="p.municipio_id"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <header class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0">
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">
            {{ p.municipio_nome }}
          </h3>
          <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
            REDEC: {{ p.redec ?? '—' }}
          </p>
        </div>
        <span :class="[p.tem_foto ? PILULA_SIM : PILULA_NAO, 'shrink-0']">
          {{ p.tem_foto ? 'Com foto' : 'Sem foto' }}
        </span>
      </header>

      <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2">
        <div class="min-w-0">
          <dt :class="DT">Prefeito</dt>
          <dd :class="DD" class="truncate">{{ p.prefeito_nome ?? '—' }}</dd>
        </div>
        <div class="min-w-0">
          <dt :class="DT">Telefone</dt>
          <dd :class="DD" class="truncate">{{ p.tel_prefeitura ?? '—' }}</dd>
        </div>
        <div class="col-span-2 min-w-0">
          <dt :class="DT">E-mail institucional</dt>
          <!-- Mesma receita da celula da tabela: em 375px o e-mail e o unico campo
               que nao cabe, e ele rola dentro do proprio bloco. -->
          <dd class="min-w-0 overflow-x-auto">
            <span class="block w-max whitespace-nowrap text-sm text-slate-700 dark:text-slate-200">
              {{ p.email_prefeitura ?? '—' }}
            </span>
          </dd>
        </div>
      </dl>

      <footer class="mt-3 flex justify-end border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <!--
          No bloco o rotulo FICA: aqui nao ha coluna disputando largura, e um icone
          solto no pe do card e adivinhacao.
        -->
        <ActionButton
          action="view"
          module="cedec"
          resource="prefeituras"
          label="Ver dados"
          :allowed="true"
          size="sm"
          tooltip-text="Ver dados da prefeitura"
          @click="abrir(p.municipio_id)"
        />
      </footer>
    </article>

    <div
      v-if="prefeituras.length === 0"
      class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <ListEmptyState
        :icon="BuildingOffice2Icon"
        title="Nenhum município encontrado"
        helper="Ajuste a busca, a REDEC ou a macrorregião, ou use Limpar para ver a lista inteira."
      />
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { BuildingOffice2Icon } from '@heroicons/vue/24/outline';
import { useMobile } from '@/Composables/useMobile';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

/**
 * Organismo: concentra a interacao da listagem. Navegar acontece aqui, no mesmo padrao
 * dos outros organismos de tabela -- a pagina nao precisa repassar um evento que so
 * tem um destino possivel.
 *
 * Nao recebe mais `podeEditar`: a linha abre o DETALHE, que qualquer um com
 * cedec.prefeituras.view pode ver. Quem decide sobre editar e a tela de detalhe.
 */
defineProps({
  prefeituras: { type: Array, default: () => [] },
});

const { isDesktop } = useMobile();

const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'px-3 py-2 text-sm font-medium text-slate-900 dark:text-slate-100';
const DT = 'text-xs font-medium text-slate-500 dark:text-slate-400';
const DD = 'text-sm text-slate-700 dark:text-slate-200';

// Classes literais, nunca interpoladas: o Tailwind varre os arquivos .vue de
// resources/js em busca de literais. Uma string montada em runtime, ou vinda de enum
// PHP, nao seria detectada e a cor nao existiria no CSS final.
//
// Comentario de LINHA de proposito: o glob que descreve essa varredura contem a
// sequencia que fecha comentario de bloco, e escrever isso entre barra-asterisco
// quebra o build do Vue com "Unexpected token".
const PILULA_SIM = 'inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300';
const PILULA_NAO = 'inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500 dark:bg-slate-700/50 dark:text-slate-400';

/**
 * A linha abre o DETALHE, nao o formulario. O detalhe e a porta: quem so tem
 * cedec.prefeituras.view para la, e quem pode editar segue pelo botao de dentro. Antes
 * disso, quem so consultava caia num formulario com aviso de leitura.
 *
 * O binding da rota e por Municipio, nao por Prefeitura: a CEDEC navega pelos 853
 * municipios, inclusive os que ainda nao tem linha em compdec_prefeituras.
 */
function abrir(municipioId) {
  router.visit(route('cedec.prefeituras.show', municipioId));
}
</script>
