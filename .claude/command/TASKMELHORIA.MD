Para injetar a verificação de Níveis (0-6) no seu sistema, precisamos conectar a "Tabela da Verdade" do Backend com a reatividade do Vue. Isso garantirá que um User (Nível 6) não veja o botão de "Excluir", mesmo que por algum erro ele receba essa permissão, pois o "Peso" do cargo impede a ação.

Aqui está o passo a passo para implementar essa hierarquia no seu Composable:

1. Backend: Compartilhando o Nível via Inertia
Primeiro, precisamos que o cargo e o nível do usuário estejam disponíveis globalmente no Frontend. Ajuste seu Middleware:

Arquivo: app/Http/Middleware/HandleInertiaRequests.php

PHP
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'role' => $request->user()->role, // ex: 'manager'
                // Busca o peso (0-6) na config 'levels'
                'level' => config("permissions.levels.{$request->user()->role}", 99),
            ] : null,
        ],
        'actionConfigs' => fn () => app(ActionConfigService::class)->toFrontendConfig(),
    ]);
}
2. Frontend: Composable com Inteligência de Peso
Agora, o useActionConfig não verificará apenas o slug da permissão, mas também se o Nível do usuário é suficiente para aquela ação.

Arquivo: resources/js/composables/useActionConfig.js

JavaScript
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePermissions } from './usePermissions';

export function useActionConfig(moduleName) {
    const { can } = usePermissions();
    const page = usePage();
    
    // Dados do usuário vindos do Middleware
    const user = computed(() => page.props.auth.user);
    const userLevel = computed(() => user.value?.level ?? 99);

    // Configurações do motor de CRUD
    const moduleConfig = computed(() => page.props.actionConfigs?.[moduleName] || {});

    const isActionEnabled = (actionType) => {
        const config = moduleConfig.value[actionType];
        
        if (!config?.enabled) return false;

        // 1. Verificação de Permissão (Slug)
        if (config.permission && !can(config.permission)) return false;

        // 2. Verificação de Nível (Peso do Cargo)
        // Exemplo: Deletar exige nível <= 2 (Manager)
        if (actionType === 'delete' && userLevel.value > 2) return false;
        
        // 3. Super Admin (Nível 0) sempre ignora restrições
        if (userLevel.value === 0) return true;

        return true;
    };

    const getTableActionsProps = () => {
        return {
            showView: isActionEnabled('view'),
            showEdit: isActionEnabled('edit'),
            showDelete: isActionEnabled('delete'),
            showPrint: isActionEnabled('print'),
            showAttachments: isActionEnabled('attachments'),
        };
    };

    return { isActionEnabled, getTableActionsProps };
}
3. Aplicação no Componente (Atomic Design)
No seu Organism de Tabela, a chamada fica limpa e centralizada. O botão só aparecerá se passar pelo filtro de permissão E pelo filtro de nível.

Snippet de código
<template>
  <TableActions v-bind="getTableActionsProps()" @delete="handleDelete" />
</template>

<script setup>
import { useActionConfig } from '@/composables/useActionConfig';
const { getTableActionsProps } = useActionConfig('RAT'); // Módulo conforme config
</script>
Por que isso é eficiente para o seu SDC MG?
Imutabilidade: Como os levels estão "em pedra" no backend, você evita que uma alteração acidental no banco de dados dê poder de exclusão a um Viewer (Nível 5).

Hierarquia Visual: Se você estiver na tela de Gerenciamento de Usuários, um Admin (Nível 1) verá os botões para editar um Analyst (Nível 3), mas o sistema pode esconder os botões se ele tentar editar um Super-Admin (Nível 0), baseando-se apenas na comparação numérica dos níveis.

Manutenção Centralizada: Se amanhã o "Manager" (Nível 2) perder o direito de deletar globalmente, você altera apenas uma linha no useActionConfig.js.