import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePermissions } from '../auth/usePermissions';
import {
    ActionTypes,
    ActionDefaults,
    ActionToPropMap,
    getActionDefault
} from '../../domain/actions/ActionTypes';

/**
 * Composable para gerenciamento de configuracao de acoes de um modulo.
 * Segue os principios SOLID e DRY, centralizando a logica de toggle de botoes.
 *
 * @param {string} moduleName - Nome do modulo (ex: 'rat', 'pae', 'orgaos')
 * @returns {Object} Funcoes e estados para gerenciar acoes
 */
export function useActionConfig(moduleName) {
    const { can } = usePermissions();
    const page = usePage();

    /**
     * Configuracao do modulo vinda do backend.
     */
    const moduleConfig = computed(() => {
        return page.props.actionConfigs?.[moduleName] || {};
    });

    /**
     * Nivel hierarquico do usuario atual.
     */
    const userLevel = computed(() => {
        return page.props.auth?.user?.hierarchy_level ?? 99;
    });

    /**
     * Verifica se o usuario e Super Admin.
     */
    const isSuperAdmin = computed(() => {
        return page.props.auth?.user?.is_super_admin === true;
    });

    /**
     * Verifica se uma acao esta habilitada considerando:
     * 1. Super Admin bypass (nivel 0 ignora restricoes)
     * 2. Sobrescritas locais
     * 3. Configuracao do modulo (backend)
     * 4. Permissao do usuario
     * 5. Nivel hierarquico do usuario
     *
     * @param {string} action - Tipo da acao (ActionTypes)
     * @param {Object} overrides - Sobrescritas locais opcionais
     * @returns {boolean}
     */
    const isActionEnabled = (action, overrides = {}) => {
        if (isSuperAdmin.value) {
            return true;
        }

        if (Object.hasOwn(overrides, action) && overrides[action] === false) {
            return false;
        }

        const config = moduleConfig.value[action];

        if (!config) {
            return ActionDefaults[action]?.enabled ?? true;
        }

        if (!config.enabled) {
            return false;
        }

        if (config.permission && !can(config.permission)) {
            return false;
        }

        if (config.requiredLevel != null && userLevel.value > config.requiredLevel) {
            return false;
        }

        return true;
    };

    /**
     * Retorna a configuracao completa de uma acao.
     *
     * @param {string} action - Tipo da acao
     * @returns {Object|null}
     */
    const getActionConfig = (action) => {
        const backendConfig = moduleConfig.value[action] || {};
        const defaults = getActionDefault(action) || {};

        return {
            ...defaults,
            ...backendConfig,
            enabled: isActionEnabled(action)
        };
    };

    /**
     * Retorna metadata de uma acao.
     *
     * @param {string} action - Tipo da acao
     * @param {string} key - Chave do metadata (opcional)
     * @returns {Object|any}
     */
    const getActionMetadata = (action, key = null) => {
        const config = getActionConfig(action);
        const metadata = config?.metadata || {};

        if (key) {
            return metadata[key] ?? null;
        }

        return metadata;
    };

    /**
     * Retorna configuracao de modal de uma acao.
     *
     * @param {string} action - Tipo da acao
     * @returns {Object|null}
     */
    const getModalConfig = (action) => {
        const config = getActionConfig(action);
        return config?.modalConfig || null;
    };

    /**
     * Retorna a rota customizada de uma acao.
     *
     * @param {string} action - Tipo da acao
     * @returns {string|null}
     */
    const getActionRoute = (action) => {
        const config = getActionConfig(action);
        return config?.route || null;
    };

    /**
     * Gera as props para o componente TableActions.
     *
     * @param {Object} overrides - Sobrescritas por acao { [action]: boolean }
     * @returns {Object} Props para TableActions
     */
    const getTableActionsProps = (overrides = {}) => {
        const props = {};

        Object.entries(ActionToPropMap).forEach(([actionType, propName]) => {
            props[propName] = isActionEnabled(actionType, overrides);
        });

        return props;
    };

    /**
     * Gera props para TableActions com base em uma entidade.
     * Util para desabilitar acoes baseado no estado da entidade.
     *
     * @param {Object} entity - Entidade do dominio
     * @param {Object} rules - Regras de desabilitacao { action: (entity) => boolean }
     * @returns {Object} Props para TableActions
     */
    const getTableActionsPropsForEntity = (entity, rules = {}) => {
        const overrides = {};

        Object.entries(rules).forEach(([action, ruleFn]) => {
            if (typeof ruleFn === 'function') {
                overrides[action] = ruleFn(entity);
            }
        });

        return getTableActionsProps(overrides);
    };

    /**
     * Verifica se alguma acao esta disponivel.
     *
     * @returns {boolean}
     */
    const hasAnyAction = computed(() => {
        return Object.values(ActionTypes).some(action => isActionEnabled(action));
    });

    /**
     * Retorna lista de acoes habilitadas.
     *
     * @returns {string[]}
     */
    const enabledActions = computed(() => {
        return Object.values(ActionTypes).filter(action => isActionEnabled(action));
    });

    /**
     * Verifica se a acao requer confirmacao.
     *
     * @param {string} action - Tipo da acao
     * @returns {boolean}
     */
    const requiresConfirmation = (action) => {
        const config = getActionConfig(action);
        return config?.requiresConfirmation ?? false;
    };

    /**
     * Retorna a mensagem de confirmacao de uma acao.
     *
     * @param {string} action - Tipo da acao
     * @returns {string|null}
     */
    const getConfirmationMessage = (action) => {
        const config = getActionConfig(action);
        return config?.confirmationMessage ?? null;
    };

    /**
     * Retorna o tooltip de uma acao.
     *
     * @param {string} action - Tipo da acao
     * @returns {string|null}
     */
    const getTooltip = (action) => {
        const config = getActionConfig(action);
        return config?.tooltip ?? config?.label ?? null;
    };

    /**
     * Cria um handler para acao que verifica confirmacao.
     *
     * @param {string} action - Tipo da acao
     * @param {Function} handler - Funcao a executar
     * @param {Function} confirmFn - Funcao de confirmacao (opcional)
     * @returns {Function}
     */
    const createActionHandler = (action, handler, confirmFn = null) => {
        return async (...args) => {
            if (!isActionEnabled(action)) {
                return;
            }

            if (requiresConfirmation(action) && confirmFn) {
                const message = getConfirmationMessage(action);
                const confirmed = await confirmFn(message);
                if (!confirmed) {
                    return;
                }
            }

            return handler(...args);
        };
    };

    /**
     * Gera todas as configuracoes para uso em DataTable simplificado.
     */
    const getDataTableConfig = () => {
        const actions = {};

        Object.values(ActionTypes).forEach(action => {
            const config = getActionConfig(action);
            actions[action] = {
                ...config,
                enabled: isActionEnabled(action)
            };
        });

        return {
            module: moduleName,
            actions,
            props: getTableActionsProps()
        };
    };

    return {
        moduleConfig,
        userLevel,
        isSuperAdmin,
        isActionEnabled,
        getActionConfig,
        getActionMetadata,
        getModalConfig,
        getActionRoute,
        getTableActionsProps,
        getTableActionsPropsForEntity,
        hasAnyAction,
        enabledActions,
        requiresConfirmation,
        getConfirmationMessage,
        getTooltip,
        createActionHandler,
        getDataTableConfig,
        ActionTypes
    };
}

