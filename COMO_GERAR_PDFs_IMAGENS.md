# 📸 Como Gerar PDFs e Imagens do Frontend

## 🎯 Opções para Converter os Mockups

### Opção 1: Markdown para PDF (Mais Rápido) ⚡

#### Usando VS Code + Extensões

1. **Instalar extensão**:
   - Nome: "Markdown PDF"
   - Publisher: yzane
   - Link: https://marketplace.visualstudio.com/items?itemName=yzane.markdown-pdf

2. **Converter**:
   - Abrir arquivo `DECRETACOES_FRONTEND_MOCKUPS.md`
   - Pressionar `Ctrl+Shift+P` (ou `Cmd+Shift+P` no Mac)
   - Digitar: "Markdown PDF: Export (pdf)"
   - Aguardar conversão

3. **Resultado**:
   - PDF gerado na mesma pasta
   - Preserva formatação ASCII art
   - Fonte monoespaçada automática

#### Usando Pandoc (Terminal)

```bash
# Instalar pandoc
sudo apt install pandoc texlive-xetex  # Linux
brew install pandoc                     # Mac

# Converter para PDF
pandoc DECRETACOES_FRONTEND_MOCKUPS.md \
  -o DECRETACOES_FRONTEND_MOCKUPS.pdf \
  --pdf-engine=xelatex \
  -V geometry:margin=2cm \
  -V monofont="JetBrains Mono"

# Converter para HTML
pandoc DECRETACOES_FRONTEND_MOCKUPS.md \
  -o DECRETACOES_FRONTEND_MOCKUPS.html \
  --standalone \
  --css=style.css
```

---

### Opção 2: Ferramentas Online (Sem Instalação) 🌐

#### 1. Markdown to PDF Online
- **Site**: https://www.markdowntopdf.com/
- **Como usar**:
  1. Copiar conteúdo do arquivo `.md`
  2. Colar no site
  3. Clicar em "Convert"
  4. Baixar PDF

#### 2. Dillinger
- **Site**: https://dillinger.io/
- **Como usar**:
  1. Abrir site
  2. Colar markdown
  3. Visualizar preview
  4. Export → PDF

#### 3. StackEdit
- **Site**: https://stackedit.io/
- **Como usar**:
  1. Criar novo documento
  2. Colar conteúdo
  3. Menu → Export to disk → PDF

---

### Opção 3: Criar Imagens Profissionais (Melhor Resultado) 🎨

#### Figma (Recomendado para Design Profissional)

**Software**: https://www.figma.com (Grátis)

**Passo a passo**:

1. **Criar conta grátis** no Figma

2. **Criar novo arquivo**:
   - File → New design file
   - Escolher template "Desktop" (1920x1080)

3. **Criar frames para cada tela**:
   ```
   Frame 1: ProcessoIndex  (1920x1080)
   Frame 2: ProcessoShow   (1920x1080)
   Frame 3: ProcessoMobile (375x667)
   ```

4. **Usar os mockups como referência**:
   - Copiar estrutura dos ASCII arts
   - Adicionar componentes visuais reais

5. **Componentes prontos no Figma**:
   - Pesquisar por "Admin Dashboard UI Kit"
   - Usar templates gratuitos como base
   - Kits recomendados:
     - "Untitled UI" (grátis)
     - "Ant Design" (grátis)
     - "Material UI" (grátis)

6. **Exportar**:
   - Selecionar frame
   - Export → PNG (para imagens)
   - Export → PDF (para documento)

#### Balsamiq (Wireframes Rápidos)

**Software**: https://balsamiq.com (Trial 30 dias)

**Vantagens**:
- Rápido para prototipar
- Estilo sketch/wireframe
- Ótimo para apresentações

**Como usar**:
1. Baixar trial gratuito
2. Arrastar componentes (cards, botões, badges)
3. Seguir estrutura dos mockups ASCII
4. Exportar como PNG

#### Excalidraw (Diagramas/Sketch Online)

**Site**: https://excalidraw.com (100% Grátis)

**Vantagens**:
- Totalmente grátis
- Estilo hand-drawn
- Colaborativo
- Sem cadastro

**Como usar**:
1. Acessar site
2. Desenhar estrutura das telas
3. Usar biblioteca de componentes
4. Exportar PNG/SVG

---

### Opção 4: Screenshots do Sistema Real (Após Implementação) 📱

#### Quando o módulo estiver funcionando:

**Ferramentas de Screenshot**:

1. **Navegador (F12 DevTools)**:
   ```
   - Abrir DevTools (F12)
   - Ctrl+Shift+P
   - "Capture full size screenshot"
   - Salva PNG automaticamente
   ```

2. **Extensões Chrome**:
   - **Nimbus Screenshot** (captura página completa)
   - **Awesome Screenshot** (com anotações)
   - **GoFullPage** (scroll completo)

3. **Software Desktop**:
   - **Flameshot** (Linux) - Grátis
   - **Lightshot** (Windows/Mac) - Grátis
   - **Snagit** (Pago) - Profissional

4. **Video para GIF**:
   - **ScreenToGif** (Windows) - Grátis
   - **Kap** (Mac) - Grátis
   - **Peek** (Linux) - Grátis

---

## 📐 Template CSS para Markdown

Crie um arquivo `mockup-style.css`:

