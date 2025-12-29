<template>
  <AuthenticatedLayout :title="`Órgão: ${orgao.nome}`">
    <div class="orgao-show">
      <!-- Header com ações -->
      <div class="header-section">
        <div class="header-content">
          <Button
            variant="ghost"
            @click="handleBack"
          >
            ← Voltar
          </Button>

          <div class="header-title">
            <Heading level="1">{{ orgao.nome }}</Heading>
            <div class="header-meta">
              <Badge :color="orgao.statusBadgeColor">{{ orgao.statusLabel }}</Badge>
              <Badge :color="getTipoBadgeColor(orgao.tipo)">{{ orgao.tipoLabel }}</Badge>
              <Text variant="muted">Código: {{ orgao.codigo }}</Text>
            </div>
          </div>
        </div>

        <div class="header-actions" v-if="canManage">
          <Button
            variant="outline"
            @click="handleEdit"
          >
            Editar
          </Button>
          <Button
            variant="danger"
            @click="handleDelete"
          >
            Excluir
          </Button>
        </div>
      </div>

      <!-- Conteúdo em grid -->
      <div class="content-grid">
        <!-- Card: Informações Básicas -->
        <CardBase title="Informações Básicas">
          <div class="info-grid">
            <div class="info-item">
              <Text variant="label">Código</Text>
              <Text variant="mono">{{ orgao.codigo }}</Text>
            </div>

            <div class="info-item">
              <Text variant="label">Nome</Text>
              <Text>{{ orgao.nome }}</Text>
            </div>

            <div class="info-item">
              <Text variant="label">Tipo</Text>
              <Badge :color="getTipoBadgeColor(orgao.tipo)">{{ orgao.tipoLabel }}</Badge>
            </div>

            <div class="info-item">
              <Text variant="label">Status</Text>
              <Badge :color="orgao.statusBadgeColor">{{ orgao.statusLabel }}</Badge>
            </div>

            <div class="info-item" v-if="orgao.municipio">
              <Text variant="label">Município</Text>
              <Text>{{ orgao.municipio.nome }} - {{ orgao.municipio.uf }}</Text>
            </div>

            <div class="info-item" v-if="orgao.email">
              <Text variant="label">E-mail</Text>
              <Text>{{ orgao.email }}</Text>
            </div>

            <div class="info-item" v-if="orgao.telefone">
              <Text variant="label">Telefone</Text>
              <Text>{{ orgao.telefone }}</Text>
            </div>

            <div class="info-item" v-if="orgao.endereco">
              <Text variant="label">Endereço</Text>
              <Text>{{ orgao.endereco }}</Text>
            </div>
          </div>
        </CardBase>

        <!-- Card: Hierarquia -->
        <CardBase title="Hierarquia" v-if="orgao.hierarquia && orgao.hierarquia.length > 0">
          <div class="hierarquia-tree">
            <div
              v-for="(nivel, index) in orgao.hierarquia"
              :key="nivel.id"
              class="hierarquia-nivel"
            >
              <div class="hierarquia-connector" v-if="index > 0"></div>
              <div class="hierarquia-card" :class="{ 'is-current': nivel.id === orgao.id }">
                <Badge :color="getTipoBadgeColor(nivel.tipo)" size="sm">
                  {{ nivel.tipoLabel }}
                </Badge>
                <Text variant="bold">{{ nivel.nome }}</Text>
                <Text variant="muted" size="sm">{{ nivel.codigo }}</Text>
              </div>
            </div>
          </div>
        </CardBase>

        <!-- Card: Responsável -->
        <CardBase title="Responsável" v-if="orgao.responsavel_nome">
          <div class="info-grid">
            <div class="info-item">
              <Text variant="label">Nome</Text>
              <Text>{{ orgao.responsavel_nome }}</Text>
            </div>

            <div class="info-item" v-if="orgao.responsavel_cpf">
              <Text variant="label">CPF</Text>
              <Text variant="mono">{{ orgao.responsavel_cpf }}</Text>
            </div>

            <div class="info-item" v-if="orgao.responsavel_email">
              <Text variant="label">E-mail</Text>
              <Text>{{ orgao.responsavel_email }}</Text>
            </div>

            <div class="info-item" v-if="orgao.responsavel_telefone">
              <Text variant="label">Telefone</Text>
              <Text>{{ orgao.responsavel_telefone }}</Text>
            </div>
          </div>
        </CardBase>

        <!-- Card: Usuários Vinculados -->
        <CardBase title="Usuários Vinculados" class="full-width">
          <div class="usuarios-header">
            <Text variant="muted">{{ usuarios.length }} usuário(s) vinculado(s)</Text>
            <Button
              v-if="canVincularUsuarios"
              variant="primary"
              size="sm"
              @click="handleVincularUsuario"
            >
              Vincular Usuário
            </Button>
          </div>

          <table class="usuarios-table" v-if="usuarios.length > 0">
            <thead>
              <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Função</th>
                <th>Principal</th>
                <th v-if="canVincularUsuarios">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="usuario in usuarios" :key="usuario.id">
                <td>{{ usuario.name }}</td>
                <td>{{ usuario.email }}</td>
                <td>
                  <Badge color="gray">{{ usuario.pivot.funcao }}</Badge>
                </td>
                <td>
                  <Badge v-if="usuario.pivot.is_principal" color="blue">Sim</Badge>
                  <Text v-else variant="muted">—</Text>
                </td>
                <td v-if="canVincularUsuarios">
                  <Button
                    variant="ghost"
                    size="sm"
                    @click="handleDesvincularUsuario(usuario.id)"
                  >
                    Remover
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-else class="empty-state">
            <Text variant="muted">Nenhum usuário vinculado</Text>
          </div>
        </CardBase>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';

