/**
 * Tipos de acoes disponiveis no sistema.
 * Espelha o enum ActionType do backend.
 */
export const ActionTypes = Object.freeze({
    VIEW: 'view',
    PRINT: 'print',
    EDIT: 'edit',
    DELETE: 'delete',
    ATTACHMENTS: 'attachments',
    HISTORY: 'history',
    ARCHIVE: 'archive',
    UPLOAD: 'upload',
    NOTIFICATIONS: 'notifications',
    EXPORT: 'export',
    DUPLICATE: 'duplicate',
    FINALIZE: 'finalize'
});

/**
 * Configuracoes padrao para cada tipo de acao.
 * Todas as acoes sao habilitadas por padrao (enabled: true).
 */
export const ActionDefaults = Object.freeze({
    [ActionTypes.VIEW]: {
        enabled: true,
        icon: 'EyeIcon',
        variant: 'primary',
        label: 'Visualizar',
        tooltip: 'Visualizar detalhes'
    },
    [ActionTypes.PRINT]: {
        enabled: true,
        icon: 'PrinterIcon',
        variant: 'info',
        label: 'Imprimir',
        tooltip: 'Imprimir registro'
    },
    [ActionTypes.EDIT]: {
        enabled: true,
        icon: 'PencilIcon',
        variant: 'warning',
        label: 'Editar',
        tooltip: 'Editar registro'
    },
    [ActionTypes.DELETE]: {
        enabled: true,
        icon: 'TrashIcon',
        variant: 'danger',
        label: 'Excluir',
        tooltip: 'Excluir registro',
        requiresConfirmation: true,
        confirmationMessage: 'Tem certeza que deseja excluir este registro?'
    },
    [ActionTypes.ATTACHMENTS]: {
        enabled: true,
        icon: 'PaperClipIcon',
        variant: 'success',
        label: 'Anexos',
        tooltip: 'Gerenciar anexos'
    },
    [ActionTypes.HISTORY]: {
        enabled: true,
        icon: 'ClockIcon',
        variant: 'success',
        label: 'Historico',
        tooltip: 'Ver historico'
    },
    [ActionTypes.ARCHIVE]: {
        enabled: true,
        icon: 'ArchiveBoxIcon',
        variant: 'topaz',
        label: 'Arquivar',
        tooltip: 'Arquivar registro',
        requiresConfirmation: true,
        confirmationMessage: 'Tem certeza que deseja arquivar este registro?'
    },
    [ActionTypes.UPLOAD]: {
        enabled: true,
        icon: 'UploadIcon',
        variant: 'warning',
        label: 'Upload',
        tooltip: 'Fazer upload'
    },
    [ActionTypes.NOTIFICATIONS]: {
        enabled: true,
        icon: 'BellIcon',
        variant: 'secondary',
        label: 'Notificacoes',
        tooltip: 'Ver notificacoes'
    },
    [ActionTypes.EXPORT]: {
        enabled: true,
        icon: 'ArrowDownTrayIcon',
        variant: 'info',
        label: 'Exportar',
        tooltip: 'Exportar dados'
    },
    [ActionTypes.DUPLICATE]: {
        enabled: true,
        icon: 'DocumentDuplicateIcon',
        variant: 'secondary',
        label: 'Duplicar',
        tooltip: 'Duplicar registro'
    },
    [ActionTypes.FINALIZE]: {
        enabled: true,
        icon: 'CheckIcon',
        variant: 'success',
        label: 'Finalizar',
        tooltip: 'Finalizar registro',
        requiresConfirmation: true,
        confirmationMessage: 'Tem certeza que deseja finalizar este registro? Esta acao nao pode ser desfeita.'
    }
});

/**
 * Ordem padrao das acoes.
 */
export const ActionOrder = Object.freeze({
    [ActionTypes.VIEW]: 1,
    [ActionTypes.PRINT]: 2,
    [ActionTypes.EDIT]: 3,
    [ActionTypes.ATTACHMENTS]: 4,
    [ActionTypes.UPLOAD]: 5,
    [ActionTypes.HISTORY]: 6,
    [ActionTypes.NOTIFICATIONS]: 7,
    [ActionTypes.EXPORT]: 8,
    [ActionTypes.DUPLICATE]: 9,
    [ActionTypes.FINALIZE]: 10,
    [ActionTypes.ARCHIVE]: 11,
    [ActionTypes.DELETE]: 12
});

/**
 * Mapeia tipo de acao para prop do TableActions.
 */
export const ActionToPropMap = Object.freeze({
    [ActionTypes.VIEW]: 'showView',
    [ActionTypes.PRINT]: 'showPrint',
    [ActionTypes.EDIT]: 'showEdit',
    [ActionTypes.DELETE]: 'showDelete',
    [ActionTypes.ATTACHMENTS]: 'showAttachments',
    [ActionTypes.HISTORY]: 'showHistory',
    [ActionTypes.ARCHIVE]: 'showArchive',
    [ActionTypes.UPLOAD]: 'showUpload',
    [ActionTypes.NOTIFICATIONS]: 'showNotifications',
    [ActionTypes.EXPORT]: 'showExport',
    [ActionTypes.DUPLICATE]: 'showDuplicate',
    [ActionTypes.FINALIZE]: 'showFinalize'
});

/**
 * Retorna os valores do enum como array.
 */
export function getActionValues() {
    return Object.values(ActionTypes);
}

/**
 * Verifica se um valor e um tipo de acao valido.
 */
export function isValidActionType(value) {
    return getActionValues().includes(value);
}

/**
 * Retorna a configuracao padrao de uma acao.
 */
export function getActionDefault(actionType) {
    return ActionDefaults[actionType] || null;
}
