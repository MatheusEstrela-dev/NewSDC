<template>
  <CardBase variant="default" padding="none">

    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/50 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
      <h3 class="flex items-center gap-2 text-lg font-bold text-slate-800 dark:text-slate-100">
        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
        </svg>
        Tokens de API
        <span class="text-xs font-normal text-slate-500 dark:text-slate-400">Bearer / Sanctum</span>
      </h3>
      <div class="flex items-center gap-3">
        <Badge variant="default" size="sm">{{ tokens.length }}</Badge>
        <Button
          v-if="!showForm"
          variant="primary"
          size="sm"
          :icon="PlusIcon"
          @click="showForm = true"
        >
          Gerar Novo
        </Button>
      </div>
    </div>

    <!-- Flash: token recém-gerado (exibido 1x) -->
    <div v-if="newToken" class="m-4 bg-emerald-950 border border-emerald-800 rounded-lg p-4">
      <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm mb-1">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        Token gerado: {{ newTokenName }}
      </div>
      <p class="text-xs text-emerald-300 mb-3">Copie agora. Este valor nao sera exibido novamente.</p>
      <div class="font-mono text-xs text-emerald-400 bg-emerald-900/50 border border-emerald-800 rounded p-2 mb-3 break-all leading-relaxed">
        {{ newToken }}
      </div>
      <Button
        variant="success"
        size="sm"
        :icon="ClipboardIcon"
        @click="copyToken(newToken)"
      >
        {{ copied ? 'Copiado!' : 'Copiar Token' }}
      </Button>
    </div>

    <!-- Lista de tokens -->
    <div v-if="tokens.length > 0">
      <div
        v-for="token in tokens"
        :key="token.id"
        class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/50 last:border-b-0 transition-opacity duration-200"
        :class="{ 'opacity-30 pointer-events-none': showForm }"
      >
        <div class="flex items-center gap-2 mb-2">
          <span class="w-2 h-2 rounded-full flex-shrink-0" :class="tokenDotClass(token)"></span>
          <span class="text-sm font-semibold text-slate-800 dark:text-slate-100 flex-1">{{ token.name }}</span>
          <Badge :variant="tokenBadgeVariant(token)" size="sm">{{ tokenStatusLabel(token) }}</Badge>
        </div>

        <div class="flex items-center gap-2 mb-2">
          <div class="font-mono text-xs text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded px-2 py-1 flex-1">
            sk-&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;{{ String(token.id).slice(-3).padStart(3, '\u2022') }}
          </div>
          <Button variant="danger" size="sm" :icon="TrashIcon" @click="confirmRevoke(token)">
            Revogar
          </Button>
        </div>

        <div class="flex flex-wrap gap-4 text-xs text-slate-400 dark:text-slate-500">
          <span>Criado: {{ formatDate(token.created_at) }}</span>
          <span v-if="token.expires_at">Expira: {{ formatDate(token.expires_at) }}</span>
          <span v-else>Sem expiracao</span>
          <span v-if="token.last_used_at">Ultimo uso: {{ formatDate(token.last_used_at) }}</span>
          <span v-else>Nunca usado</span>
        </div>
      </div>
    </div>

    <div v-else-if="!showForm" class="p-8 text-center">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
        </svg>
      </div>
      <p class="text-sm text-slate-500 dark:text-slate-400">Nenhum token gerado para este usuario.</p>
    </div>

    <!-- Form inline de geração -->
    <div v-if="showForm" class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t-2 border-blue-600">
      <p class="text-xs font-bold text-blue-500 dark:text-blue-400 mb-3 flex items-center gap-1.5">
        <PlusIcon class="w-3.5 h-3.5" />
        Gerar Novo Token
      </p>
      <form @submit.prevent="submitForm" class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1 flex-1 min-w-32">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Nome</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="ex: Swagger, PowerBI..."
            class="text-sm px-3 py-2 bg-white dark:bg-slate-800 border rounded-lg text-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500"
            :class="errors.name ? 'border-red-500' : 'border-slate-300 dark:border-blue-600'"
            maxlength="60"
          />
          <span v-if="errors.name" class="text-xs text-red-400">{{ errors.name }}</span>
        </div>
        <div class="flex flex-col gap-1" style="min-width: 140px;">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Expiracao</label>
          <select
            v-model="form.expires_in"
            class="text-sm px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-800 dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="24h">24 horas</option>
            <option value="7d">7 dias</option>
            <option value="30d">30 dias</option>
            <option value="never">Sem expiracao</option>
          </select>
        </div>
        <Button type="submit" variant="primary" size="sm" :loading="processing">
          Gerar
        </Button>
        <Button type="button" variant="secondary" size="sm" @click="cancelForm">
          Cancelar
        </Button>
      </form>
    </div>

    <ConfirmDialog
      :is-open="showRevokeDialog"
      title="Revogar Token de API"
      :message="tokenToRevoke ? `Revogar o token “${tokenToRevoke.name}”?` : ''"
      description="Esta acao nao pode ser desfeita. Aplicacoes que usam este token perderao acesso imediatamente."
      variant="danger"
      confirm-text="Sim, revogar"
      cancel-text="Cancelar"
      :loading="isRevoking"
      @confirm="executeRevoke"
      @cancel="cancelRevoke"
    />

  </CardBase>