const props = defineProps({
  orgao: {
    type: Object,
    required: true,
  },
  usuarios: {
    type: Array,
    default: () => [],
  },
  canManage: {
    type: Boolean,
    default: false,
  },
  canVincularUsuarios: {
    type: Boolean,
    default: false,
  },
});

const getTipoBadgeColor = (tipo) => {
  const colors = {
    compdec: 'blue',
    redec: 'yellow',
    cedec: 'purple',
  };
  return colors[tipo] || 'gray';
};

const handleBack = () => {
  router.visit(route('compdec.index'));
};

const handleEdit = () => {
  router.visit(route('compdec.edit', props.orgao.id));
};

const handleDelete = () => {
  if (confirm('Tem certeza que deseja excluir este órgão?')) {
    router.delete(route('compdec.destroy', props.orgao.id));
  }
};

const handleVincularUsuario = () => {
  // TODO: Abrir modal de vincular usuário
  alert('Funcionalidade em desenvolvimento');
};

const handleDesvincularUsuario = (userId) => {
  if (confirm('Tem certeza que deseja remover este vínculo?')) {
    router.delete(route('compdec.usuarios.desvincular', {
      orgao: props.orgao.id,
      usuario: userId,
    }));
  }
};
</script>

<style scoped>
.orgao-show {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 2rem;
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  flex: 1;
}

.header-title {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.header-meta {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
}

.full-width {
  grid-column: 1 / -1;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.hierarquia-tree {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.hierarquia-nivel {
  position: relative;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.hierarquia-connector {
  position: absolute;
  left: 1.25rem;
  top: -0.5rem;
  width: 2px;
  height: 1rem;
  background: #e5e7eb;
}

.hierarquia-card {
  display: flex;
  gap: 0.75rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
  flex: 1;
}

.hierarquia-card.is-current {
  background: #eff6ff;
  border-color: #3b82f6;
}

.usuarios-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.usuarios-table {
  width: 100%;
  border-collapse: collapse;
}

.usuarios-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.usuarios-table th {
  padding: 0.75rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  color: #6b7280;
}

.usuarios-table td {
  padding: 0.75rem;
  border-bottom: 1px solid #f3f4f6;
}

.empty-state {
  padding: 2rem;
  text-align: center;
}
</style>
