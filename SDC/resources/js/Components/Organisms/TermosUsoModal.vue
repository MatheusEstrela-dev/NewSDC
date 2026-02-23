<script setup>
import { 
  ShieldCheckIcon, 
  DocumentTextIcon, 
  CheckCircleIcon, 
  ExclamationTriangleIcon, 
  LockClosedIcon, 
  ScaleIcon, 
  UserPlusIcon,
  XMarkIcon
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
                <div class="bg-blue-900 p-6 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <ShieldCheckIcon class="w-8 h-8" />
                        </div>
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold leading-tight">Sistema de Defesa Civil (SDC)</h2>
                            <p class="text-blue-100 text-xs md:text-sm font-medium">Termo de Uso e Responsabilidade do Portal Geral</p>
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

                <!-- Conformidade Banner -->
                <div class="flex-1 overflow-y-auto p-6 md:p-10 bg-slate-50 dark:bg-slate-950 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-700">
                    <!-- Disclaimer de Conformidade -->
                    <div class="text-center mb-8 pb-6 border-b border-slate-200 dark:border-slate-700">
                        <p class="text-sm md:text-base text-slate-600 dark:text-slate-300 leading-relaxed max-w-3xl mx-auto">
                            Este é um <strong class="text-blue-700 dark:text-blue-400">sistema oficial do Estado de Minas Gerais</strong>. 
                            Ao navegar e utilizar o SDC, você declara ciência e concordância integral com os 
                            <strong>termos de uso</strong>, <strong>responsabilidade técnica</strong> e 
                            <strong>tratamento de dados (LGPD)</strong>.
                        </p>
                    </div>

                    <!-- Warning Alert -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 p-4 mb-8 rounded-r-lg">
                        <div class="flex gap-3">
                            <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 dark:text-amber-500 shrink-0" />
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                <strong>Atenção:</strong> Leia atentamente todos os itens abaixo. Este sistema é monitorado e de uso estritamente institucional. O acesso requer ciência integral das responsabilidades civis e criminais.
                            </p>
                        </div>
                    </div>

                    <!-- Section Component Inline for Vue -->
                    <div class="space-y-8">
                        
                        <!-- Section 1 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg group-hover:bg-blue-700 group-hover:text-white transition-colors duration-300">
                                    <DocumentTextIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">1. Natureza do Serviço</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base">
                                <p>O Sistema de Defesa Civil (SDC) é uma plataforma oficial do Estado de Minas Gerais, destinada à gestão de riscos, desastres e assistência técnica. O uso deste portal é estritamente institucional e técnico.</p>
                            </div>
                        </div>

                        <!-- Section 2 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg group-hover:bg-blue-700 group-hover:text-white transition-colors duration-300">
                                    <UserPlusIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">2. Responsabilidade Individual</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base space-y-4">
                                <p><strong>2.1. Identificação:</strong> Todo acesso às áreas restritas do SDC é monitorado. O usuário é legalmente responsável por todas as ações realizadas sob suas credenciais (login/senha ou certificado digital).</p>
                                <p><strong>2.2. Uso Indevido:</strong> Qualquer tentativa de acesso a módulos não autorizados, extração em massa de dados (scraping) ou ataques de negação de serviço (DoS) será rastreada e reportada às autoridades policiais e órgãos de controle.</p>
                                <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg border border-red-100 dark:border-red-800 text-red-900 dark:text-red-200 text-xs md:text-sm">
                                    <strong>2.3. Fé Pública:</strong> O usuário declara ciência de que os dados inseridos em qualquer módulo do sistema (RAT, cadastros, vistorias, alertas) possuem presunção de veracidade. A inserção de dados falsos ou a alteração maliciosa de informações públicas constitui crime de Falsidade Ideológica (Art. 299 CP) e Inserção de dados falsos em sistema de informações (Art. 313-A CP).
                                </div>
                            </div>
                        </div>

                        <!-- Section 3 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg group-hover:bg-blue-700 group-hover:text-white transition-colors duration-300">
                                    <LockClosedIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">3. Propriedade Intelectual</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base">
                                <p class="mb-3">3.1. O conteúdo técnico, códigos, logotipos e bases de dados são de propriedade exclusiva do Estado de Minas Gerais.</p>
                                <p>3.2. É proibida a reprodução parcial ou total dos dados para fins comerciais. O uso das informações para fins acadêmicos ou jornalísticos deve citar obrigatoriamente a fonte <strong>"Sistema de Defesa Civil - SDC/MG"</strong>.</p>
                            </div>
                        </div>

                        <!-- Section 4 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg group-hover:bg-blue-700 group-hover:text-white transition-colors duration-300">
                                    <CheckCircleIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">4. Dever de Cuidado e Segurança</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base">
                                <p class="mb-4">O usuário obriga-se a:</p>
                                <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <li class="flex gap-2 bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-slate-200 dark:border-slate-700 italic">
                                        <span class="text-blue-600 dark:text-blue-400 font-bold">A)</span> Encerrar a sessão (logout) sempre que se afastar do terminal.
                                    </li>
                                    <li class="flex gap-2 bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-slate-200 dark:border-slate-700 italic">
                                        <span class="text-blue-600 dark:text-blue-400 font-bold">B)</span> Não utilizar senhas óbvias ou compartilhá-las com terceiros.
                                    </li>
                                    <li class="flex gap-2 bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-slate-200 dark:border-slate-700 italic">
                                        <span class="text-blue-600 dark:text-blue-400 font-bold">C)</span> Reportar imediatamente qualquer suspeita de comprometimento da conta.
                                    </li>
                                    <li class="flex gap-2 bg-white dark:bg-slate-800 p-3 rounded shadow-sm border border-slate-200 dark:border-slate-700 italic">
                                        <span class="text-blue-600 dark:text-blue-400 font-bold">D)</span> Não difundir conteúdos ilícitos, políticos ou de assédio.
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Section 5 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg group-hover:bg-blue-700 group-hover:text-white transition-colors duration-300">
                                    <ShieldCheckIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">5. Tratamento de Dados (LGPD)</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base">
                                <p class="mb-3">5.1. O usuário atua como <strong>Operador de Dados</strong> (Lei 13.709/2018) e deve garantir a estrita confidencialidade, sendo proibido o uso desses dados para fins particulares.</p>
                                <p>5.2. O descumprimento das normas de privacidade acarretará responsabilidade objetiva do agente perante o Estado.</p>
                            </div>
                        </div>

                        <!-- Section 6 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg group-hover:bg-blue-700 group-hover:text-white transition-colors duration-300">
                                    <ScaleIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">6. Limitação de Responsabilidade</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base">
                                <p>A Administração empenha esforços para manter o SDC disponível 24/7. O usuário reconhece que instabilidades técnicas podem ocorrer. O Estado não se responsabiliza por danos decorrentes de falhas de conexão ou mau uso do hardware.</p>
                            </div>
                        </div>

                        <!-- Section 7 -->
                        <div class="group">
                            <div class="flex items-center gap-3 mb-3 pb-2 border-b border-slate-200 dark:border-slate-800">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg group-hover:bg-blue-700 group-hover:text-white transition-colors duration-300">
                                    <ExclamationTriangleIcon class="w-5 h-5" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white uppercase tracking-tight">7. Sanções</h3>
                            </div>
                            <div class="pl-2 text-slate-600 dark:text-slate-300 leading-relaxed text-sm md:text-base">
                                <p class="mb-2">O descumprimento deste termo sujeita o usuário a:</p>
                                <div class="flex flex-wrap gap-2 text-xs font-bold uppercase">
                                    <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full">Suspensão de Acesso</span>
                                    <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full">Processo Administrativo (PAD)</span>
                                    <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full">Responsabilização Civil e Criminal</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer / Aviso Implícito -->
                <div class="p-6 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shrink-0">
                    <div class="max-w-3xl mx-auto">
                        <!-- Aviso de Conformidade Implícita -->
                        <div class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                            <CheckCircleIcon class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                            <p class="text-sm text-blue-800 dark:text-blue-200 leading-relaxed">
                                Ao utilizar o Sistema de Defesa Civil (SDC), você <strong>automaticamente declara ciência e concordância</strong> integral com todos os termos de uso, responsabilidade técnica e tratamento de dados (LGPD) descritos acima.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
