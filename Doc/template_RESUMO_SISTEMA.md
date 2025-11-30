# Resumo do Sistema SDC

## 📋 Visão Geral

**SDC - Sistema de Defesa Civil** é uma aplicação web desenvolvida em Laravel para gerenciar e coordenar atividades relacionadas à Defesa Civil em nível estadual e municipal.

### Informações do Sistema

- **Nome**: SDC - Sistema de Defesa Civil
- **Versão Atual**: 2.1.1.0 (17/08/2023)
- **Framework**: Laravel 9.52.16
- **PHP**: 8.1.33
- **Composer**: 2.8.12
- **Licença**: MIT

---

## 🎯 Objetivo do Sistema

O SDC é uma plataforma integrada para gestão de:
- **Ajuda Humanitária**: Pedidos, estoque, distribuição e prestação de contas
- **RAT (Registro de Ações Táticas)**: Registro e acompanhamento de ocorrências
- **Decretos e Desastres**: Gestão de decretos de situação de emergência e desastres
- **PMDA (Plano Municipal de Defesa Civil)**: Gestão de planos municipais
- **CEDEC (Centro de Defesa Civil)**: Coordenação de centros regionais
- **COMPDEC (Coordenadoria Municipal)**: Gestão municipal de defesa civil
- **DRD/DRRD (Defesa Civil Regional)**: Coordenação regional
- **Estoque**: Controle de materiais e recursos
- **TDAP (Transporte e Distribuição)**: Gestão de transporte de ajuda
- **Equipes e Voluntários**: Cadastro e gestão de equipes

---

## 🏗️ Arquitetura

### Padrão de Arquitetura

- **MVC (Model-View-Controller)**: Padrão principal
- **Repository Pattern**: Utilizado em alguns módulos
- **Service Layer**: Serviços de negócio em `app/Services/`
- **Modular**: Utiliza `nwidart/laravel-modules` para organização modular
- **API RESTful**: Endpoints documentados com Scramble

### Estrutura de Camadas

```
┌─────────────────────────────────────┐
│         Presentation Layer           │
│  (Views, Controllers, Middleware)   │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Business Layer              │
│  (Services, Repositories, Policies) │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Data Layer                  │
│  (Models, Migrations, Seeders)      │
└─────────────────────────────────────┘
```

---

## 🛠️ Stack Tecnológico

### Backend

- **Framework**: Laravel 9.52.16
- **PHP**: 8.1.33
- **Banco de Dados**: MySQL/MariaDB (via Doctrine DBAL)
- **Autenticação**: Laravel Sanctum + Google 2FA
- **Autorização**: Spatie Laravel Permission

### Frontend

- **CSS Framework**: Bootstrap 5.3.2
- **JavaScript**: jQuery 3.7.1, Alpine.js 3.13.3
- **Icons**: Font Awesome 6.5.1, Bootstrap Icons 1.11.3
- **Editores**: CKEditor, Summernote
- **Gráficos**: Chart.js 4.4.1
- **Build Tool**: Laravel Mix 6.0.6

### Bibliotecas Principais

#### Produção
- `maatwebsite/excel` - Exportação/Importação Excel
- `barryvdh/laravel-dompdf` - Geração de PDFs
- `yajra/laravel-datatables-oracle` - DataTables
- `intervention/image` - Manipulação de imagens
- `irazasyed/telegram-bot-sdk` - Integração Telegram
- `guzzlehttp/guzzle` - Cliente HTTP
- `spatie/laravel-permission` - Sistema de permissões
- `nwidart/laravel-modules` - Módulos Laravel

#### Desenvolvimento
- `barryvdh/laravel-debugbar` - Debug bar
- `phpunit/phpunit` - Testes unitários
- `fakerphp/faker` - Dados fake para testes
- `opcodesio/log-viewer` - Visualizador de logs

---

## 📦 Módulos Principais

### 1. **Ajuda Humanitária**
- Gestão de pedidos de ajuda
- Controle de estoque
- Análise técnica de pedidos
- Prestação de contas
- Gestão de cisternas
- Beneficiários e itens

### 2. **RAT (Registro de Ações Táticas)**
- Registro de ocorrências
- Relatos detalhados
- Envolvidos e recursos
- Boletins de ocorrência
- Vistorias técnicas

