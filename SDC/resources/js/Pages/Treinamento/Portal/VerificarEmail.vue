<template>
  <Head title="Portal de Treinamentos - Confirmar cadastro" />

  <div class="login-container">
    <div class="login-card" style="max-width: 32rem;">
      <header class="card-header">
        <picture>
          <source srcset="/imgs/logo-defesa-civil-login.webp" type="image/webp" />
          <img
            src="/imgs/logo-defesa-civil-login.png"
            alt="Logo Defesa Civil"
            class="main-logo"
            width="240"
            height="72"
            style="aspect-ratio: 10/3;"
          />
        </picture>
        <div class="system-title">Confirme seu cadastro</div>
      </header>

      <p class="mb-6 text-center text-sm text-slate-300">
        Enviamos um codigo de 6 numeros para <strong class="text-amber-400">{{ emailMascarado }}</strong>.
        Ele vale por {{ ttlMinutos }} minutos.
      </p>

      <form @submit.prevent="submit" class="login-form">
        <div class="input-group">
          <input
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            id="codigo"
            :value="form.codigo"
            @input="handleCodigoInput"
            maxlength="6"
            class="input-field text-center"
            style="letter-spacing: 0.5em; font-size: 1.25rem;"
            placeholder=" "
            required
            :class="{ 'border-red-500': form.errors.codigo }"
          />
          <label for="codigo" class="input-label">Codigo de 6 numeros</label>
        </div>

        <div v-if="mensagensErro.length" class="mb-4 mt-2 p-2 rounded-lg bg-red-500/10 border border-red-500/20 text-center shadow-sm backdrop-blur-sm">
          <p v-for="msg in mensagensErro" :key="msg" class="text-sm text-red-400 font-medium">{{ msg }}</p>
        </div>

        <button type="submit" class="btn-login" :disabled="form.processing || form.codigo.length < 6">
          <span v-if="!form.processing">Confirmar cadastro</span>
          <span v-else class="btn-loading">Confirmando...</span>
        </button>
      </form>

      <div class="card-footer">
        Nao recebeu?
        <button
          type="button"
          class="text-amber-400 hover:underline disabled:opacity-50 disabled:no-underline disabled:cursor-not-allowed"
          :disabled="reenvio.processing || esperaReenvio > 0"
          @click="reenviar"
        >
          <span v-if="esperaReenvio > 0">Reenviar codigo em {{ esperaReenvio }}s</span>
          <span v-else>Reenviar codigo</span>
        </button>
      </div>

      <div class="card-footer">
        <Link :href="route('login')" class="text-amber-400 hover:underline">Voltar para o login</Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import '../../../../css/pages/auth/login.css';

defineProps({
  emailMascarado: { type: String, required: true },
  ttlMinutos: { type: Number, required: true },
  maxTentativas: { type: Number, required: true },
});

const form = useForm({ codigo: '' });
const reenvio = useForm({});

// retry_after nao e mensagem: o backend manda a chave junto do erro para o
// front animar a contagem (mesmo contrato do LoginRequest::throwThrottle).
// Sem filtrar, o v-for imprimiria o numero cru como se fosse texto de erro.
const mensagensErro = computed(() =>
  Object.entries(form.errors)
    .filter(([campo]) => campo !== 'retry_after')
    .map(([, msg]) => msg),
);

const esperaReenvio = ref(0);
let timer = null;

const pararTimer = () => {
  if (timer) {
    clearInterval(timer);
    timer = null;
  }
};

const iniciarEspera = (segundos) => {
  esperaReenvio.value = segundos;
  pararTimer();

  timer = setInterval(() => {
    esperaReenvio.value -= 1;

    if (esperaReenvio.value <= 0) {
      pararTimer();
    }
  }, 1000);
};

// O cooldown de reenvio chega como erro de validacao no reenvio.
watch(
  () => reenvio.errors.retry_after,
  (segundos) => {
    if (segundos) {
      iniciarEspera(Number(segundos));
    }
  },
);

onBeforeUnmount(pararTimer);

const handleCodigoInput = (evt) => {
  const numbersOnly = evt.target.value.replace(/\D/g, '').slice(0, 6);
  form.codigo = numbersOnly;
  evt.target.value = numbersOnly;
};

const submit = () => {
  form.post(route('portal.treinamento.verificar-email.store'));
};

const reenviar = () => {
  reenvio.post(route('portal.treinamento.verificar-email.reenviar'), {
    preserveScroll: true,
    onSuccess: () => iniciarEspera(60),
  });
};
</script>
