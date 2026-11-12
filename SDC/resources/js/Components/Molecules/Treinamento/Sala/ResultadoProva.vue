<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';

const props = defineProps({
  resultado: { type: Object, required: true },
  situacao: { type: Object, default: null },
});

const emit = defineEmits(['revisar', 'nova-tentativa']);

const restantes = computed(() => props.situacao?.tentativas_restantes ?? 0);
const travada = computed(() => props.situacao?.travada === true);
const podeNovaTentativa = computed(() => !props.resultado.aprovada && props.situacao?.pode_iniciar === true);
</script>

<template>
  <div class="space-y-4" role="status">
    <Badge :variant="resultado.aprovada ? 'success' : 'danger'">{{ resultado.aprovada ? 'Aprovado' : 'Reprovado' }}</Badge>

    <div>
      <p class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ resultado.nota }}</p>
      <Text size="sm" color="muted">
        {{ resultado.acertos }} de {{ resultado.total }} questões corretas (tentativa {{ resultado.numero }})
      </Text>
    </div>

    <Text v-if="resultado.aprovada" size="sm" color="success">
      Parabéns! O próximo conteúdo da trilha foi liberado.
    </Text>
    <template v-else>
      <Text v-if="travada" size="sm">
        Suas tentativas acabaram. O professor precisa liberar uma nova tentativa para você continuar.
      </Text>
      <Text v-else size="sm">
        Você ainda tem {{ restantes }} {{ restantes === 1 ? 'tentativa' : 'tentativas' }}. Revise o conteúdo antes de tentar de novo.
      </Text>

      <div class="flex flex-wrap gap-2">
        <Button variant="outline" @click="emit('revisar')">Revisar conteúdo</Button>
        <Button v-if="podeNovaTentativa" @click="emit('nova-tentativa')">Fazer nova tentativa</Button>
      </div>
    </template>
  </div>
</template>