### 3. **Decretos e Desastres**
- Gestão de decretos de situação de emergência
- Classificação de desastres (COBRADE)
- Processos de entrada
- Categorias e grupos de desastres
- Logs de alterações

### 4. **PMDA (Plano Municipal de Defesa Civil)**
- Gestão de planos municipais
- Comunidades e pontos
- Representações
- Comentários e anexos
- Alterações históricas

### 5. **CEDEC (Centro de Defesa Civil)**
- Gestão de centros regionais
- Demandas e inventários
- Bot Telegram
- Viaturas e funcionários
- Mensagens e notificações

### 6. **COMPDEC (Coordenadoria Municipal)**
- Gestão municipal
- Equipes e preparação
- Interdições
- Vistorias
- Upload de planos

### 7. **DRD/DRRD (Defesa Civil Regional)**
- Coordenação regional
- Boletins e diários
- Plantões
- PAE (Plano de Ação de Emergência)
- Formulários e protocolos

### 8. **Estoque**
- Controle de materiais
- Movimentações
- Fornecedores
- Depósitos
- Relatórios

### 9. **TDAP (Transporte e Distribuição)**
- Cronogramas de transporte
- Lotes e viagens
- Prestadores de serviço
- Caminhões
- Vistorias de transporte

### 10. **Gestão de Usuários e Permissões**
- Usuários e perfis
- Roles e permissões
- Autenticação com 2FA
- Logs de acesso
- Configurações do sistema

---

## 📊 Estatísticas do Sistema

### Código
- **Controllers**: ~150 arquivos
- **Models**: 142 arquivos
- **Views**: 353 arquivos Blade
- **Migrations**: 65 arquivos
- **Routes**: 5 arquivos (web, api, auth, channels, console)
- **Middleware**: 11 arquivos
- **Helpers**: 6 arquivos

### Dependências
- **Pacotes PHP (produção)**: 20
- **Pacotes PHP (desenvolvimento)**: 9
- **Pacotes PHP (transitivas)**: ~150+
- **Pacotes NPM (produção)**: 9
- **Pacotes NPM (desenvolvimento)**: 12

---

## 🔐 Segurança

### Autenticação
- Laravel Sanctum para autenticação de API
- Google 2FA (Two-Factor Authentication)
- Autenticação de sessão tradicional

### Autorização
- Spatie Laravel Permission
- Policies para controle de acesso
- Middleware de verificação de usuário

### Proteções
- CSRF Token
- Criptografia de cookies
- Validação de hosts e proxies
- Sanitização de inputs

---

## 📡 Integrações

### APIs Externas
- **Telegram Bot API**: Notificações e comunicação
- **CEP**: Busca de endereços (via Guzzle)
- **Scramble**: Documentação automática de API

### Exportações
- **Excel**: Exportação de dados (Maatwebsite Excel)
- **PDF**: Geração de relatórios (DOMPDF)
- **LaTeX**: Compilação de documentos

### Uploads
- **FilePond**: Upload de arquivos
- **CompressorJS**: Compressão de imagens

---

## 🗄️ Banco de Dados

### Estrutura
- **65 Migrations**: Schema completo do banco
- **Seeders**: Dados iniciais (usuários, categorias, viaturas)
- **Factories**: Geração de dados de teste

### Principais Entidades
- Usuários e Permissões
- Ajuda Humanitária (pedidos, estoque, cisternas)
- RAT (ocorrências, relatos, recursos)
- Decretos e Desastres
- PMDA (planos, comunidades, pontos)
- CEDEC/COMPDEC (centros, coordenadorias)
- Estoque e TDAP

---

## 🧪 Testes

### Estrutura de Testes
- **Feature Tests**: Testes de funcionalidades
  - Autenticação
  - Verificação de email
  - Reset de senha
  - Registro de usuários
- **Unit Tests**: Testes unitários
- **PHPUnit 9.6.13**: Framework de testes

---

## 📝 Documentação

