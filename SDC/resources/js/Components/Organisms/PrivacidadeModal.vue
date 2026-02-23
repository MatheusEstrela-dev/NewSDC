<script setup>
import { 
  ShieldCheckIcon, 
  LockClosedIcon, 
  EyeIcon, 
  ServerIcon, 
  UserGroupIcon,
  GlobeAltIcon,
  DocumentTextIcon,
  XMarkIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6 md:p-10">
            <!-- Backdrop -->
            <div 
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="close"
            ></div>
            
            <!-- Modal Container -->
            <div class="relative bg-white dark:bg-slate-900 w-full max-w-4xl max-h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden transform transition-all">
                
                <!-- Header -->
                <div class="bg-emerald-800 dark:bg-emerald-900 p-6 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <ShieldCheckIcon class="w-8 h-8" />
                        </div>
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold leading-tight">Política de Privacidade</h2>
                            <p class="text-emerald-200 text-xs md:text-sm font-medium">Proteção de Dados e Conformidade LGPD</p>
                        </div>
                    </div>
                    <button 
                        @click="close"
                        class="p-2 hover:bg-white/10 rounded-full transition-colors"
                        title="Fechar"
                    >
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <!-- Content Area -->
                <div class="flex-1 overflow-y-auto p-6 md:p-10 bg-slate-50 dark:bg-slate-950 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700">
                    
                    <!-- Disclaimer de Conformidade -->
                    <div class="text-center mb-8 pb-6 border-b border-slate-200 dark:border-slate-700">
                        <p class="text-sm md:text-base text-slate-600 dark:text-slate-300 leading-relaxed max-w-3xl mx-auto">
                            Este documento descreve como o <strong class="text-emerald-700 dark:text-emerald-400">Sistema de Defesa Civil (SDC)</strong> 
                            coleta, utiliza, armazena e protege os dados pessoais dos usuários, em conformidade com a 
                            <strong>Lei Geral de Proteção de Dados (Lei nº 13.709/2018)</strong>.
                        </p>
                    </div>

                    <!-- Sections -->
                    <div class="space-y-8">
                        
                        <!-- Section 1 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-lg group-hover:bg-emerald-700 group-hover:text-white transition-colors duration-300">
                                    <UserGroupIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">1. Controlador de Dados</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base">
                                <p>O controlador dos dados pessoais é a <strong>Coordenadoria Estadual de Defesa Civil de Minas Gerais (CEDEC-MG)</strong>, órgão responsável pelo desenvolvimento e manutenção do SDC.</p>
                            </div>
                        </div>

                        <!-- Section 2 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-lg group-hover:bg-emerald-700 group-hover:text-white transition-colors duration-300">
                                    <DocumentTextIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">2. Dados Coletados</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base space-y-4">
                                <p><strong>2.1. Dados de Identificação:</strong> Nome completo, CPF, matrícula funcional, cargo, órgão de lotação e endereço de e-mail institucional.</p>
                                <p><strong>2.2. Dados de Acesso:</strong> Registros de login (data, hora, IP), histórico de navegação dentro do sistema e logs de auditoria.</p>
                                <p><strong>2.3. Dados Operacionais:</strong> Informações inseridas no exercício das funções (RATs, protocolos, vistorias, pareceres técnicos).</p>
                            </div>
                        </div>

                        <!-- Section 3 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-lg group-hover:bg-emerald-700 group-hover:text-white transition-colors duration-300">
                                    <EyeIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">3. Finalidade do Tratamento</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base">
                                <p class="mb-4">Os dados são tratados exclusivamente para:</p>
                                <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <li class="flex gap-2 bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-slate-200 dark:border-slate-700">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">A)</span>
                                        Gestão de desastres e proteção civil
                                    </li>
                                    <li class="flex gap-2 bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-slate-200 dark:border-slate-700">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">B)</span>
                                        Monitoramento e auditoria de acessos
                                    </li>
                                    <li class="flex gap-2 bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-slate-200 dark:border-slate-700">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">C)</span>
                                        Cumprimento de obrigações legais
                                    </li>
                                    <li class="flex gap-2 bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-slate-200 dark:border-slate-700">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">D)</span>
                                        Geração de relatórios estatísticos
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Section 4 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-lg group-hover:bg-emerald-700 group-hover:text-white transition-colors duration-300">
                                    <GlobeAltIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">4. Compartilhamento de Dados</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base space-y-4">
                                <p><strong>4.1.</strong> Os dados podem ser compartilhados com órgãos públicos competentes (TCE-MG, CGE, MP) mediante requisição formal ou para cumprimento de obrigações legais.</p>
                                <p><strong>4.2.</strong> Não há transferência internacional de dados. Todas as informações são armazenadas em servidores localizados no território brasileiro.</p>
                            </div>
                        </div>

                        <!-- Section 5 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-lg group-hover:bg-emerald-700 group-hover:text-white transition-colors duration-300">
                                    <ServerIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">5. Segurança e Armazenamento</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base space-y-4">
                                <p><strong>5.1.</strong> Os dados são protegidos por criptografia em trânsito (TLS 1.3) e em repouso (AES-256).</p>
                                <p><strong>5.2.</strong> O acesso é controlado por autenticação multifator e permissões granulares baseadas em perfis (RBAC).</p>
                                <p><strong>5.3.</strong> Os dados são retidos pelo período necessário ao cumprimento das finalidades ou conforme exigido por legislação específica (mínimo de 5 anos para fins de auditoria).</p>
                            </div>
                        </div>

                        <!-- Section 6 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-lg group-hover:bg-emerald-700 group-hover:text-white transition-colors duration-300">
                                    <LockClosedIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">6. Direitos do Titular</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base">
                                <p class="mb-4">Conforme a LGPD, o titular dos dados tem direito a:</p>
                                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                    <span class="px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full">Confirmação de Tratamento</span>
                                    <span class="px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full">Acesso aos Dados</span>
                                    <span class="px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full">Correção de Dados Incompletos</span>
                                    <span class="px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full">Portabilidade</span>
                                    <span class="px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full">Revogação de Consentimento</span>
                                </div>
                                <p class="mt-4 text-sm italic">Para exercer seus direitos, entre em contato através do canal de Suporte ou pelo e-mail <strong>dpo@defesacivil.mg.gov.br</strong>.</p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer / Aviso Implícito -->
                <div class="p-6 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shrink-0">
                    <div class="max-w-3xl mx-auto">
                        <!-- Aviso de Conformidade Implícita -->
                        <div class="flex items-start gap-3 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                            <CheckCircleIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <p class="text-sm text-emerald-800 dark:text-emerald-200 leading-relaxed">
                                Ao utilizar o SDC, você <strong>consente com o tratamento de seus dados pessoais</strong> conforme descrito nesta Política de Privacidade, em conformidade com a LGPD (Lei nº 13.709/2018).
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