</template>

<script setup>
import { ref, markRaw } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import TrashIcon from '@/Components/Icons/TrashIcon.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useToast } from '@/Composables/useToast';

const PlusIconRaw     = markRaw(PlusIcon);
const ClipboardIconRaw = markRaw(ClipboardIcon);
const TrashIconRaw    = markRaw(TrashIcon);

const props = defineProps({
  userId:       { type: Number, required: true },
  tokens:       { type: Array,  default: () => [] },
  newToken:     { type: String, default: null },
  newTokenName: { type: String, default: null },
});

const showForm   = ref(false);
const processing = ref(false);
const copied     = ref(false);
const errors     = ref({});

const showRevokeDialog = ref(false);
const tokenToRevoke    = ref(null);
const isRevoking       = ref(false);

const form = ref({
  name:       '',
  expires_in: '30d',
});

function cancelForm() {
  showForm.value  = false;
  form.value.name = '';
  errors.value    = {};
}

function submitForm() {
  processing.value = true;
  errors.value     = {};

  router.post(route('admin.permissions.users.tokens.store', props.userId), form.value, {
    onError:  (e) => { errors.value = e; },
    onFinish: () => { processing.value = false; showForm.value = false; form.value.name = ''; },
  });
}

function confirmRevoke(token) {
  tokenToRevoke.value    = token;
  showRevokeDialog.value = true;
}

function executeRevoke() {
  if (!tokenToRevoke.value) return;
  const { toast } = useToast();
  isRevoking.value = true;

  router.delete(route('admin.permissions.users.tokens.destroy', {
    user:    props.userId,
    tokenId: tokenToRevoke.value.id,
  }), {
    preserveScroll: true,
    onSuccess: () => {
      toast('Token revogado com sucesso!', 'success');
      showRevokeDialog.value = false;
      tokenToRevoke.value    = null;
    },
    onError: (err) => {
      console.error('Erro ao revogar token:', err);
      toast('Falha ao revogar token. Verifique o console.', 'error');
    },
    onFinish: () => { isRevoking.value = false; },
  });
}

function cancelRevoke() {
  if (isRevoking.value) return;
  showRevokeDialog.value = false;
  tokenToRevoke.value    = null;
}

function copyToken(value) {
  navigator.clipboard.writeText(value).then(() => {
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
  });
}

function tokenStatusLabel(token) {
  if (!token.expires_at) return 'ATIVO';
  const hoursLeft = (new Date(token.expires_at) - new Date()) / 36e5;
  if (hoursLeft < 0)   return 'EXPIRADO';
  if (hoursLeft < 24)  return `EXPIRA EM ${Math.ceil(hoursLeft)}H`;
  return 'ATIVO';
}

function tokenBadgeVariant(token) {
  const label = tokenStatusLabel(token);
  if (label === 'EXPIRADO')       return 'danger';
  if (label.startsWith('EXPIRA')) return 'warning';
  return 'success';
}

function tokenDotClass(token) {
  const variant = tokenBadgeVariant(token);
  const map = { success: 'bg-emerald-500', warning: 'bg-amber-400', danger: 'bg-red-500' };
  return map[variant] ?? 'bg-slate-400';
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}
</script>
