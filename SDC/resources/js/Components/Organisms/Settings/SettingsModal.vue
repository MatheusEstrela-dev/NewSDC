<template>
  <Teleport to="body">
    <Transition leave-active-class="duration-200">
      <div 
        v-if="isOpen" 
        class="fixed inset-0 overflow-y-auto px-3 py-4 pt-16 sm:px-0 sm:pt-20" 
        style="z-index: 9999 !important;" 
        scroll-region
        ref="settingsModalContainer"
      >
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div v-show="isOpen" class="fixed inset-0 transform transition-all" style="z-index: 9998 !important;" @click="close">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" />
          </div>
        </Transition>

        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div
            v-if="isOpen"
            class="relative mb-6 w-full max-w-5xl h-[95vh] md:h-[85vh] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl flex flex-col md:flex-row overflow-hidden border border-slate-200 dark:border-slate-800 sm:mx-auto"
            style="z-index: 10000 !important;"
          >
            <!-- Mobile Tab Strip -->
            <div class="flex md:hidden overflow-x-auto border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 gap-1 px-3 py-2 flex-shrink-0 scrollbar-hide">
              <button
                v-for="tab in tabs"
                :key="'mobile-'+tab.id"
                @click="currentTab = tab.id"
                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap transition-all flex-shrink-0"
                :class="currentTab === tab.id ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'"
              >
                <component :is="tab.icon" class="w-3.5 h-3.5" />
                {{ tab.label }}
              </button>
            </div>

            <!-- Sidebar Navigation (desktop only) -->
            <div class="hidden md:flex w-64 bg-slate-50 dark:bg-slate-800/50 border-r border-slate-200 dark:border-slate-800 flex-col flex-shrink-0">
              <div class="p-6">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                  Configurações
                </h2>
                <p class="text-xs text-slate-500 mt-1">Centro de Preferências</p>
              </div>

              <nav class="flex-1 px-4 space-y-1 overflow-y-auto">
                <button
                  v-for="tab in tabs"
                  :key="tab.id"
                  @click="currentTab = tab.id"
                  class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all"
                  :class="currentTab === tab.id ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-200'"
                >
                  <component :is="tab.icon" class="w-5 h-5" />
                  {{ tab.label }}
                </button>
              </nav>

              <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                   <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-300">
                       {{ userInitials }}
                   </div>
                   <div class="flex-1 min-w-0">
                       <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ userName }}</p>
                       <p class="text-xs text-slate-500 truncate">Configurando perfil</p>
                   </div>
                </div>
              </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-w-0 bg-white dark:bg-slate-900">
              <!-- Header -->
              <div class="px-4 py-4 sm:px-8 sm:py-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
                <div>
                  <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ activeTabLabel }}</h3>
                  <p class="text-sm text-slate-500">{{ activeTabDescription }}</p>
                </div>
                <button @click="close" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>

              <!-- Content Area -->
              <div class="flex-1 overflow-y-auto p-4 sm:p-8">
                
                <!-- Tab: Perfil -->
                <div v-if="currentTab === 'profile'" class="space-y-8">
                   <section>
                       <h4 class="text-sm font-medium text-slate-900 dark:text-white uppercase tracking-wider mb-4">Informações Pessoais</h4>
                       <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                           <div class="col-span-2 flex items-center gap-6">
                               <div class="w-20 h-20 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 border-2 border-dashed border-slate-300 dark:border-slate-700">
                                   <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                               </div>
                               <button class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg transition-colors">Alterar Foto</button>
                           </div>
                           <div class="space-y-1">
                               <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Nome Completo</label>
                               <input type="text" v-model="form.name" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 dark:text-white">
                           </div>
                           <div class="space-y-1">
                               <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Cargo / Função</label>
                               <input type="text" v-model="form.role" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 dark:text-white">
                           </div>
                       </div>
                   </section>
                   <section>
                       <h4 class="text-sm font-medium text-slate-900 dark:text-white uppercase tracking-wider mb-4">Assinatura Digital</h4>
                       <div class="p-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg">
                           <p class="text-sm text-slate-500 mb-2">Utilizada para assinar relatórios RAT e ofícios automaticamente.</p>
                           <div class="h-32 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded flex items-center justify-center text-slate-400">
                               <span>Arraste sua assinatura ou clique para upload</span>
                           </div>
                       </div>
                   </section>
                </div>

                <!-- Tab: Notificações -->
                <div v-if="currentTab === 'notifications'" class="space-y-8">
                    <div v-for="module in notificationModules" :key="module.id" class="p-4 border border-slate-200 dark:border-slate-700 rounded-xl">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400">
                                    <component :is="module.icon" class="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white">{{ module.name }}</h4>
                                    <p class="text-xs text-slate-500">{{ module.description }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <input type="checkbox" v-model="module.channels.bell" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">Sino (Sistema)</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <input type="checkbox" v-model="module.channels.email" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">E-mail</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <input type="checkbox" v-model="module.channels.push" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">Push (Desktop)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tab: Interface -->
                <div v-if="currentTab === 'interface'" class="space-y-8">
                    <section>
                        <h4 class="text-sm font-medium text-slate-900 dark:text-white uppercase tracking-wider mb-4">Tema & Aparência</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                            <button class="p-4 border-2 border-slate-200 dark:border-slate-700 rounded-xl hover:border-blue-500 transition-all flex flex-col items-center gap-2">
                                <div class="w-full h-20 bg-white rounded-lg shadow-sm border border-slate-200"></div>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Claro</span>
                            </button>
                            <button class="p-4 border-2 border-blue-500 bg-blue-50/10 rounded-xl flex flex-col items-center gap-2">
                                <div class="w-full h-20 bg-slate-900 rounded-lg shadow-sm border border-slate-700"></div>
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400">Escuro (Ativo)</span>
                            </button>
                            <button class="p-4 border-2 border-slate-200 dark:border-slate-700 rounded-xl hover:border-blue-500 transition-all flex flex-col items-center gap-2">
                                <div class="w-full h-20 bg-black rounded-lg shadow-sm border border-white/20"></div>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Alto Contraste</span>
                            </button>
                        </div>
                    </section>
                    <section>
                         <h4 class="text-sm font-medium text-slate-900 dark:text-white uppercase tracking-wider mb-4">Módulo Inicial</h4>
                         <p class="text-sm text-slate-500 mb-3">Escolha qual página abrir ao fazer login.</p>
                         <select v-model="form.startModule" class="w-full max-w-md px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-slate-900 dark:text-white">
                             <option value="dashboard">Dashboard Geral</option>
                             <option value="demandas">Gestão de Demandas</option>
                             <option value="rat_create">Novo RAT (Técnico)</option>
                             <option value="pae">Monitoramento PAE</option>
                         </select>
                    </section>
                </div>

                 <!-- Tab: Regionalização -->
                 <div v-if="currentTab === 'region'" class="space-y-8">
                     <div class="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-xl flex items-start gap-4">
                         <svg class="w-6 h-6 text-blue-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                         <div>
                             <h4 class="font-bold text-blue-900 dark:text-blue-100">Filtro Geográfico Inteligente</h4>
                             <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">Ao selecionar uma região, o sistema filtrará automaticamente alertas meteorológicos e notificações relevantes apenas para esta área. Ideal para coordenadores regionais.</p>
                         </div>
                     </div>
                     
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <!-- Mock Regions -->
                          <label v-for="region in regions" :key="region.id" class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-lg cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" :class="{'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10': form.regions.includes(region.id)}">
                              <div class="flex items-center gap-3">
                                  <input type="checkbox" :value="region.id" v-model="form.regions" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                  <span class="font-medium text-slate-900 dark:text-white">{{ region.name }}</span>
                              </div>
                              <span class="text-xs text-slate-500">{{ region.count }} municípios</span>
                          </label>
                     </div>
                 </div>

                 <!-- Tab: Integrações -->
                 <div v-if="currentTab === 'integrations'" class="space-y-8">
                     <div class="space-y-6">
                         <div class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-xl">
                             <div class="flex items-center gap-4">
                                 <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-white font-bold text-xl">W</div>
                                 <div>
                                     <h4 class="font-bold text-slate-900 dark:text-white">WhatsApp / Telegram</h4>
                                     <p class="text-sm text-slate-500">Receber resumos diários e alertas críticos.</p>
                                 </div>
                             </div>
                             <button class="px-4 py-2 text-sm font-medium border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">Conectar</button>
                         </div>

                          <div class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-xl">
                             <div class="flex items-center gap-4">
                                 <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-xl">N</div>
                                 <div>
                                     <h4 class="font-bold text-slate-900 dark:text-white">N8N Webhook</h4>
                                     <p class="text-sm text-slate-500">Token pessoal para automações externas.</p>
                                 </div>
                             </div>
                             <button class="px-4 py-2 text-sm font-medium border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">Gerenciar Token</button>
                         </div>
                     </div>
                 </div>

              </div>

              <!-- Footer -->
              <div class="px-4 py-4 sm:px-8 sm:py-5 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-900">
                <span class="text-sm text-slate-500">Alterações não salvas serão perdidas.</span>
                <div class="flex gap-3">
                  <button @click="close" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors">
                    Cancelar
                  </button>
                  <button 
                    @click="save" 
                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-500 rounded-lg shadow-sm transition-all flex items-center gap-2"
                    :disabled="isSaving"
                  >
                    <svg v-if="isSaving" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span v-if="isSaving">Salvando...</span>
                    <span v-else>Salvar Alterações</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { 
    UserIcon, 
    BellIcon, 
    ComputerDesktopIcon, 
    MapIcon, 
    CpuChipIcon, 
    ShieldCheckIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: Boolean
});

