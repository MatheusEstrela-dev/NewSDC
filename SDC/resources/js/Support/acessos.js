/**
 * Constantes de tela do modulo Acessos: status, campos do cadastro e rotulos
 * da auditoria. Fonte unica para filtro, badge, formulario e detalhe.
 */

// Mesma lista validada no CadastroAcessoController::index.
export const STATUS_ACESSO = [
  { value: 'pendente', label: 'Pendente', variante: 'warning' },
  { value: 'aprovado', label: 'Aprovado', variante: 'info' },
  { value: 'ativo', label: 'Ativo', variante: 'success' },
  { value: 'inativo', label: 'Inativo', variante: 'neutral' },
  { value: 'rejeitado', label: 'Rejeitado', variante: 'danger' },
];

const POR_STATUS = Object.fromEntries(STATUS_ACESSO.map((s) => [s.value, s]));

export const rotuloStatusAcesso = (status) => POR_STATUS[status]?.label ?? status ?? '—';
export const varianteStatusAcesso = (status) => POR_STATUS[status]?.variante ?? 'default';

export const TIPOS_DOCUMENTO = [
  { value: 'masp', label: 'MASP' },
  { value: 'militar', label: 'Militar' },
  { value: 'outros', label: 'Outro' },
];

// Ordem e limites do formulario. `rotuloFormulario` sobrepoe `rotulo` so no input.
export const CAMPOS_ACESSO = [
  { key: 'nome', rotulo: 'Nome', required: true },
  { key: 'tipo_documento', rotulo: 'Tipo de documento', required: true },
  { key: 'documento', rotulo: 'Documento', required: true, max: 30 },
  { key: 'cpf', rotulo: 'CPF', rotuloFormulario: 'CPF (11 dígitos)', required: true, max: 11 },
  { key: 'login_ad', rotulo: 'Login AD', max: 100 },
  { key: 'email_corporativo', rotulo: 'E-mail corporativo', type: 'email' },
  { key: 'email_pessoal', rotulo: 'E-mail pessoal', type: 'email' },
  { key: 'telefone_mesa', rotulo: 'Telefone' },
  { key: 'telefone_whatsapp', rotulo: 'WhatsApp' },
  { key: 'setor', rotulo: 'Setor', max: 150 },
  { key: 'posto', rotulo: 'Posto', max: 150 },
  { key: 'cargo', rotulo: 'Cargo', max: 150 },
  { key: 'observacoes_ti', rotulo: 'Observações de TI', textarea: true },
];

export const CHAVES_CAMPOS_ACESSO = CAMPOS_ACESSO.map((c) => c.key);

// Formulario vazio (novo cadastro) ou preenchido a partir de um cadastro.
export function dadosFormularioAcesso(cadastro = null) {
  const dados = Object.fromEntries(CHAVES_CAMPOS_ACESSO.map((key) => [key, cadastro?.[key] || '']));
  if (!cadastro) dados.tipo_documento = 'masp';
  return dados;
}

const ROTULO_ACAO = {
  criado: 'Cadastro criado',
  editado: 'Cadastro editado',
  aprovado: 'Cadastro aprovado',
  status_alterado: 'Status alterado',
};

export const rotuloAcaoAuditoria = (acao) => ROTULO_ACAO[acao] ?? acao;
