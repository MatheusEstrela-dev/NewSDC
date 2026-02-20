<?php

declare(strict_types=1);

namespace App\Core\Actions\Enums;

/**
 * Enum que define os tipos de acoes disponiveis no sistema.
 * Segue o principio OCP (Open/Closed) - aberto para extensao, fechado para modificacao.
 */
enum ActionType: string
{
    case VIEW = 'view';
    case PRINT = 'print';
    case EDIT = 'edit';
    case DELETE = 'delete';
    case ATTACHMENTS = 'attachments';
    case HISTORY = 'history';
    case ARCHIVE = 'archive';
    case UPLOAD = 'upload';
    case EXPORT = 'export';
    case DUPLICATE = 'duplicate';
    case FINALIZE = 'finalize';

    /**
     * Retorna o label padrao para o tipo de acao.
     */
    public function label(): string
    {
        return match ($this) {
            self::VIEW => 'Visualizar',
            self::PRINT => 'Imprimir',
            self::EDIT => 'Editar',
            self::DELETE => 'Excluir',
            self::ATTACHMENTS => 'Anexos',
            self::HISTORY => 'Historico',
            self::ARCHIVE => 'Arquivar',
            self::UPLOAD => 'Upload',
            self::EXPORT => 'Exportar',
            self::DUPLICATE => 'Duplicar',
            self::FINALIZE => 'Finalizar',
        };
    }

    /**
     * Retorna a variante de cor padrao para o tipo de acao.
     */
    public function variant(): string
    {
        return match ($this) {
            self::VIEW => 'primary',
            self::PRINT => 'info',
            self::EDIT => 'warning',
            self::DELETE => 'danger',
            self::ATTACHMENTS => 'success',
            self::HISTORY => 'success',
            self::ARCHIVE => 'topaz',
            self::UPLOAD => 'warning',
            self::EXPORT => 'info',
            self::DUPLICATE => 'secondary',
            self::FINALIZE => 'success',
        };
    }

    /**
     * Retorna o nome do icone padrao para o tipo de acao.
     */
    public function icon(): string
    {
        return match ($this) {
            self::VIEW => 'EyeIcon',
            self::PRINT => 'PrinterIcon',
            self::EDIT => 'PencilIcon',
            self::DELETE => 'TrashIcon',
            self::ATTACHMENTS => 'PaperClipIcon',
            self::HISTORY => 'ClockIcon',
            self::ARCHIVE => 'ArchiveBoxIcon',
            self::UPLOAD => 'UploadIcon',
            self::EXPORT => 'ArrowDownTrayIcon',
            self::DUPLICATE => 'DocumentDuplicateIcon',
            self::FINALIZE => 'CheckIcon',
        };
    }

    /**
     * Retorna todos os valores do enum como array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Retorna as acoes padrao para CRUD basico.
     */
    public static function defaultCrudActions(): array
    {
        return [
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ];
    }

    /**
     * Retorna todas as acoes disponiveis com suas configuracoes padrao.
     */
    public static function allWithDefaults(): array
    {
        $result = [];
        foreach (self::cases() as $action) {
            $result[$action->value] = [
                'value' => $action->value,
                'label' => $action->label(),
                'variant' => $action->variant(),
                'icon' => $action->icon(),
            ];
        }
        return $result;
    }
}
