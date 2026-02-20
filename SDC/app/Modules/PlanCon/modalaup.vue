<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design de Upload Premium - Electric Edition</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #020617;
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(30, 58, 138, 0.25) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(8, 145, 178, 0.2) 0%, transparent 40%);
        }

        [v-cloak] { display: none; }

        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.7);
        }

        .drop-zone {
            transition: all 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
            background: rgba(30, 41, 59, 0.15);
            border: 2px dashed rgba(51, 65, 85, 1);
            position: relative;
        }

        /* Efeito de "Acender" os pontilhados */
        .drop-zone.active {
            border-color: #22d3ee;
            background: rgba(8, 145, 178, 0.08);
            box-shadow: 
                0 0 30px rgba(34, 211, 238, 0.3), 
                inset 0 0 20px rgba(34, 211, 238, 0.05);
            transform: scale(1.02);
            animation: neon-pulse 2s infinite ease-in-out;
        }

        @keyframes neon-pulse {
            0%, 100% { 
                border-color: rgba(34, 211, 238, 0.4);
                box-shadow: 0 0 15px rgba(34, 211, 238, 0.1);
            }
            50% { 
                border-color: rgba(34, 211, 238, 1);
                box-shadow: 0 0 35px rgba(34, 211, 238, 0.4);
            }
        }

        .file-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            transition: all 0.3s ease;
        }

        .file-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(34, 211, 238, 0.2);
            transform: translateY(-2px);
        }

        .neon-btn {
            background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.4);
            transition: all 0.3s ease;
        }

        .neon-btn:hover:not(:disabled) {
            box-shadow: 0 0 40px rgba(6, 182, 212, 0.6);
            transform: translateY(-1px);
        }

        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        .custom-scroll:hover::-webkit-scrollbar-thumb {
            background: rgba(34, 211, 238, 0.2);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 antialiased">

    <div id="app" v-cloak class="w-full max-w-xl">
        <div class="glass-panel rounded-[2.5rem] overflow-hidden flex flex-col relative">
            
            <!-- Barra Estética de Cores -->
            <div class="h-1.5 flex w-full">
                <div class="h-full flex-1 bg-slate-900"></div>
                <div class="h-full flex-1 bg-blue-800"></div>
                <div class="h-full flex-1 bg-blue-600"></div>
                <div class="h-full flex-1 bg-cyan-400"></div>
                <div class="h-full flex-1 bg-blue-600"></div>
                <div class="h-full flex-1 bg-blue-800"></div>
                <div class="h-full flex-1 bg-slate-900"></div>
            </div>

            <div class="p-8 md:p-12">
                <!-- Header -->
                <header class="mb-12 flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-2 h-2 rounded-full bg-cyan-400 shadow-[0_0_8px_#22d3ee]"></span>
                            <span class="text-cyan-400 text-[10px] font-black uppercase tracking-[0.4em]">Cloud Sync Active</span>
                        </div>
                        <h1 class="text-4xl font-bold text-white tracking-tighter">Asset Manager</h1>
                    </div>
                    <div class="p-3 bg-white/5 rounded-2xl border border-white/5">
                        <i data-lucide="layers" class="w-5 h-5 text-slate-400"></i>
                    </div>
                </header>

                <!-- Drop Zone com Pontilhado que Acende -->
                <div 
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                    @click="$refs.fileInput.click()"
                    :class="['drop-zone group relative rounded-[1.5rem] p-12 flex flex-col items-center justify-center cursor-pointer overflow-hidden', isDragging ? 'active' : '']"
                >
                    <input type="file" ref="fileInput" class="hidden" multiple @change="handleFileSelect">
                    
                    <!-- Brilho de fundo no hover -->
                    <div class="absolute inset-0 bg-cyan-500/0 group-hover:bg-cyan-500/5 transition-colors duration-500"></div>

                    <div class="relative mb-8">
                        <div :class="['absolute inset-0 blur-2xl rounded-full scale-150 transition-all duration-700', isDragging ? 'bg-cyan-500/40' : 'bg-cyan-500/10 group-hover:bg-cyan-500/20']"></div>
                        <div class="relative bg-slate-950 border border-slate-800 p-5 rounded-2xl shadow-inner group-hover:border-cyan-500/50 transition-all">
                            <i data-lucide="upload" :class="['w-8 h-8 transition-all duration-500', isDragging ? 'text-white scale-110' : 'text-cyan-400']"></i>
                        </div>
                    </div>

                    <h3 class="text-slate-100 font-bold text-xl mb-2">Arraste seus assets</h3>
                    <p class="text-slate-500 text-sm text-center font-medium">Os pontilhados acendem ao detetar seus arquivos.<br><span class="text-slate-600 text-xs">Suporta FIG, PNG e PDF</span></p>
                </div>

                <!-- Lista de Ficheiros -->
                <div v-if="files.length > 0" class="mt-12 space-y-4 max-h-[350px] overflow-y-auto pr-3 custom-scroll">
                    <div class="flex items-center justify-between px-1">
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Fila de Carregamento</span>
                        <button @click="files = []" class="text-[10px] font-bold text-red-500/70 hover:text-red-400 transition-colors uppercase tracking-widest">Remover todos</button>
                    </div>

                    <div v-for="(file, index) in files" :key="index" 
                        class="file-card flex items-center p-4 rounded-2xl group/card relative overflow-hidden">
                        
                        <div class="w-12 h-12 rounded-xl bg-slate-900/80 flex items-center justify-center mr-4 border border-white/5 group-hover/card:border-cyan-500/30 transition-colors">
                            <i v-if="file.type.includes('image')" data-lucide="image" class="w-5 h-5 text-blue-400"></i>
                            <i v-else-if="file.type.includes('pdf')" data-lucide="file-text" class="w-5 h-5 text-red-400"></i>
                            <i v-else data-lucide="box" class="w-5 h-5 text-slate-400"></i>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="text-sm font-bold text-slate-200 truncate pr-6">{{ file.name }}</h4>
                                <span class="text-[9px] text-slate-500 font-black tracking-tighter">{{ formatSize(file.size) }}</span>
                            </div>
                            
                            <!-- Barra de Progresso Elétrica -->
                            <div class="w-full bg-black/40 h-1.5 rounded-full overflow-hidden p-[1px]">
                                <div 
                                    class="h-full bg-gradient-to-r from-blue-700 via-cyan-500 to-white transition-all duration-1000 ease-out rounded-full shadow-[0_0_10px_rgba(6,182,212,0.5)]"
                                    :style="{ width: file.progress + '%' }"
                                ></div>
                            </div>
                        </div>

                        <button @click.stop="removeFile(index)" class="ml-4 p-2 bg-slate-900 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-400/10 transition-all">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Footer de Ações -->
                <footer class="mt-12 flex items-center gap-6">
                    <div class="hidden sm:flex flex-col">
                        <span class="text-[10px] font-bold text-slate-600 uppercase">Segurança</span>
                        <span class="text-[11px] text-slate-400 font-medium">SSL 256-bit</span>
                    </div>
                    
                    <button 
                        @click="startUpload" 
                        :disabled="files.length === 0 || isUploading"
                        class="flex-1 neon-btn text-white font-black text-xs tracking-[0.2em] py-5 rounded-2xl flex items-center justify-center disabled:opacity-20 disabled:grayscale transition-all"
                    >
                        <i v-if="isUploading" data-lucide="refresh-cw" class="w-4 h-4 mr-3 animate-spin"></i>
                        {{ isUploading ? 'SINCRONIZANDO...' : 'FINALIZAR PROCESSO' }}
                    </button>
                </footer>
            </div>
        </div>

        <p class="text-center text-slate-700 text-[9px] mt-8 font-bold uppercase tracking-[0.5em] opacity-50">High-End Design System v2.0</p>
    </div>

    <script>
        const { createApp, ref, onMounted, nextTick } = Vue;

        createApp({
            setup() {
                const files = ref([]);
                const isDragging = ref(false);
                const isUploading = ref(false);

                const initIcons = () => {
                    nextTick(() => {
                        if (window.lucide) lucide.createIcons();
                    });
                };

                onMounted(() => {
                    initIcons();
                });

                const handleFileSelect = (e) => addFiles(e.target.files);
                const handleDrop = (e) => {
                    isDragging.value = false;
                    addFiles(e.dataTransfer.files);
                };

                const addFiles = (newFiles) => {
                    for (let file of newFiles) {
                        const fileObj = {
                            name: file.name,
                            size: file.size,
                            type: file.type,
                            progress: 0
                        };
                        files.value.push(fileObj);
                        simulateProgress(files.value.length - 1);
                    }
                    initIcons();
                };

                const simulateProgress = (idx) => {
                    let interval = setInterval(() => {
                        if (files.value[idx] && files.value[idx].progress < 100) {
                            files.value[idx].progress += Math.random() * 15;
                            if (files.value[idx].progress > 100) files.value[idx].progress = 100;
                        } else {
                            clearInterval(interval);
                        }
                    }, 500);
                };

                const removeFile = (idx) => {
                    files.value.splice(idx, 1);
                    initIcons();
                };

                const formatSize = (bytes) => {
                    if (bytes === 0) return '0B';
                    const i = Math.floor(Math.log(bytes) / Math.log(1024));
                    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + ['B', 'KB', 'MB', 'GB'][i];
                };

                const startUpload = () => {
                    isUploading.value = true;
                    setTimeout(() => {
                        isUploading.value = false;
                        files.value = [];
                        initIcons();
                    }, 3000);
                };

                return { files, isDragging, isUploading, handleFileSelect, handleDrop, removeFile, formatSize, startUpload };
            }
        }).mount('#app');
    </script>
</body>
</html>