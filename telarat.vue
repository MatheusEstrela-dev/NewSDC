<!DOCTYPE html>
<html lang="pt-BR" class="dark">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SDC - Módulo RAT</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Vue 3 -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Configuração do Tailwind -->
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              gray: {
                900: "#111827",
                950: "#030712",
              },
            },
            animation: {
              "fade-in": "fadeIn 0.3s ease-out forwards",
            },
            keyframes: {
              fadeIn: {
                from: { opacity: "0", transform: "translateY(5px)" },
                to: { opacity: "1", transform: "translateY(0)" },
              },
            },
          },
        },
      };
    </script>

    <style>
      /* Scrollbar Personalizada */
      .custom-scrollbar::-webkit-scrollbar {
        height: 4px;
      }
      .custom-scrollbar::-webkit-scrollbar-track {
        background: #111827;
      }
      .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #374151;
        border-radius: 2px;
      }

      /* Ajustes finos de input date no dark mode */
      input[type="datetime-local"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
      }
    </style>
  </head>
  <body
    class="bg-gray-950 text-gray-100 font-sans selection:bg-blue-500 selection:text-white"
  >
    <div id="app" class="min-h-screen flex flex-col">
      <!-- Topbar -->
      <header
        class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex items-center justify-between sticky top-0 z-20"
      >
        <div class="flex items-center gap-3">
          <div
            class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center font-bold text-lg text-white"
          >
            S
          </div>
          <h1 class="text-xl font-semibold tracking-tight text-white">
            SDC <span class="text-gray-500">|</span> Novo RAT
          </h1>
        </div>
        <div class="flex gap-2">
          <span
            class="px-3 py-1 bg-yellow-500/10 text-yellow-500 text-xs font-medium rounded-full border border-yellow-500/20"
          >
            Rascunho
          </span>
        </div>
      </header>

      <main class="max-w-7xl mx-auto p-6 flex-grow w-full">
        <!-- Navegação de Abas -->
        <nav
          class="flex overflow-x-auto border-b border-gray-800 mb-6 gap-1 custom-scrollbar"
        >
          <button
            v-for="tab in visibleTabs"
            :key="tab.id"
            @click="selectTab(tab.id)"
            :class="[
              'flex items-center gap-2 px-5 py-4 text-sm font-medium transition-colors border-b-2 whitespace-nowrap outline-none focus:ring-2 focus:ring-blue-500/50 focus:rounded-t',
              currentTab === tab.id
                ? 'border-blue-500 text-blue-400 bg-gray-900/50'
                : 'border-transparent text-gray-400 hover:text-gray-200 hover:bg-gray-900/30',
            ]"
          >
            <i :data-lucide="tab.icon" class="w-4 h-4"></i>
            {{ tab.label }}

            <span
              v-if="tab.count !== undefined"
              :class="[
                'ml-1 px-2 py-0.5 text-xs rounded-full',
                currentTab === tab.id
                  ? 'bg-blue-500/20 text-blue-300'
                  : 'bg-gray-800 text-gray-400',
              ]"
            >
              {{ tab.count }}
            </span>
          </button>
        </nav>

        <!-- Conteúdo das Abas -->
        <div class="min-h-[500px] pb-20">
          <!-- ABA 1: DADOS GERAIS -->
          <section
            v-show="currentTab === 'gerais'"
            class="space-y-6 animate-fade-in"
          >
            <!-- Card: Atendimento -->
            <div
              class="bg-gray-900 rounded-lg border border-gray-800 overflow-hidden"
            >
              <div
                class="px-5 py-3 border-b border-gray-800 bg-gray-900/50 flex items-center gap-2"
              >
                <i data-lucide="clock" class="w-4 h-4 text-blue-500"></i>
                <h3 class="font-medium text-blue-500">Atendimento</h3>
              </div>

              <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Data do Fato -->
                <div class="space-y-1.5">
                  <label
                    class="text-xs font-medium text-gray-400 uppercase tracking-wider"
                  >
                    Data/Hora do Fato <span class="text-red-500">*</span>
                  </label>
                  <input
                    type="datetime-local"
                    v-model="formData.dadosGerais.data_fato"
                    class="w-full bg-gray-950 border border-gray-700 rounded p-2.5 text-sm text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all placeholder-gray-600"
                  />
                </div>

                <!-- Início Atividade -->
                <div class="space-y-1.5">
                  <label
                    class="text-xs font-medium text-gray-400 uppercase tracking-wider"
                  >
                    Início da Atividade <span class="text-red-500">*</span>
                  </label>
                  <input
                    type="datetime-local"
                    v-model="formData.dadosGerais.data_inicio_atividade"
                    class="w-full bg-gray-950 border border-gray-700 rounded p-2.5 text-sm text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all"
                  />
                </div>

                <!-- Término Atividade -->
                <div class="space-y-1.5">
                  <label
                    class="text-xs font-medium text-gray-400 uppercase tracking-wider"
                  >
                    Término da Atividade
                  </label>
                  <input
                    type="datetime-local"
                    v-model="formData.dadosGerais.data_termino_atividade"
                    class="w-full bg-gray-950 border border-gray-700 rounded p-2.5 text-sm text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all"
                  />
                </div>
              </div>
            </div>

            <!-- Card: Natureza e Config -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Natureza -->
              <div class="bg-gray-900 rounded-lg border border-gray-800">
                <div
                  class="px-5 py-3 border-b border-gray-800 flex items-center gap-2"
                >
                  <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                  <h3 class="font-medium text-gray-300">Natureza</h3>
                </div>
                <div class="p-5 space-y-4">
                  <div class="space-y-1.5">
                    <label class="text-sm text-gray-400"
                      >Classificação COBRADE</label
                    >
                    <select
                      v-model="formData.dadosGerais.nat_cobrade_id"
                      class="w-full bg-gray-950 border border-gray-700 rounded p-2.5 text-sm text-gray-200 focus:border-blue-500 outline-none"
                    >
                      <option value="" disabled>
                        Selecione a classificação...
                      </option>
                      <option value="1">1.3.2.1.0 - Tempestade Local</option>
                      <option value="2">1.2.1.0.0 - Inundação</option>
                      <option value="3">
                        1.1.3.3.1 - Deslizamento de Planície
                      </option>
                    </select>
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-sm text-gray-400"
                      >Nome da Operação (Opcional)</label
                    >
                    <input
                      type="text"
                      v-model="formData.dadosGerais.nat_nome_operacao"
                      placeholder="Ex: Operação Chuvas de Verão"
                      class="w-full bg-gray-950 border border-gray-700 rounded p-2.5 text-sm text-gray-200 focus:border-blue-500 outline-none placeholder-gray-600"
                    />
                  </div>
                </div>
              </div>

              <!-- Configurações -->
              <div class="bg-gray-900 rounded-lg border border-gray-800">
                <div
                  class="px-5 py-3 border-b border-gray-800 flex items-center gap-2"
                >
                  <i data-lucide="settings" class="w-4 h-4 text-gray-400"></i>
                  <h3 class="font-medium text-gray-300">
                    Configurações do RAT
                  </h3>
                </div>
                <div class="p-5 space-y-5">
                  <!-- Toggle Vistoria -->
                  <div
                    class="flex items-center justify-between p-3 rounded bg-gray-950 border border-gray-800"
                  >
                    <div class="flex gap-3">
                      <div class="mt-1 bg-purple-500/20 p-1.5 rounded h-fit">
                        <i
                          data-lucide="clipboard"
                          class="w-4 h-4 text-purple-400"
                        ></i>
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-200">
                          Realizou Vistoria Imobiliária?
                        </p>
                        <p class="text-xs text-gray-500">
                          Habilita a aba de vistoria técnica
                        </p>
                      </div>
                    </div>
                    <button
                      @click="toggleVistoria"
                      :class="[
                        'w-11 h-6 flex items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-blue-500',
                        formData.dadosGerais.tem_vistoria
                          ? 'bg-blue-600'
                          : 'bg-gray-700',
                      ]"
                    >
                      <span
                        :class="[
                          'w-4 h-4 bg-white rounded-full shadow transform transition-transform ml-1',
                          formData.dadosGerais.tem_vistoria
                            ? 'translate-x-5'
                            : 'translate-x-0',
                        ]"
                      ></span>
                    </button>
                  </div>

                  <!-- Readonly Info -->
                  <div class="space-y-1.5 opacity-60">
                    <label class="text-sm text-gray-400"
                      >Unidade Responsável</label
                    >
                    <input
                      type="text"
                      value="COMPDEC - Município Modelo/MG"
                      disabled
                      class="w-full bg-gray-800 border border-gray-700 rounded p-2 text-sm text-gray-400 cursor-not-allowed"
                    />
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- ABA 2: RECURSOS -->
          <section v-show="currentTab === 'recursos'" class="animate-fade-in">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-medium text-gray-300">
                Recursos Empregados
              </h2>
              <button
                @click="addRecursoMock"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded flex items-center gap-2 transition-colors"
              >
                <i data-lucide="plus" class="w-4 h-4"></i> Adicionar Recurso
              </button>
            </div>

            <div
              v-if="formData.recursos.length === 0"
              class="bg-gray-900 border border-gray-800 rounded-lg p-10 text-center text-gray-500 flex flex-col items-center"
            >
              <i data-lucide="truck" class="w-12 h-12 mb-3 text-gray-700"></i>
              <p>Nenhum recurso adicionado a esta ocorrência.</p>
            </div>

            <div v-else class="grid gap-4">
              <div
                v-for="(rec, index) in formData.recursos"
                :key="index"
                class="bg-gray-900 border border-gray-800 p-4 rounded-lg flex justify-between items-center group hover:border-gray-700 transition-colors"
              >
                <div class="flex items-center gap-4">
                  <div class="bg-gray-800 p-2 rounded text-gray-400">
                    <i
                      :data-lucide="
                        rec.recurso_tipo === 'viatura' ? 'truck' : 'user'
                      "
                      class="w-5 h-5"
                    ></i>
                  </div>
                  <div>
                    <p class="font-medium text-gray-200 capitalize">
                      {{ rec.recurso_tipo }}
                    </p>
                    <p class="text-sm text-gray-500">{{ rec.descricao }}</p>
                  </div>
                </div>
                <button
                  class="text-gray-500 hover:text-red-400 p-2 opacity-0 group-hover:opacity-100 transition-opacity"
                >
                  <i data-lucide="trash" class="w-4 h-4"></i>
                </button>
              </div>
            </div>
          </section>

          <!-- ABA 3: ENVOLVIDOS -->
          <section v-show="currentTab === 'envolvidos'" class="animate-fade-in">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-medium text-gray-300">
                Pessoas Envolvidas
              </h2>
              <button
                @click="addEnvolvidoMock"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded flex items-center gap-2 transition-colors"
              >
                <i data-lucide="plus" class="w-4 h-4"></i> Adicionar Pessoa
              </button>
            </div>
            <div
              class="bg-gray-900 border border-gray-800 rounded-lg p-10 text-center text-gray-500 flex flex-col items-center"
            >
              <i data-lucide="users" class="w-12 h-12 mb-3 text-gray-700"></i>
              <p>Nenhum envolvido registrado.</p>
            </div>
          </section>

          <!-- ABA 4: VISTORIA -->
          <section v-show="currentTab === 'vistoria'" class="animate-fade-in">
            <div
              class="bg-purple-900/10 border border-purple-500/20 rounded-lg p-4 mb-6"
            >
              <div class="flex gap-3">
                <i
                  data-lucide="clipboard"
                  class="w-5 h-5 text-purple-400 mt-0.5"
                ></i>
                <div>
                  <h3 class="text-purple-300 font-medium">
                    Relatório de Vistoria Técnica
                  </h3>
                  <p class="text-sm text-purple-400/70">
                    Preencha os dados abaixo referentes à avaliação estrutural e
                    de risco do imóvel.
                  </p>
                </div>
              </div>
            </div>

            <div
              class="bg-gray-900 border border-gray-800 rounded-lg divide-y divide-gray-800"
            >
              <!-- Accordions Simulados -->
              <div
                class="p-4 hover:bg-gray-800/30 cursor-pointer flex justify-between items-center transition-colors"
              >
                <span class="font-medium text-gray-300"
                  >4.1 Identificação e Imóvel</span
                >
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
              </div>
              <div
                class="p-4 hover:bg-gray-800/30 cursor-pointer flex justify-between items-center transition-colors"
              >
                <span class="font-medium text-gray-300"
                  >4.6 Infraestrutura e Riscos</span
                >
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
              </div>
              <div
                class="p-4 hover:bg-gray-800/30 cursor-pointer flex justify-between items-center transition-colors"
              >
                <span class="font-medium text-gray-300"
                  >4.11 Patologias Identificadas</span
                >
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500"></i>
              </div>
            </div>
          </section>

          <!-- ABA 5: HISTÓRICO -->
          <section v-show="currentTab === 'historico'" class="animate-fade-in">
            <div class="relative pl-6 border-l border-gray-800 space-y-8 my-4">
              <div class="relative">
                <div
                  class="absolute -left-[31px] bg-green-900/50 border border-green-500/50 w-4 h-4 rounded-full mt-1"
                ></div>
                <p class="text-sm text-gray-500 mb-1">Hoje, 14:30 - Sistema</p>
                <p class="text-gray-300">
                  Rascunho criado pelo usuário
                  <span class="text-blue-400">Agente Silva</span>.
                </p>
              </div>
              <div class="relative">
                <div
                  class="absolute -left-[31px] bg-blue-900/50 border border-blue-500/50 w-4 h-4 rounded-full mt-1"
                ></div>
                <p class="text-sm text-gray-500 mb-1">
                  Hoje, 14:32 - Agente Silva
                </p>
                <p class="text-gray-300">
                  Preenchimento inicial dos dados gerais da ocorrência.
                </p>
              </div>
            </div>

            <div class="mt-6">
              <label class="text-sm text-gray-400 mb-2 block"
                >Adicionar Observação</label
              >
              <textarea
                rows="3"
                class="w-full bg-gray-900 border border-gray-800 rounded p-3 text-sm text-gray-200 focus:border-blue-500 outline-none placeholder-gray-600"
                placeholder="Digite uma nova observação..."
              ></textarea>
              <div class="flex justify-end mt-2">
                <button
                  class="text-sm bg-gray-800 hover:bg-gray-700 text-gray-300 px-4 py-2 rounded transition-colors"
                >
                  Registrar no Histórico
                </button>
              </div>
            </div>
          </section>
        </div>
      </main>

      <!-- Footer -->
      <footer
        class="fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-800 p-4 z-10"
      >
        <div class="max-w-7xl mx-auto flex justify-end gap-3">
          <button
            class="px-6 py-2.5 rounded text-sm font-medium text-gray-400 hover:text-white hover:bg-gray-800 transition-colors"
          >
            Cancelar
          </button>
          <button
            class="px-6 py-2.5 rounded text-sm font-medium bg-gray-800 text-blue-400 border border-gray-700 hover:bg-gray-750 transition-colors"
          >
            Salvar Rascunho
          </button>
          <button
            class="px-6 py-2.5 rounded text-sm font-medium bg-blue-600 text-white hover:bg-blue-500 shadow-lg shadow-blue-900/20 transition-all flex items-center gap-2"
          >
            <i data-lucide="check-circle" class="w-4 h-4"></i> Finalizar RAT
          </button>
        </div>
      </footer>
    </div>

    <!-- Script da Aplicação -->
    <script>
      const { createApp, ref, computed, nextTick, onUpdated, onMounted } = Vue;

      createApp({
        setup() {
          const currentTab = ref("gerais");

          // Dados Mockados
          const formData = ref({
            status: 0,
            dadosGerais: {
              data_fato: "",
              data_inicio_atividade: "",
              data_termino_atividade: "",
              nat_cobrade_id: "",
              nat_nome_operacao: "",
              tem_vistoria: false,
              local_municipio: "",
            },
            recursos: [],
            envolvidos: [],
            vistoria: {},
          });

          // Definição das Abas
          const tabs = computed(() => [
            {
              id: "gerais",
              label: "Dados Gerais",
              icon: "info",
              hidden: false,
            },
            {
              id: "recursos",
              label: "Recursos Empregados",
              icon: "truck",
              count: formData.value.recursos.length,
              hidden: false,
            },
            {
              id: "envolvidos",
              label: "Envolvidos",
              icon: "users",
              count: formData.value.envolvidos.length,
              hidden: false,
            },
            {
              id: "vistoria",
              label: "Vistoria",
              icon: "clipboard",
              hidden: !formData.value.dadosGerais.tem_vistoria,
            },
            {
              id: "historico",
              label: "Histórico",
              icon: "clock",
              hidden: false,
            },
          ]);

          const visibleTabs = computed(() =>
            tabs.value.filter((t) => !t.hidden)
          );

          // Funções de Controle
          const selectTab = (tabId) => {
            currentTab.value = tabId;
          };

          const toggleVistoria = () => {
            formData.value.dadosGerais.tem_vistoria =
              !formData.value.dadosGerais.tem_vistoria;
            if (
              !formData.value.dadosGerais.tem_vistoria &&
              currentTab.value === "vistoria"
            ) {
              currentTab.value = "gerais";
            }
          };

          const addRecursoMock = () => {
            formData.value.recursos.push({
              seq: formData.value.recursos.length + 1,
              recurso_tipo: "viatura",
              descricao: "Viatura L200 - Placa ABC-1234",
            });
          };

          const addEnvolvidoMock = () => {
            alert("Funcionalidade de modal (Mock)");
          };

          // Atualizar Ícones do Lucide após renderização do Vue
          const refreshIcons = () => {
            nextTick(() => {
              lucide.createIcons();
            });
          };

          onMounted(() => refreshIcons());
          onUpdated(() => refreshIcons());

          return {
            currentTab,
            formData,
            visibleTabs,
            selectTab,
            toggleVistoria,
            addRecursoMock,
            addEnvolvidoMock,
          };
        },
      }).mount("#app");
    </script>
  </body>
</html>