const emit = defineEmits(['close']);
const settingsModalContainer = ref(null);

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = null;
    }
  },
  { immediate: true }
);

const page = usePage();
const currentTab = ref('profile');
const isSaving = ref(false);

const tabs = [
  { id: 'profile', label: 'Meu Perfil', icon: UserIcon, description: 'Gerencie suas informações pessoais e assinatura digital.' },
  { id: 'notifications', label: 'Notificações', icon: BellIcon, description: 'Escolha como e quando você quer ser alertado.' },
  { id: 'region', label: 'Regionalização', icon: MapIcon, description: 'Filtre dados e alertas por região de interesse.' },
  { id: 'interface', label: 'Interface & UX', icon: ComputerDesktopIcon, description: 'Personalize a aparência e comportamento do sistema.' },
  { id: 'integrations', label: 'Integrações', icon: CpuChipIcon, description: 'Conecte serviços externos e tokens de API.' },
  { id: 'security', label: 'Segurança', icon: ShieldCheckIcon, description: 'Gerencie sua senha e sessões ativas.' },
];

// Reactive Form State (Mocked)
const form = ref({
    name: page.props.auth?.user?.name || '',
    role: 'Coordenador Defesa Civil',
    startModule: 'dashboard',
    regions: ['bh'],
});

const notificationModules = ref([
    { id: 'rat', name: 'Relatórios (RAT)', description: 'Alertas sobre novos relatórios, vistorias e aprovações.', icon: 'DocumentTextIcon', channels: { bell: true, email: true, push: false } },
    { id: 'pae', name: 'Planos (PAE)', description: 'Vencimentos de prazos e atualizações de status.', icon: 'MapIcon', channels: { bell: true, email: false, push: true } },
    { id: 'meteo', name: 'Meteorologia', description: 'Alertas críticos de chuva e mudanças climáticas (INMET).', icon: 'CloudIcon', channels: { bell: true, email: true, push: true } },
    { id: 'demanda', name: 'Demandas/Chamados', description: 'Atribuições de tarefas e novos comentários.', icon: 'CheckBadgeIcon', channels: { bell: true, email: false, push: false } },
]);

const regions = [
    { id: 'bh', name: 'RMBH - Metropolitana', count: 34 },
    { id: 'sul', name: 'Sul de Minas', count: 120 },
    { id: 'norte', name: 'Norte de Minas', count: 86 },
    { id: 'vales', name: 'Vales do Aço/Rio Doce', count: 54 },
];

const activeTabLabel = computed(() => tabs.find(t => t.id === currentTab.value)?.label);
const activeTabDescription = computed(() => tabs.find(t => t.id === currentTab.value)?.description);

const userName = computed(() => page.props.auth?.user?.name || 'Usuário');
const userInitials = computed(() => userName.value.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase());

const close = () => {
    emit('close');
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.isOpen) {
        close();
    }
};

onMounted(() => {
    console.log('SettingsModal Mounted!');
    document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = null;
});

const save = async () => {
    isSaving.value = true;
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500));
    isSaving.value = false;
    emit('close');
    // In real app: Show toast success
};
</script>