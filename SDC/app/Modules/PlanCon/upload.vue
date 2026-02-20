<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Upload Premium - Vue.js</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        [v-cloak] { display: none; }
        
        body {
            background-color: #020617; /* Slate 950 */
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(30, 58, 138, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(56, 189, 248, 0.1) 0%, transparent 50%);
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(51, 65, 85, 0.5);
        }

        .drop-zone-active {
            background: rgba(30, 58, 138, 0.2);
            border-color: #38bdf8;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.2);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 10px;
        }

        .animate-pulse-slow {
            animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased">

    <div id="app" v-cloak class="w-full max-w-2xl">
        <div class="glass-card rounded-3xl shadow-2xl overflow-hidden">
            
            <!-- Barra Decorativa Superior (Inspirada na Imagem) -->
            <div class="h-1.5 w-full flex">
                <div class="flex-1 bg-blue-900"></div>
                <div class="flex-1 bg-blue-700"></div>
                <div class="flex-1 bg-blue-500"></div>
                <div class="flex-1 bg-sky-400"></div>
                <div class="flex-1 bg-blue-500"></div>
                <div class="flex-1 bg-blue-700"></div>
                <div class="flex-1 bg-blue-900"></div>
            </div>

            <div class="p-8 md:p-12">
                <!-- Cabeçalho -->
                <div class="mb-10 text-center">
                    <h1 class="text-3xl font-extrabold text-white tracking-tight mb-2">Central de Design</h1>
                    <p class="text-slate-400">Arraste os seus assets para a nuvem segura</p>
                </div>

                <!-- Zona de Drop -->
                <div 
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                    :class="['relative border-2 border-dashed rounded-2xl p-12 flex flex-col items-center justify-center transition-all duration-300 group cursor-pointer', 
                            isDragging ? 'drop-zone-active scale-[1.01]' : 'border-slate-700 hover:border-slate-500 hover:bg-slate-800/30']"
                    @click="$refs.fileInput.click()"
                >
                    <input 
                        type="file" 
                        ref="fileInput" 
                        class="hidden" 
                        multiple 
                        @change="handleFileSelect"
                        accept="image/*,.pdf,.zip,.fig"
                    >
                    
                    <div :class="['p-5 rounded-2xl mb-6 transition-all duration-500', isDragging ? 'bg-sky-500 shadow-lg shadow-sky-500/50 rotate-12' : 'bg-slate-800 group-hover:bg-slate-700']">
                        <i data-lucide="cloud-upload" :class="['w-10 h-10', isDragging ? 'text-white' : 'text-sky-400']"></i>
                    </div>
                    
                    <p class="text-xl font-semibold text-slate-200">Pronto para carregar?</p>
                    <p class="text-sm text-slate-500 mt-2 text-center max-w-xs">Solte os seus ficheiros FIG, PDF ou Imagens aqui (Máx. 50MB por ficheiro)</p>
                </div>

                <!-- Lista de Ficheiros com Scroll Personalizado -->
                <div v-if="files.length > 0" class="mt-10 space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-[0.2em]">Ficheiros Ativos ({{ files.length }})</h3>
                        <button @click="files = []" class="text-xs text-red-400 hover:text-red-300 transition-colors uppercase font-bold">Limpar tudo</button>
                    </div>
                    
                    <div v-for="(file, index) in files" :key="index" 
                        class="flex items-center p-4 bg-slate-800/40 rounded-xl border border-slate-700/50 group animate-in slide-in-from-bottom-2 duration-300">
                        
                        <!-- Ícone Dinâmico -->
                        <div class="mr-4 p-3 bg-slate-900 rounded-lg">
                            <i v-if="file.type.includes('image')" data-lucide="image" class="w-5 h-5 text-purple-400"></i>
                            <i v-else-if="file.type.includes('pdf')" data-lucide="file-text" class="w-5 h-5 text-red-400"></i>
                            <i v-else data-lucide="layers" class="w-5 h-5 text-sky-400"></i>
                        </div>

                        <!-- Detalhes -->
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-sm font-semibold text-slate-200 truncate">{{ file.name }}</p>
                                <span class="text-[10px] font-mono text-slate-500 bg-slate-900 px-2 py-0.5 rounded">{{ formatSize(file.size) }}</span>
                            </div>
                            
                            <!-- Barra de Progresso Estilizada -->
                            <div class="w-full bg-slate-900 rounded-full h-1 overflow-hidden">
                                <div 
                                    class="h-full rounded-full transition-all duration-700 ease-out bg-gradient-to-r from-blue-600 to-sky-400"
                                    :style="{ width: file.progress + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Eliminar -->
                        <button 
                            @click.stop="removeFile(index)"
                            class="ml-4 p-2 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-400/10 transition-all"
                        >
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Footer / CTA -->
                <div class="mt-10 pt-8 border-t border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center text-slate-500 text-xs">
                        <i data-lucide="shield-check" class="w-4 h-4 mr-2 text-emerald-500"></i>
                        Encriptação ponta-a-ponta ativa
                    </div>
                    
                    <button 
                        @click="uploadAll"
                        :disabled="files.length === 0 || isUploading"
                        class="w-full sm:w-auto px-10 py-3.5 bg-gradient-to-r from-blue-600 to-sky-500 text-white font-bold rounded-xl hover:shadow-[0_0_25px_rgba(56,189,248,0.4)] transition-all disabled:opacity-30 disabled:hover:shadow-none flex items-center justify-center"
                    >
                        <i v-if="isUploading" data-lucide="loader-2" class="w-5 h-5 mr-2 animate-spin"></i>
                        {{ isUploading ? 'A Processar...' : 'Iniciar Upload' }}
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-slate-600 text-[10px] mt-6 uppercase tracking-widest">Powered by Vue.js & Design System Blue</p>
    </div>

    <script>
        const { createApp, ref, onMounted, nextTick } = Vue;

        createApp({
            setup() {
                const files = ref([]);
                const isDragging = ref(false);
                const isUploading = ref(false);

                const refreshIcons = () => {
                    nextTick(() => {
                        if (window.lucide) {
                            lucide.createIcons();
                        }
                    });
                };

                onMounted(() => {
                    refreshIcons();
                });

                const handleFileSelect = (event) => {
                    addFiles(event.target.files);
                };

                const handleDrop = (event) => {
                    isDragging.value = false;
                    addFiles(event.dataTransfer.files);
                };

                const addFiles = (newFiles) => {
                    for (let file of newFiles) {
                        const fileObj = {
                            name: file.name,
                            size: file.size,
                            type: file.type,
                            progress: 0,
                            raw: file
                        };
                        files.value.push(fileObj);
                        simulateProgress(files.value.length - 1);
                    }
                    refreshIcons();
                };

                const simulateProgress = (index) => {
                    let interval = setInterval(() => {
                        if (!files.value[index]) {
                            clearInterval(interval);
                            return;
                        }
                        if (files.value[index].progress >= 100) {
                            clearInterval(interval);
                        } else {
                            files.value[index].progress += Math.floor(Math.random() * 15) + 5;
                            if (files.value[index].progress > 100) files.value[index].progress = 100;
                        }
                    }, 300);
                };

                const removeFile = (index) => {
                    files.value.splice(index, 1);
                    refreshIcons();
                };

                const formatSize = (bytes) => {
                    if (bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
                };

                const uploadAll = () => {
                    isUploading.value = true;
                    // Simulação de upload final
                    setTimeout(() => {
                        isUploading.value = false;
                        files.value = [];
                        refreshIcons();
                    }, 2500);
                };

                return {
                    files,
                    isDragging,
                    isUploading,
                    handleFileSelect,
                    handleDrop,
                    removeFile,
                    formatSize,
                    uploadAll
                };
            }
        }).mount('#app');
    </script>
</body>
</html>