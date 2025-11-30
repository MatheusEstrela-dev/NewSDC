# Estrutura do Módulo de Login

## 📁 Organização de Arquivos

A página de login foi reorganizada seguindo as melhores práticas de desenvolvimento moderno, com separação clara de responsabilidades:

```
resources/
├── js/
│   ├── Pages/
│   │   └── Auth/
│   │       └── login.vue          # Componente Vue principal
│   ├── composables/
│   │   └── useLogin.js            # Lógica reutilizável do login
│   └── utils/
│       └── cpfMask.js             # Utilitários para máscara de CPF
└── css/
    └── pages/
        └── auth/
            └── login.css          # Estilos específicos do login
```

## 🎯 Componentes

### 1. `login.vue` - Componente Principal
- **Localização**: `resources/js/Pages/Auth/login.vue`
- **Responsabilidade**: Template e estrutura visual do formulário
- **Tecnologias**: Vue 3 Composition API, Inertia.js, Tailwind CSS

### 2. `useLogin.js` - Composable
- **Localização**: `resources/js/composables/useLogin.js`
- **Responsabilidade**: Lógica de negócio e gerenciamento de estado
- **Funcionalidades**:
  - Gerenciamento de estado (CPF, senha, remember, loading)
  - Validação de formulário
  - Submissão via Inertia.js
  - Toggle de visibilidade de senha

### 3. `cpfMask.js` - Utilitários
- **Localização**: `resources/js/utils/cpfMask.js`
- **Responsabilidade**: Formatação e validação de CPF
- **Funções**:
  - `applyCpfMask()` - Aplica máscara 000.000.000-00
  - `removeCpfMask()` - Remove máscara, retorna apenas números
  - `isValidCpfFormat()` - Valida formato do CPF

### 4. `login.css` - Estilos
- **Localização**: `resources/css/pages/auth/login.css`
- **Responsabilidade**: Estilos específicos da página de login
- **Características**:
  - Variáveis CSS para temas
  - Classes utilitárias com Tailwind
  - Design responsivo
  - Animações e transições

## 🔧 Configurações

### Vite Config
O arquivo `vite.config.js` foi atualizado com alias para facilitar imports:

```js
resolve: {
    alias: {
        '@': path.resolve(__dirname, 'resources/js'),
        ziggy: path.resolve(__dirname, 'vendor/tightenco/ziggy/dist/index.esm.js'),
    },
}
```

### Rotas
A rota raiz (`/`) foi configurada para redirecionar automaticamente para o login:

```php
Route::get('/', function () {
    return redirect()->route('login');
});
```

### LoginRequest
O `LoginRequest` foi atualizado para aceitar CPF ao invés de email:

```php
'cpf' => ['required', 'string', 'size:11'],
```

## 🎨 Design

### Características Visuais
- **Tema**: Dark blue gradient background
- **Card**: Glassmorphism effect com backdrop blur
- **Cores**: 
  - Primary: #f39c12 (laranja)
  - Background: Gradiente azul escuro
  - Text: Branco/cinza claro
- **Tipografia**: Inter font family
- **Responsivo**: Mobile-first approach

### Componentes Visuais
- Floating labels nos inputs
- Ícones SVG inline
- Toggle de senha com animação
- Botão com estados de loading
- Checkbox customizado
- Mensagens de erro estilizadas

## 🚀 Funcionalidades

### Implementadas
✅ Máscara automática de CPF  
✅ Validação de formulário em tempo real  
✅ Toggle de visibilidade de senha  
✅ Estado de loading no botão  
✅ Tratamento de erros  
✅ Integração com Inertia.js  
✅ Design responsivo  
✅ Acessibilidade (labels, placeholders)  

### Fluxo de Autenticação
1. Usuário preenche CPF (com máscara automática)
2. Usuário preenche senha
3. Validação client-side
4. Submissão via Inertia.js
5. Backend valida e autentica
6. Redirecionamento para dashboard

## 📝 Boas Práticas Aplicadas

### Separação de Responsabilidades
- **View**: Apenas template e apresentação
- **Logic**: Composable isolado e testável
- **Utils**: Funções puras e reutilizáveis
- **Styles**: CSS modular e organizado

### Código Limpo
- Nomes descritivos
- Funções pequenas e focadas
- Comentários quando necessário
- Estrutura consistente

### Performance
- Lazy loading de componentes
- CSS otimizado com Tailwind
- Validação client-side antes do submit
- Rate limiting no backend

## 🔄 Próximos Passos

### Melhorias Sugeridas
- [ ] Adicionar validação de dígitos verificadores do CPF
- [ ] Implementar recuperação de senha
- [ ] Adicionar testes unitários para composables
- [ ] Melhorar acessibilidade (ARIA labels)
- [ ] Adicionar animações de entrada
- [ ] Implementar modo claro/escuro

## 📚 Referências

- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Inertia.js](https://inertiajs.com/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Laravel Authentication](https://laravel.com/docs/authentication)

---

**Última atualização**: 2025-11-20

