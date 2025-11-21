<template>
  <div class="bg-white rounded-xl shadow-sm border border-slate-100 flex flex-col h-fit">
    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/30">
      <h3 class="font-bold text-slate-800 text-lg">{{ title }}</h3>
    </div>

    <div class="p-0">
      <div class="relative">
        <!-- Linha vertical conectora -->
        <div class="absolute left-8 top-4 bottom-4 w-0.5 bg-slate-100"></div>

        <ul class="py-2">
          <li
            v-for="(item, index) in items"
            :key="item.id"
            class="relative px-6 py-4 hover:bg-slate-50 cursor-pointer transition-colors flex gap-4 group"
            @click="$emit('view-item', item)"
          >
            <!-- Bolinha da timeline -->
            <div class="relative z-10 mt-1">
              <div
                :class="[
                  'w-4 h-4 rounded-full border-2 border-white ring-1',
                  index === 0
                    ? 'bg-blue-500 ring-blue-200 shadow-[0_0_0_3px_rgba(59,130,246,0.2)]'
                    : 'bg-slate-300 ring-slate-200',
                ]"
              ></div>
            </div>

            <div class="flex-1">
              <div class="flex justify-between items-start">
                <span
                  class="font-semibold text-slate-800 text-sm group-hover:text-blue-700 transition-colors"
                >
                  {{ item.municipio }}
                </span>
                <span
                  class="text-[10px] font-medium text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded"
                >
                  {{ item.data }}
                </span>
              </div>
              <p class="text-sm text-slate-600 mt-0.5">{{ item.acao }}</p>
              <p
                v-if="item.protocolo"
                class="text-xs text-slate-400 mt-1 font-mono bg-slate-50 inline-block px-1 rounded border border-slate-100"
              >
                {{ item.protocolo }}
              </p>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'Últimas Movimentações',
  },
  items: {
    type: Array,
    required: true,
  },
});

defineEmits(['view-item']);
</script>