```css
/* mockup-style.css */
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap');

body {
  font-family: 'JetBrains Mono', monospace;
  background: #0f172a;
  color: #e2e8f0;
  padding: 2rem;
  line-height: 1.6;
}

pre, code {
  font-family: 'JetBrains Mono', monospace;
  background: #1e293b;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  white-space: pre;
}

h1, h2, h3 {
  color: #60a5fa;
  border-bottom: 2px solid #334155;
  padding-bottom: 0.5rem;
}

/* Cards/Boxes */
.mockup-card {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 0.5rem;
  padding: 1.5rem;
  margin: 1rem 0;
}

/* Badges */
.badge-blue { color: #3b82f6; }
.badge-yellow { color: #eab308; }
.badge-green { color: #22c55e; }
.badge-red { color: #ef4444; }
.badge-orange { color: #f97316; }
```

Use assim:
```bash
pandoc DECRETACOES_FRONTEND_MOCKUPS.md \
  -o output.html \
  --css=mockup-style.css \
  --standalone
```

---

## 🎨 Paleta de Cores para Design Tools

### Para Figma/Sketch/Adobe XD

```
Copie e cole nos color styles:

Backgrounds:
#0f172a - slate-900 (Background principal)
#1e293b - slate-800 (Cards)
#334155 - slate-700 (Borders)

Status:
#3b82f6 - blue-500 (Registro)
#eab308 - yellow-500 (Aguardando)
#f97316 - orange-500 (Ajustes)
#22c55e - green-500 (Reconhecido)
#ef4444 - red-500 (Não Reconhecido)

Text:
#f8fafc - slate-50 (Heading)
#e2e8f0 - slate-200 (Body)
#94a3b8 - slate-400 (Muted)

Primary:
#3b82f6 - blue-500 (Primary)
#2563eb - blue-600 (Primary Dark)
```

---

## 📦 Kit de Assets

### Ícones (Grátis)

1. **Heroicons** (Recomendado - mesmo do projeto)
   - Site: https://heroicons.com
   - Formato: SVG
   - Estilo: Outline + Solid
   - Download: Grátis

2. **Lucide Icons**
   - Site: https://lucide.dev
   - Formato: SVG
   - Consistente com TailwindCSS

3. **Feather Icons**
   - Site: https://feathericons.com
   - Minimalista e clean

### Fontes

```css
/* Sistema (default do projeto) */
font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto;

/* Monospace (código) */
font-family: 'JetBrains Mono', 'Fira Code', monospace;
```

---

## 🚀 Workflow Recomendado

### Para Apresentação Executiva:

1. ✅ Abrir `DECRETACOES_FRONTEND_MOCKUPS.md`
2. ✅ Converter para PDF com Pandoc
3. ✅ Adicionar capa profissional
4. ✅ Apresentar stakeholders

### Para Desenvolvimento:

1. ✅ Usar mockups ASCII como referência
2. ✅ Implementar componentes Vue
3. ✅ Tirar screenshots reais
4. ✅ Atualizar documentação com screens

### Para Design Refinado:

1. ✅ Abrir Figma
2. ✅ Criar frames baseados nos mockups
3. ✅ Adicionar componentes reais
4. ✅ Exportar PNG de alta qualidade
5. ✅ Criar protótipo interativo

---

## 📝 Checklist de Exportação

### Antes de gerar PDF/Imagens:

- [ ] Revisar todos os textos
- [ ] Verificar ASCII art alinhamento
- [ ] Testar em fonte monoespaçada
- [ ] Validar paleta de cores
- [ ] Adicionar metadados (autor, data)
- [ ] Incluir índice/sumário
- [ ] Numerar páginas
- [ ] Adicionar rodapé com versão

### Metadados para PDF (Pandoc):

```bash
pandoc input.md -o output.pdf \
  --metadata title="Módulo Decretações - Frontend" \
  --metadata author="Equipe SDC" \
  --metadata date="2025-12-27" \
  --metadata keywords="decretações,frontend,mockups" \
  --toc \
  --number-sections
```

---

## 💡 Dicas Profissionais

### 1. Consistência Visual
- Usar mesma paleta em todo documento
- Manter espaçamentos uniformes
- Alinhar elementos grid

### 2. Acessibilidade
- Contraste mínimo 4.5:1 para texto
- Não depender só de cores
- Adicionar textos alternativos

### 3. Responsividade
- Mostrar versões mobile E desktop
- Indicar breakpoints
- Demonstrar estados (hover, active)

### 4. Documentação
- Incluir especificações técnicas
- Adicionar guia de cores
- Documentar componentes

---

## 📚 Recursos Adicionais

### Tutoriais:
- [Figma para Desenvolvedores](https://www.youtube.com/watch?v=FTFaQWZBqQ8)
- [Pandoc Markdown to PDF](https://pandoc.org/MANUAL.html)
- [Excalidraw Tutorial](https://excalidraw.com/#room)

### Templates Prontos:
- [Untitled UI (Figma)](https://www.untitledui.com/)
- [TailwindUI](https://tailwindui.com/)
- [DaisyUI](https://daisyui.com/)

---

**Boa sorte com a conversão! 🎨**

Se precisar de ajuda, consulte a documentação das ferramentas ou entre em contato.