/**
 * Regras comuns para habilitar/desabilitar acoes baseadas em status.
 * Retorna TRUE = acao HABILITADA, FALSE = acao DESABILITADA.
 */
export const CommonActionRules = {
    enableEditUnlessFinalized: (entity) => {
        const status = entity?.status;
        return status !== 'finalizado' && status !== 'concluido';
    },

    enableDeleteUnlessInProgress: (entity) => {
        const status = entity?.status;
        return status !== 'em_andamento' && status !== 'em_progresso';
    },

    enableDeleteUnlessFinalized: (entity) => {
        const status = entity?.status;
        return status !== 'finalizado' && status !== 'concluido';
    },

    enableUnlessArchived: (entity) => {
        return entity?.status !== 'arquivado' && !entity?.archived;
    },

    enableEditUnlessLocked: (entity) => {
        return !entity?.locked;
    },

    enableDeleteUnlessHasChildren: (entity) => {
        return !entity?.has_children && !(entity?.children_count > 0);
    },
};

/**
 * Factory para criar regras customizadas baseadas em status.
 */
export const createStatusRule = (disabledStatuses) => {
    return (entity) => !disabledStatuses.includes(entity?.status);
};

/**
 * Combina multiplas regras (AND).
 */
export const combineRules = (...rules) => {
    return (entity) => rules.every(rule => rule(entity));
};
