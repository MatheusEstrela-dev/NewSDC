<script setup>
/**
 * Representantes de uma comunidade do PMDA.
 *
 * Regra do legado (gestaocedec, mod_pipa/backEnd/View/pmda/comunidade.php): a
 * comunidade so fecha com REPRESENTANTES_MINIMO, e o legado pintava a linha de
 * vermelho enquanto faltava. O contador aqui cumpre o mesmo papel -- sem ele o
 * usuario so descobre o que falta ao tentar enviar o PMDA.
 *
 * O CPF unico dentro do mesmo PMDA e validado no backend; aqui a mensagem dele
 * so e exibida.
 */
import { computed, ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { TrashIcon, UserPlusIcon } from '@heroicons/vue/24/outline';

const REPRESENTANTES_MINIMO = 3;

const props = defineProps({
  show: { type: Boolean, default: false },
  comunidade: { type: Object, default: null },
});

const emit = defineEmits(['close']);
const { can } = usePermissions();

const form = useForm({
  nome: '',
  cpf: '',
  tel: '',
  whatsapp: '',
  email: '',
  endereco: '',
  bairro: '',
});

const representantes = computed(() => props.comunidade?.representantes ?? []);
const faltam = computed(() => Math.max(0, REPRESENTANTES_MINIMO - representantes.value.length));
const erro = computed(() => Object.values(form.errors)[0] ?? null);

// O modal fica aberto entre inclusoes (sao 3 seguidas): limpar ao abrir evita
// carregar o erro da comunidade anterior para a proxima.
watch(() => props.show, (aberto) => {
  if (aberto) {
    form.reset();
    form.clearErrors();
  }
});

const campos = [
  { chave: 'nome', label: 'Nome', obrigatorio: true, max: 100, placeholder: 'Ex: Maria Pereira' },
  { chave: 'cpf', label: 'CPF', mask: 'cpf', max: 14, placeholder: '000.000.000-00' },
  { chave: 'tel', label: 'Telefone', mask: 'telefone', max: 20, placeholder: '(38) 9 9999-9999' },
  { chave: 'whatsapp', label: 'WhatsApp', mask: 'telefone', max: 20, placeholder: '(38) 9 9999-9999' },
  { chave: 'email', label: 'E-mail', max: 110, placeholder: 'nome@exemplo.com' },
  { chave: 'endereco', label: 'Endereço', max: 150, placeholder: 'Rua, número' },
  { chave: 'bairro', label: 'Bairro', max: 100, placeholder: 'Centro' },
];

function adicionar() {
  if (!form.nome.trim() || !props.comunidade) return;

  form.post(route('pmda.representantes.store', props.comunidade.id), {
    preserveScroll: true,
    onSuccess: () => { form.reset(); },
  });
}

function remover(id) {
  router.delete(route('pmda.representantes.destroy', id), { preserveScroll: true });
}
</script>

<template>
  <Modal :show="show" max-width="3xl" @close="emit('close')">
    <div v-if="comunidade" class="space-y-4 p-5">
      <header class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">
            Representantes — {{ comunidade.nome }}
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            A comunidade precisa de {{ REPRESENTANTES_MINIMO }} representantes para o PMDA ser enviado.
          </p>
        </div>
        <span
          class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
          :class="faltam === 0
            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'
            : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400'"
        >
          {{ representantes.length }}/{{ REPRESENTANTES_MINIMO }}
        </span>
      </header>

      <div v-if="can('pmda.representantes.create')" class="rounded-lg border border-slate-200 p-3 dark:border-slate-700/50">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="campo in campos" :key="campo.chave">
            <label class="pmda-field-label">
              {{ campo.label }} <span v-if="campo.obrigatorio" class="req">*</span>
            </label>
            <TextInput
              v-model="form[campo.chave]"
              :mask="campo.mask"
              :maxlength="campo.max"
              :placeholder="campo.placeholder"
            />
          </div>
        </div>
        <div class="mt-3 flex justify-end">
          <Button
            variant="success"
            size="sm"
            :disabled="!form.nome.trim() || form.processing"
            :loading="form.processing"
            @click="adicionar"
          >
            <UserPlusIcon class="mr-1 h-4 w-4" /> Adicionar Representante
          </Button>
        </div>
        <p v-if="erro" class="mt-2 text-xs text-red-600">{{ erro }}</p>
      </div>

      <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700/50">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700/50">
          <thead class="bg-slate-50 dark:bg-slate-800/40">
            <tr class="text-left text-slate-500 dark:text-slate-400">
              <th class="px-4 py-2 font-medium">Nome</th>
              <th class="px-4 py-2 font-medium">CPF</th>
              <th class="px-4 py-2 font-medium">Contato</th>
              <th class="px-4 py-2 font-medium">Endereço</th>
              <th class="px-4 py-2 text-right font-medium">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="r in representantes" :key="r.id" class="text-slate-700 dark:text-slate-300">
              <td class="px-4 py-2.5 font-medium">{{ r.nome }}</td>
              <td class="px-4 py-2.5 font-mono text-xs">{{ r.cpf || '—' }}</td>
              <td class="px-4 py-2.5">
                <span class="block">{{ r.tel || '—' }}</span>
                <span v-if="r.whatsapp" class="block text-xs text-emerald-600 dark:text-emerald-500">
                  WhatsApp: {{ r.whatsapp }}
                </span>
                <span v-if="r.email" class="block text-xs text-slate-400">{{ r.email }}</span>
              </td>
              <td class="px-4 py-2.5 text-xs text-slate-500">
                <span class="block">{{ r.endereco || '—' }}</span>
                <span v-if="r.bairro" class="block text-slate-400">{{ r.bairro }}</span>
              </td>
              <td class="px-4 py-2.5 text-right">
                <Button
                  v-if="can('pmda.representantes.delete')"
                  variant="danger"
                  size="sm"
                  @click="remover(r.id)"
                >
                  <TrashIcon class="h-4 w-4" />
                </Button>
              </td>
            </tr>
            <tr v-if="representantes.length === 0">
              <td colspan="5" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500">
                Nenhum representante cadastrado.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="faltam > 0" class="text-xs text-amber-600 dark:text-amber-500">
        Ainda {{ faltam === 1 ? 'falta 1 representante' : `faltam ${faltam} representantes` }} nesta comunidade.
      </p>

      <div class="flex justify-end pt-1">
        <Button variant="secondary" size="sm" @click="emit('close')">Fechar</Button>
      </div>
    </div>
  </Modal>
</template>
