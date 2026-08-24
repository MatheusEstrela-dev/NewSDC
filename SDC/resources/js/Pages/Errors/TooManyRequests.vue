<template>
    <Head :title="title ?? 'Muitas tentativas'" />

    <div class="error-wrap">
      <div class="error-card">
        <div class="error-code">429</div>
        <h1 class="error-title">{{ title ?? 'Muitas tentativas' }}</h1>
        <p class="error-message">
          {{ message ?? 'Recebemos varias solicitacoes em pouco tempo e pausamos o acesso por seguranca. Aguarde um instante e tente novamente.' }}
        </p>

        <p v-if="restante > 0" class="error-retry">
          Tente novamente em <strong>{{ restante }}</strong> segundos.
        </p>
        <p v-else-if="retryAfter > 0" class="error-retry">
          Voce ja pode tentar novamente.
        </p>
      </div>
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

// Sem o link "Voltar ao Dashboard" do NotFound/Forbidden: aqui o problema nao e
// destino errado, e so esperar. A sidebar do layout ja da a navegacao, e a
// contagem regressiva e a informacao que realmente importa.
//
// Cidadao do Portal de Treinamentos nao chega nesta pagina: o Handler so
// renderiza Inertia quando existe $request->user() (guard "web"); o guard
// "cidadao" cai em resources/views/errors/429.blade.php.
const props = defineProps({
  title: { type: String, default: null },
  message: { type: String, default: null },
  retryAfter: { type: Number, default: 0 },
});

const restante = ref(props.retryAfter);
let timer = null;

onMounted(() => {
  if (restante.value <= 0) {
    return;
  }

  timer = setInterval(() => {
    restante.value -= 1;

    if (restante.value <= 0) {
      clearInterval(timer);
      timer = null;
    }
  }, 1000);
});

onBeforeUnmount(() => {
  if (timer) {
    clearInterval(timer);
  }
});
</script>

<style scoped>
.error-wrap {
  min-height: calc(100vh - 64px);
  display: grid;
  place-items: center;
  padding: 2rem 1rem;
  background: #0f172a;
}

.error-card {
  width: 100%;
  max-width: 520px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 16px;
  padding: 2rem;
  text-align: center;
  backdrop-filter: blur(12px);
}

.error-code {
  font-size: 3rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: rgba(148, 163, 184, 0.9);
  margin-bottom: 0.5rem;
}

.error-title {
  color: #e2e8f0;
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
}

.error-message {
  color: rgba(226, 232, 240, 0.85);
  margin: 0;
  line-height: 1.5;
}

.error-retry {
  margin: 1.25rem 0 0;
  font-size: 0.9375rem;
  color: rgba(148, 163, 184, 0.9);
}

.error-retry strong {
  color: #e2e8f0;
  font-variant-numeric: tabular-nums;
}
</style>
