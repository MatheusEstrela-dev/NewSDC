<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
              <!-- Header -->
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center justify-between pb-4 border-b">
                  <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                    Registrar Recebimento de Material
                  </DialogTitle>
                  <button
                    type="button"
                    class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none"
                    @click="$emit('close')"
                  >
                    <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                  </button>
                </div>

                <!-- Tabs -->
                <div class="mt-4">
                  <nav class="flex space-x-4" aria-label="Tabs">
                    <button
                      v-for="tab in tabs"
                      :key="tab.id"
                      :class="[
                        currentTab === tab.id
                          ? 'bg-blue-100 text-blue-700'
                          : 'text-gray-500 hover:text-gray-700',
                        'px-3 py-2 font-medium text-sm rounded-md'
                      ]"
                      @click="currentTab = tab.id"
                    >
                      {{ tab.name }}
                    </button>
                  </nav>
                </div>

                <!-- Tab Content -->
                <form @submit.prevent="handleSubmit" class="mt-6">
                  <!-- Aba 1: Dados do Transporte -->
                  <div v-show="currentTab === 'transporte'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Placa do Veículo *</label>
                        <input
                          v-model="formData.placa_veiculo"
                          type="text"
                          required
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Transportadora</label>
                        <input
                          v-model="formData.transportadora"
                          type="text"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        />
                      </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Nome do Motorista *</label>
                        <input
                          v-model="formData.motorista_nome"
                          type="text"
                          required
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Documento (RG/CPF)</label>
                        <input
                          v-model="formData.motorista_documento"
                          type="text"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        />
                      </div>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Doca de Descarga</label>
                      <input
                        v-model="formData.doca_descarga"
                        type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                      />
                    </div>
                  </div>

                  <!-- Aba 2: Documentação -->
                  <div v-show="currentTab === 'documentacao'" class="space-y-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Número da Nota Fiscal *</label>
                      <input
                        v-model="formData.nota_fiscal"
                        type="text"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Ordem de Compra (Opcional)</label>
                      <input
                        v-model="formData.ordem_compra_id"
                        type="number"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                      />
                    </div>
                  </div>

                  <!-- Aba 3: Conferência -->
                  <div v-show="currentTab === 'conferencia'" class="space-y-4">
                    <div class="flex justify-between items-center">
                      <h4 class="font-medium text-gray-900">Itens Recebidos</h4>
                      <button
                        type="button"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none"
                        @click="adicionarItem"
                      >
                        Adicionar Item
                      </button>
                    </div>

                    <div class="overflow-x-auto">
                      <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                          <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produto</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qtd Nota</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qtd Conferida</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lote</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Validade</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                          <tr v-for="(item, index) in formData.itens" :key="index">
                            <td class="px-3 py-2">
                              <select
                                v-model="item.product_id"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                              >
                                <option value="">Selecione...</option>
                                <option v-for="product in products" :key="product.id" :value="product.id">
                                  {{ product.nome }}
                                </option>
                              </select>
                            </td>
                            <td class="px-3 py-2">
                              <input
                                v-model.number="item.quantidade_nota"
                                type="number"
                                required
                                min="1"
                                class="block w-20 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                              />
                            </td>
                            <td class="px-3 py-2">
                              <input
                                v-model.number="item.quantidade_conferida"
                                type="number"
                                required
                                min="0"
                                class="block w-20 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                              />
                            </td>
                            <td class="px-3 py-2">
                              <input
                                v-model="item.numero_lote"
                                type="text"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                              />
                            </td>
                            <td class="px-3 py-2">
                              <input
                                v-model="item.data_validade"
                                type="date"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                              />
                            </td>
                            <td class="px-3 py-2">
                              <button
                                type="button"
                                class="text-red-600 hover:text-red-900"
                                @click="removerItem(index)"
                              >
                                <TrashIcon class="h-5 w-5" />
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Footer -->
                  <div class="mt-6 flex justify-end space-x-3">
                    <button
                      type="button"
                      class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none"
                      @click="$emit('close')"
                    >
                      Cancelar
                    </button>
                    <button
                      type="submit"
                      class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none"
                      :disabled="isSubmitting"
                    >
                      {{ isSubmitting ? 'Salvando...' : 'Finalizar Recebimento' }}
                    </button>
                  </div>
                </form>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  products: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'submit'])

const currentTab = ref('transporte')
const isSubmitting = ref(false)

const tabs = [
  { id: 'transporte', name: 'Dados do Transporte' },
  { id: 'documentacao', name: 'Documentação' },
  { id: 'conferencia', name: 'Conferência Física' }
]

const formData = reactive({
  placa_veiculo: '',
  transportadora: '',
  motorista_nome: '',
  motorista_documento: '',
  doca_descarga: '',
  nota_fiscal: '',
  ordem_compra_id: null,
  itens: []
})

const adicionarItem = () => {
  formData.itens.push({
    product_id: '',
    quantidade_nota: 1,
    quantidade_conferida: 1,
    numero_lote: '',
    data_validade: '',
    tem_avaria: false
  })
}

const removerItem = (index) => {
  formData.itens.splice(index, 1)
}

const handleSubmit = async () => {
  isSubmitting.value = true
  try {
    emit('submit', { ...formData })
  } finally {
    isSubmitting.value = false
  }
}
</script>
