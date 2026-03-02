/**
 * Composables Index - Re-exports de todos os composables organizados por dominio
 *
 * Estrutura:
 * - core/      : Utilitarios compartilhados (modal, tabs, table)
 * - ui/        : Componentes de interface (theme, loading, actions)
 * - mobile/    : PWA e responsividade (gestures, offline, network)
 * - auth/      : Autenticacao e permissoes
 * - location/  : Servicos de localizacao e endereco
 * - data/      : Manipulacao de dados e API
 * - ai/        : Inteligencia artificial
 * - pae/       : Plano de Acao Emergencial
 * - rat/       : Relatorio de Atendimento Tecnico
 * - dashboard/ : Painel e estatisticas
 */

// Core - Utilitarios compartilhados
export * from './core';

// UI - Interface do usuario
export * from './ui';

// Mobile - PWA e responsividade
export * from './mobile';

// Auth - Autenticacao
export * from './auth';

// Location - Localizacao
export * from './location';

// Data - Dados e API
export * from './data';

// AI - Inteligencia artificial
export * from './ai';

// PAE - Plano de Acao Emergencial
export * from './pae';

// RAT - Relatorio de Atendimento Tecnico
export * from './rat';

// Dashboard - Painel
export * from './dashboard';

// Composables gerais (root level)
export * from './useDemandas';
export * from './useDocuments';
export * from './useHierarchy';
export * from './useNavigation';
export * from './useNotifications';
export * from './usePlanCon';
export * from './useReset';
