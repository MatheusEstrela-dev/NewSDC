<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDC - Health Check Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes pulse-green {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-green {
            animation: pulse-green 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    <div id="app" class="min-h-screen">
        <!-- Header -->
        <header class="bg-gray-800 shadow-lg border-b border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <i class="fas fa-heartbeat text-3xl text-green-500 pulse-green"></i>
                        <div>
                            <h1 class="text-2xl font-bold">SDC Health Dashboard</h1>
                            <p class="text-gray-400 text-sm">Sistema de Defesa Civil - Monitoramento em Tempo Real</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <div class="text-sm text-gray-400">Status Geral</div>
                            <div class="flex items-center space-x-2">
                                <span :class="healthData.status === 'healthy' ? 'text-green-500' : 'text-yellow-500'" class="text-xl font-bold">
                                    @{{ healthData.status === 'healthy' ? 'SAUDÁVEL' : 'DEGRADADO' }}
                                </span>
                                <div :class="healthData.status === 'healthy' ? 'bg-green-500' : 'bg-yellow-500'" class="w-3 h-3 rounded-full pulse-green"></div>
                            </div>
                        </div>
                        <button @click="refreshData" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-sync-alt" :class="{'fa-spin': loading}"></i>
                            <span>Atualizar</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- System Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Uptime Card -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 slide-in">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Uptime</p>
                            <p class="text-2xl font-bold mt-1">@{{ formatUptime(healthData.performance?.uptime_seconds) }}</p>
                        </div>
                        <div class="bg-blue-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-clock text-2xl text-blue-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Memory Card -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 slide-in" style="animation-delay: 0.1s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Memória</p>
                            <p class="text-2xl font-bold mt-1">@{{ healthData.system?.memory_usage_mb }} MB</p>
                            <p class="text-xs text-gray-500">Pico: @{{ healthData.system?.memory_peak_mb }} MB</p>
                        </div>
                        <div class="bg-purple-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-memory text-2xl text-purple-500"></i>
                        </div>
                    </div>
                </div>

                <!-- CPU Card -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 slide-in" style="animation-delay: 0.2s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">CPU Load</p>
                            <p class="text-2xl font-bold mt-1">@{{ cpuAverage }}</p>
                            <p class="text-xs text-gray-500">1min / 5min / 15min</p>
                        </div>
                        <div class="bg-orange-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-microchip text-2xl text-orange-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Requests Card -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 slide-in" style="animation-delay: 0.3s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Requisições/min</p>
                            <p class="text-2xl font-bold mt-1">@{{ healthData.performance?.requests_per_minute || 0 }}</p>
                        </div>
                        <div class="bg-green-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-chart-line text-2xl text-green-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <!-- Response Time Card -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 slide-in" style="animation-delay: 0.4s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Response Time</p>
                            <p class="text-2xl font-bold mt-1" :class="(healthData.performance?.avg_response_time_ms || 0) > 500 ? 'text-yellow-500' : 'text-green-500'">
                                @{{ healthData.performance?.avg_response_time_ms || 0 }} ms
                            </p>
                            <p class="text-xs text-gray-500">Média (últimas 100)</p>
                        </div>
                        <div class="bg-indigo-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-tachometer-alt text-2xl text-indigo-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Error Rate Card -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 slide-in" style="animation-delay: 0.5s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Error Rate</p>
                            <p class="text-2xl font-bold mt-1" :class="(healthData.performance?.error_rate_percent || 0) > 5 ? 'text-red-500' : ((healthData.performance?.error_rate_percent || 0) > 1 ? 'text-yellow-500' : 'text-green-500')">
                                @{{ healthData.performance?.error_rate_percent || 0 }}%
                            </p>
                            <p class="text-xs text-gray-500">Taxa de erros</p>
                        </div>
                        <div class="bg-red-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Cache Hit Rate Card -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 slide-in" style="animation-delay: 0.6s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Cache Hit Rate</p>
                            <p class="text-2xl font-bold mt-1" :class="(healthData.metrics?.cache_hit_rate || 0) > 80 ? 'text-green-500' : ((healthData.metrics?.cache_hit_rate || 0) > 50 ? 'text-yellow-500' : 'text-red-500')">
                                @{{ healthData.metrics?.cache_hit_rate || 0 }}%
                            </p>
                            <p class="text-xs text-gray-500">Taxa de acerto</p>
                        </div>
                        <div class="bg-yellow-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-bolt text-2xl text-yellow-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Database Connections Card -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 slide-in" style="animation-delay: 0.7s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">DB Connections</p>
                            <p class="text-2xl font-bold mt-1">@{{ healthData.metrics?.database_connections?.active || 0 }}</p>
                            <p class="text-xs text-gray-500">Ativas / Máx: @{{ healthData.metrics?.database_connections?.max_used || 0 }}</p>
                        </div>
                        <div class="bg-teal-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-plug text-2xl text-teal-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Active Sessions Card -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 slide-in" style="animation-delay: 0.8s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 text-sm">Active Sessions</p>
                            <p class="text-2xl font-bold mt-1">@{{ healthData.metrics?.active_sessions || 0 }}</p>
                            <p class="text-xs text-gray-500">Usuários ativos</p>
                        </div>
                        <div class="bg-pink-500 bg-opacity-20 p-3 rounded-lg">
                            <i class="fas fa-users text-2xl text-pink-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Components Status -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Database -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-database text-2xl text-blue-500"></i>
                            <div>
                                <h3 class="text-lg font-bold">Database (MySQL)</h3>
                                <p class="text-sm text-gray-400">@{{ healthData.checks?.database?.connection }}</p>
                            </div>
                        </div>
                        <span :class="getStatusBadge(healthData.checks?.database?.status)">
                            @{{ healthData.checks?.database?.status?.toUpperCase() || 'N/A' }}
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Latência:</span>
                            <span class="font-mono">@{{ healthData.checks?.database?.latency_ms || 0 }} ms</span>
                        </div>
                    </div>
                </div>

                <!-- Redis -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-server text-2xl text-red-500"></i>
                            <div>
                                <h3 class="text-lg font-bold">Redis Cache</h3>
                                <p class="text-sm text-gray-400">In-Memory Store</p>
                            </div>
                        </div>
                        <span :class="getStatusBadge(healthData.checks?.redis?.status)">
                            @{{ healthData.checks?.redis?.status?.toUpperCase() || 'N/A' }}
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Latência:</span>
                            <span class="font-mono">@{{ healthData.checks?.redis?.latency_ms || 0 }} ms</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Memória Usada:</span>
                            <span class="font-mono">@{{ healthData.checks?.redis?.memory_used_mb || 0 }} MB</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Clientes Conectados:</span>
                            <span class="font-mono">@{{ healthData.checks?.redis?.connected_clients || 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Queue -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-tasks text-2xl text-yellow-500"></i>
                            <div>
                                <h3 class="text-lg font-bold">Queue System</h3>
                                <p class="text-sm text-gray-400">@{{ healthData.checks?.queue?.driver }}</p>
                            </div>
                        </div>
                        <span :class="getStatusBadge(healthData.checks?.queue?.status)">
                            @{{ healthData.checks?.queue?.status?.toUpperCase() || 'N/A' }}
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Jobs Pendentes:</span>
                            <span class="font-mono font-bold" :class="healthData.checks?.queue?.pending_jobs > 500 ? 'text-yellow-500' : 'text-green-500'">
                                @{{ healthData.checks?.queue?.pending_jobs || 0 }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-500">
                            Filas: @{{ (healthData.checks?.queue?.queues_monitored || []).join(', ') }}
                        </div>
                    </div>
                </div>

                <!-- Storage -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-hdd text-2xl text-purple-500"></i>
                            <div>
                                <h3 class="text-lg font-bold">Storage</h3>
                                <p class="text-sm text-gray-400">Disk Space</p>
                            </div>
                        </div>
                        <span :class="getStatusBadge(healthData.checks?.storage?.status)">
                            @{{ healthData.checks?.storage?.status?.toUpperCase() || 'N/A' }}
                        </span>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Total:</span>
                            <span class="font-mono">@{{ healthData.checks?.storage?.total_gb || 0 }} GB</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Livre:</span>
                            <span class="font-mono">@{{ healthData.checks?.storage?.free_gb || 0 }} GB</span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all duration-500"
                                :class="healthData.checks?.storage?.used_percent > 90 ? 'bg-red-500' : (healthData.checks?.storage?.used_percent > 75 ? 'bg-yellow-500' : 'bg-green-500')"
                                :style="`width: ${healthData.checks?.storage?.used_percent || 0}%`"
                            ></div>
                        </div>
                        <div class="text-xs text-center text-gray-400">
                            @{{ healthData.checks?.storage?.used_percent || 0 }}% usado
                        </div>
                    </div>
                </div>

                <!-- Docker Network -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <i class="fab fa-docker text-2xl text-cyan-500"></i>
                            <div>
                                <h3 class="text-lg font-bold">Docker Network</h3>
                                <p class="text-sm text-gray-400">@{{ healthData.checks?.docker_network?.network_name || 'N/A' }}</p>
                            </div>
                        </div>
                        <span :class="getStatusBadge(healthData.checks?.docker_network?.status)">
                            @{{ healthData.checks?.docker_network?.status?.toUpperCase() || 'N/A' }}
                        </span>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Containers:</span>
                            <span class="font-mono">@{{ healthData.checks?.docker_network?.running_containers || 0 }}/@{{ healthData.checks?.docker_network?.total_containers || 0 }}</span>
                        </div>
                        <div v-if="healthData.checks?.docker_network?.error" class="text-xs text-red-500">
                            <i class="fas fa-exclamation-circle"></i> @{{ healthData.checks.docker_network.error }}
                        </div>
                        <div v-else-if="healthData.checks?.docker_network?.message" class="text-xs text-yellow-500">
                            <i class="fas fa-info-circle"></i> @{{ healthData.checks.docker_network.message }}
                        </div>
                        <div v-else-if="healthData.checks?.docker_network?.containers && healthData.checks.docker_network.containers.length > 0" class="space-y-2 max-h-48 overflow-y-auto">
                            <div v-for="container in healthData.checks.docker_network.containers" :key="container.name" class="flex justify-between items-center text-xs bg-gray-700 rounded px-2 py-1">
                                <div class="flex items-center space-x-2">
                                    <div :class="container.status === 'running' ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></div>
                                    <span class="font-mono text-gray-300">@{{ container.name }}</span>
                                </div>
                                <div class="flex items-center space-x-3 text-gray-400">
                                    <span class="text-xs">@{{ container.ip }}</span>
                                    <span :class="container.status === 'running' ? 'text-green-400' : 'text-red-400'" class="text-xs">@{{ container.status }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-gray-500">
                            <i class="fas fa-info-circle"></i> Nenhum container encontrado
                        </div>
                        <div v-if="healthData.checks?.docker_network?.missing_essential && healthData.checks.docker_network.missing_essential.length > 0" class="text-xs text-yellow-500 mt-2">
                            <i class="fas fa-exclamation-triangle"></i> Faltando: @{{ healthData.checks.docker_network.missing_essential.join(', ') }}
                        </div>
                    </div>
                </div>

                <!-- Monitoring Services -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-chart-area text-2xl text-indigo-500"></i>
                            <div>
                                <h3 class="text-lg font-bold">Monitoring</h3>
                                <p class="text-sm text-gray-400">Grafana & Prometheus</p>
                            </div>
                        </div>
                        <span :class="getStatusBadge(healthData.checks?.monitoring?.status)">
                            @{{ healthData.checks?.monitoring?.status?.toUpperCase() || 'N/A' }}
                        </span>
                    </div>
                    <div class="space-y-3">
                        <!-- Grafana Status -->
                        <div class="flex items-center justify-between text-sm bg-gray-700 rounded px-3 py-2">
                            <div class="flex items-center space-x-2">
                                <div :class="healthData.checks?.monitoring?.grafana?.online ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></div>
                                <span class="font-mono text-gray-300">Grafana</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span v-if="healthData.checks?.monitoring?.grafana?.online" class="text-green-400 text-xs">
                                    <i class="fas fa-check-circle"></i> Online
                                </span>
                                <span v-else class="text-red-400 text-xs">
                                    <i class="fas fa-times-circle"></i> Offline
                                </span>
                                <span v-if="healthData.checks?.monitoring?.grafana?.latency_ms" class="text-gray-400 text-xs">
                                    @{{ healthData.checks.monitoring.grafana.latency_ms }}ms
                                </span>
                            </div>
                        </div>
                        <!-- Prometheus Status -->
                        <div class="flex items-center justify-between text-sm bg-gray-700 rounded px-3 py-2">
                            <div class="flex items-center space-x-2">
                                <div :class="healthData.checks?.monitoring?.prometheus?.online ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></div>
                                <span class="font-mono text-gray-300">Prometheus</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span v-if="healthData.checks?.monitoring?.prometheus?.online" class="text-green-400 text-xs">
                                    <i class="fas fa-check-circle"></i> Online
                                </span>
                                <span v-else class="text-red-400 text-xs">
                                    <i class="fas fa-times-circle"></i> Offline
                                </span>
                                <span v-if="healthData.checks?.monitoring?.prometheus?.latency_ms" class="text-gray-400 text-xs">
                                    @{{ healthData.checks.monitoring.prometheus.latency_ms }}ms
                                </span>
                            </div>
                        </div>
                        <!-- Error Messages -->
                        <div v-if="healthData.checks?.monitoring?.grafana?.error" class="text-xs text-yellow-500">
                            <i class="fas fa-info-circle"></i> Grafana: @{{ healthData.checks.monitoring.grafana.error }}
                        </div>
                        <div v-if="healthData.checks?.monitoring?.prometheus?.error" class="text-xs text-yellow-500">
                            <i class="fas fa-info-circle"></i> Prometheus: @{{ healthData.checks.monitoring.prometheus.error }}
                        </div>
                        <div v-if="healthData.checks?.monitoring?.error" class="text-xs text-red-500">
                            <i class="fas fa-exclamation-circle"></i> @{{ healthData.checks.monitoring.error }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h3 class="text-lg font-bold mb-4 flex items-center space-x-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <span>Informações do Sistema</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-gray-400 text-sm">PHP Version</p>
                        <p class="font-mono text-lg">@{{ healthData.system?.php_version }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Laravel Version</p>
                        <p class="font-mono text-lg">@{{ healthData.system?.laravel_version }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Last Update</p>
                        <p class="font-mono text-lg">@{{ new Date(healthData.timestamp).toLocaleTimeString('pt-BR') }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-gray-500 text-sm">
                <p>SDC - Sistema de Defesa Civil | Atualização automática a cada 5 segundos</p>
            </div>

        </main>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    healthData: {
                        status: 'loading',
                        checks: {},
                        system: {},
                        performance: {}
                    },
                    loading: false,
                    autoRefresh: null
                }
            },
            computed: {
                cpuAverage() {
                    const loads = this.healthData.system?.cpu_load || [0, 0, 0];
                    return loads.map(l => l.toFixed(2)).join(' / ');
                }
            },
            methods: {
                async refreshData() {
                    this.loading = true;
                    try {
                        const response = await fetch('/api/health/detailed');
                        this.healthData = await response.json();
                    } catch (error) {
                        console.error('Error fetching health data:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                formatUptime(seconds) {
                    if (!seconds) return '0s';
                    const days = Math.floor(seconds / 86400);
                    const hours = Math.floor((seconds % 86400) / 3600);
                    const minutes = Math.floor((seconds % 3600) / 60);

                    if (days > 0) return `${days}d ${hours}h`;
                    if (hours > 0) return `${hours}h ${minutes}m`;
                    return `${minutes}m`;
                },
                getStatusBadge(status) {
                    const badges = {
                        'ok': 'bg-green-500 bg-opacity-20 text-green-500 px-3 py-1 rounded-full text-xs font-bold',
                        'warning': 'bg-yellow-500 bg-opacity-20 text-yellow-500 px-3 py-1 rounded-full text-xs font-bold',
                        'error': 'bg-red-500 bg-opacity-20 text-red-500 px-3 py-1 rounded-full text-xs font-bold'
                    };
                    return badges[status] || badges.error;
                }
            },
            mounted() {
                // Buscar dados iniciais
                this.refreshData();

                // Auto-refresh a cada 5 segundos
                this.autoRefresh = setInterval(() => {
                    this.refreshData();
                }, 5000);
            },
            beforeUnmount() {
                if (this.autoRefresh) {
                    clearInterval(this.autoRefresh);
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
