# Análise do Sistema de Log Legado (sdc-producao)

O sistema legado utiliza uma abordagem híbrida para coleta e visualização de informações de log e auditoria.

## 1. Monitoramento de Arquivos de Log (Log Viewer)

O sistema utiliza o pacote open-source **[opcodesio/log-viewer](https://github.com/opcodesio/log-viewer)**.

### Coleta de Informações
- **Escaneamento Automático**: O pacote é configurado em [config/log-viewer.php](file:///c:/Users/x24679188/Documents/Github/sdc-producao/config/log-viewer.php) para escanear recursivamente o diretório `storage/logs` em busca de arquivos `*.log`.
- **Suporte a Múltiplos Formatos**: Ele detecta automaticamente logs padrão do Laravel (Monolog) e logs estruturados em JSON.
- **Indexação em Cache**: Para garantir performance em arquivos grandes, o sistema cria índices em cache (Redis ou File) para permitir navegação rápida (lazy scanning).
- **Interface de Usuário**: Disponibiliza uma interface completa em `/log-viewer` com filtros por nível (Error, Info, Debug, etc.), busca textual e download de arquivos.

## 2. Auditoria de Banco de Dados (Activity Log)

Além dos logs de arquivo, o sistema possui uma camada de auditoria persistente no banco de dados.

### Coleta de Informações
- **Trait `LogsModelChanges`**: Localizada em `app/Traits/LogsModelChanges.php`, esta trait é aplicada aos Models do Eloquent.
- **Model Observers**: Ela utiliza os eventos `boot` do Eloquent (`created`, `updated`, `deleted`) para interceptar mudanças.
- **Persistência**: As mudanças são salvas na tabela `model_activity_logs` via o model `App\Models\ModelActivityLog`.
- **Conteúdo do Log**:
    - `registravel_type` / `registravel_id`: Referência polimórfica ao registro afetado.
    - `usuario_id`: O usuário que realizou a ação.
    - `evento`: O tipo da ação (`criado`, `atualizado`, `deletado`).
    - `valores_antigos` / `valores_novos`: JSON contendo o estado do model antes e depois da operação.

## Comparação com o Novo Sistema (NewSDC)

| Característica | Sistema Legado (sdc-producao) | Novo Sistema (NewSDC) |
| :--- | :--- | :--- |
| **Log Viewer** | Pacote Externo (`opcodesio/log-viewer`) | Implementação Customizada (`ActivityLogger` + Vue) |
| **Armazenamento** | Arquivos `.log` locais | Arquivos JSON + Redis (tempo real) |
| **Auditoria** | Database (`model_activity_logs`) | Integrada ao `ActivityLogger` (em arquivos/Redis) |
| **Parsing** | Automático pelo pacote | Regex Customizado e `json_decode` manual |