### Documentos Existentes
- `DEPENDENCIAS.md` - Lista completa de dependências
- `ESTRUTURA_PASTAS.md` - Estrutura de diretórios
- `docs/` - Documentação técnica específica:
  - Análise técnica de controllers
  - Estrutura do banco de dados RAT
  - Implementações de busca CEP
  - Rastreabilidade de API REST
  - Relatórios de endpoints
  - Diagramas de banco de dados

---

## 🚀 Estado Atual

### Versão em Produção
- **Versão**: 2.1.1.0
- **Data**: 17/08/2023
- **Ambiente**: PHP 8.1.33, Laravel 9.52.16

### Funcionalidades Implementadas
✅ Sistema completo de ajuda humanitária  
✅ Gestão de RAT (Registro de Ações Táticas)  
✅ Sistema de decretos e desastres  
✅ PMDA (Plano Municipal)  
✅ CEDEC e COMPDEC  
✅ Controle de estoque  
✅ TDAP (Transporte)  
✅ Sistema de permissões robusto  
✅ Integração com Telegram  
✅ Exportação Excel/PDF  
✅ API REST documentada  

---

## 🔮 Próximos Passos (Roadmap)

### Migração e Modernização

#### 1. **Atualização de Stack**
- [ ] Migração para **Laravel 12**
- [ ] Atualização para **PHP 8.3**
- [ ] Dockerização com containers separados
- [ ] Configuração de ambiente com MCP (Model Context Protocol)

#### 2. **Integração Contínua**
- [ ] Implementação de **JenkinsFile** para CI/CD
- [ ] Pipeline de testes automatizados
- [ ] Deploy automatizado
- [ ] Sistema de backup integrado

#### 3. **Modernização Frontend**
- [ ] Integração com **Livewire** (Laravel Livewire)
- [ ] Implementação de **Inertia.js**
- [ ] Migração para **Vue.js 3**
- [ ] Adoção de **Tailwind CSS**

#### 4. **Melhorias Técnicas**
- [ ] Refatoração para arquitetura mais moderna
- [ ] Melhoria de performance
- [ ] Otimização de queries
- [ ] Implementação de cache estratégico
- [ ] Melhoria de testes (cobertura)

---

## 📈 Métricas e Monitoramento

### Logs
- Sistema de logging customizado
- Logs de acesso de usuários
- Logs de ações do sistema
- Visualizador de logs integrado

### Debug
- Laravel Debugbar em desenvolvimento
- Tratamento de exceções customizado
- Páginas de erro personalizadas

---

## 🌐 Internacionalização

### Idiomas Suportados
- **Português do Brasil (pt-BR)**: Principal
- **Inglês (en)**: Suporte básico
- **AdminLTE**: Múltiplos idiomas (vendor)

### Localização
- Formatação de datas e números
- Mensagens de validação
- Interface do usuário

---

## 👥 Equipe e Contribuição

### Desenvolvimento
- Framework: Laravel Community
- Módulos: Comunidade open-source
- Licença: MIT

### Manutenção
- Sistema em produção
- Atualizações regulares de segurança
- Melhorias contínuas

---

## 📞 Suporte e Recursos

### Documentação
- Documentação técnica em `docs/`
- Documentação de dependências
- Documentação de estrutura

### Ferramentas de Desenvolvimento
- Laravel Tinker (REPL)
- Artisan CLI
- Debugbar
- Log Viewer

---

## 🔄 Versionamento

### Controle de Versão
- Git para versionamento
- Estrutura modular para releases
- Tags de versão

### Changelog
- Versão atual: 2.1.1.0 (17/08/2023)
- Histórico de mudanças (quando disponível)

---

## 📌 Conclusão

O **SDC - Sistema de Defesa Civil** é uma plataforma robusta e completa para gestão de atividades de defesa civil, com:

- ✅ **10 módulos principais** funcionais
- ✅ **Arquitetura escalável** e modular
- ✅ **Sistema de permissões** robusto
- ✅ **Integrações** com serviços externos
- ✅ **Documentação** técnica completa
- ✅ **Plano de modernização** definido

O sistema está pronto para evoluir para tecnologias mais modernas (Laravel 12, PHP 8.3, Livewire, Inertia.js, Vue.js, Tailwind CSS) conforme o roadmap estabelecido.

---

**Última atualização**: 2025-11-20  
**Versão do documento**: 1.0

