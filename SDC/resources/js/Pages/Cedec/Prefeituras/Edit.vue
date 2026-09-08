<template>
  <Head :title="`Prefeitura — ${municipio.nome}`" />

  <div class="w-full space-y-6 pb-8">
    <PageHeader
      :title="`Prefeitura de ${municipio.nome}`"
      :description="`${municipio.uf} — código IBGE ${municipio.codigo_ibge}`"
      :icon-image="moduleIcon('prefeituras') ?? ''"
      variant="gradient"
      :espaco-inferior="false"
    />

    <p
      v-if="!podeEditar"
      class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
    >
      Você tem acesso de leitura a este cadastro. Editar exige a permissão cedec.prefeituras.edit.
    </p>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <form class="min-w-0 space-y-6 lg:col-span-2" @submit.prevent="salvar">
        <PrefeituraFormSections
          :form="form"
          :errors="form.errors"
          :pode-editar="podeEditar"
        />

        <FormActions
          v-if="podeEditar"
          submit-label="Salvar prefeitura"
          :loading="form.processing"
          :disabled="form.processing"
          @cancel="voltar"
          @submit="salvar"
        />
      </form>

      <div class="min-w-0 space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/40">
          <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Foto do prefeito</h3>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            JPEG, PNG ou WEBP, até {{ limiteFotoKb }} KB.
          </p>

          <div class="mt-4 flex flex-col items-center gap-4">
            <img
              v-if="prefeitura && prefeitura.foto_prefeito_url"
              :src="prefeitura.foto_prefeito_url"
              alt="Foto do prefeito"
              class="h-32 w-32 rounded-xl border-2 border-slate-200 object-cover dark:border-slate-700"
            />
            <div
              v-else
              class="flex h-32 w-32 items-center justify-center rounded-xl border-2 border-dashed border-slate-300 text-slate-400 dark:border-slate-700 dark:text-slate-500"
            >
              <UserCircleIcon class="h-12 w-12" />
            </div>

            <div v-if="podeEditar" class="flex flex-wrap justify-center gap-2">
              <input
                ref="entradaFoto"
                type="file"
                class="hidden"
                accept="image/jpeg,image/png,image/webp"
                @change="aoEscolherFoto"
              />
              <Button
                variant="outline"
                size="sm"
                :loading="formFoto.processing"
                @click="abrirSeletorDeFoto"
              >
                {{ prefeitura && prefeitura.foto_prefeito_url ? 'Trocar foto' : 'Enviar foto' }}
              </Button>
              <Button
                v-if="prefeitura && prefeitura.foto_prefeito_url"
                variant="danger"
                size="sm"
                :disabled="formFoto.processing"
                @click="removerFoto"
              >
                Remover
              </Button>
            </div>

            <p v-if="formFoto.errors.foto" class="text-xs text-red-500">
              {{ formFoto.errors.foto }}
            </p>
          </div>
        </section>

        <IndicadoresMunicipaisPanel :indicadores="indicadores" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { UserCircleIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import PrefeituraFormSections from '@/Components/Organisms/Cedec/PrefeituraFormSections.vue';
import IndicadoresMunicipaisPanel from '@/Components/Organisms/Cedec/IndicadoresMunicipaisPanel.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  municipio: { type: Object, required: true },
  prefeitura: { type: Object, default: null },
  indicadores: { type: Object, required: true },
});

/*
 * A rota cedec.prefeituras.edit e liberada por cedec.prefeituras.view: quem so
 * consulta chega aqui. Quem decide se pode editar e a PAGINA, e a decisao desce como
 * prop -- o mesmo organismo de campos serve a aba do Compdec, cujo slug e outro.
 */
const { can } = usePermissions();

// computed, nao valor solto: can() lido uma vez no setup congela a permissao, e o
// proprio usePermissions alerta contra isso. Numa navegacao Inertia sem recarregar a
// pagina, o valor antigo sobreviveria.
const podeEditar = computed(() => can('cedec.prefeituras.edit'));

const form = useForm({
  prefeito_nome: props.prefeitura?.prefeito_nome ?? '',
  prefeito_partido: props.prefeitura?.prefeito_partido ?? '',
  prefeito_telefone: props.prefeitura?.prefeito_telefone ?? '',
  prefeito_celular: props.prefeitura?.prefeito_celular ?? '',
  prefeito_email: props.prefeitura?.prefeito_email ?? '',
  email_prefeitura: props.prefeitura?.email_prefeitura ?? '',
  email_prefeitura_2: props.prefeitura?.email_prefeitura_2 ?? '',
  email_prefeitura_3: props.prefeitura?.email_prefeitura_3 ?? '',
  tel_prefeitura: props.prefeitura?.tel_prefeitura ?? '',
  tel_prefeitura_2: props.prefeitura?.tel_prefeitura_2 ?? '',
  fax_prefeitura: props.prefeitura?.fax_prefeitura ?? '',
  endereco: props.prefeitura?.endereco ?? '',
  bairro: props.prefeitura?.bairro ?? '',
  cep: props.prefeitura?.cep ?? '',
  latitude: props.prefeitura?.latitude ?? '',
  longitude: props.prefeitura?.longitude ?? '',
  inss_tem_cobranca: Boolean(props.prefeitura?.inss_tem_cobranca ?? false),
  inss_aliquota: props.prefeitura?.inss_aliquota ?? '',
  inss_lei_cobranca: props.prefeitura?.inss_lei_cobranca ?? '',
  inss_responsavel: props.prefeitura?.inss_responsavel ?? '',
});

/*
 * A foto e um envio SEPARADO do formulario, e nao um campo dele: a colecao e
 * singleFile e as rotas de foto sao proprias (POST e DELETE). Misturar os dois
 * obrigaria o update inteiro a virar multipart so por causa do arquivo.
 *
 * O limite aqui e so o texto de ajuda; quem recusa de fato e o controller, com
 * config('compdec.upload_limits.foto_prefeito'). Se aquele valor mudar, este numero
 * vira mentira -- e a razao de ele aparecer em UM lugar so nesta tela.
 */
const LIMITE_FOTO_BYTES = 300 * 1024;

const limiteFotoKb = computed(() => Math.round(LIMITE_FOTO_BYTES / 1024));

const entradaFoto = ref(null);
const formFoto = useForm({ foto: null });

function abrirSeletorDeFoto() {
  entradaFoto.value?.click();
}

function aoEscolherFoto(evento) {
  const arquivo = evento.target.files?.[0] ?? null;

  // Zera o input antes de enviar: sem isso, escolher o MESMO arquivo de novo depois
  // de um erro nao dispara `change` e o botao parece morto.
  evento.target.value = '';

  if (! arquivo) {
    return;
  }

  formFoto.foto = arquivo;
  formFoto.post(route('cedec.prefeituras.foto.upload', props.municipio.id), {
    preserveScroll: true,
    forceFormData: true,
    onFinish: () => {
      formFoto.foto = null;
    },
  });
}

function removerFoto() {
  formFoto.delete(route('cedec.prefeituras.foto.destroy', props.municipio.id), {
    preserveScroll: true,
  });
}

function salvar() {
  // .value obrigatorio: no template o computed e desempacotado, aqui nao. Sem ele a
  // condicao testaria o objeto do ref, que e sempre truthy, e a guarda nunca dispararia.
  if (! podeEditar.value) {
    return;
  }

  form.put(route('cedec.prefeituras.update', props.municipio.id), {
    preserveScroll: true,
  });
}

function voltar() {
  router.visit(route('cedec.prefeituras.index'));
}
</script>
