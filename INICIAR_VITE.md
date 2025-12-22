# 🚀 Como Iniciar o Vite do NewSDC

## 📋 Comando para Iniciar

### Opção 1: Usando o script PowerShell
```powershell
cd "c:\Users\x24679188\Documents\GitHub\NewSDC\SDC"
.\iniciar-vite.ps1
```

### Opção 2: Usando o script Batch
```batch
cd "c:\Users\x24679188\Documents\GitHub\NewSDC\SDC"
iniciar-vite.bat
```

### Opção 3: Comando direto
```bash
cd "c:\Users\x24679188\Documents\GitHub\NewSDC\SDC"
npm run dev
```

## ⚙️ Configuração do Vite

O Vite está configurado em `SDC/vite.config.js`:

- **Host:** `0.0.0.0` (acessível de qualquer interface de rede)
- **Porta:** `5175` (porta diferente para evitar conflitos com outros projetos)
- **HMR (Hot Module Replacement):** Habilitado
- **Watch:** Polling habilitado (útil para Docker/WSL)

## 🌐 Acesso

Após iniciar, o Vite estará disponível em:

- **Local:** http://localhost:5175
- **Rede:** http://[seu-ip]:5175

## 📝 Scripts Disponíveis

No `package.json`:

- `npm run dev` - Inicia o servidor de desenvolvimento Vite
- `npm run build` - Compila os assets para produção

## ✅ Verificar se Está Rodando

### Windows:
```powershell
netstat -ano | findstr :5175
```

### Verificar no Navegador:
Acesse: http://localhost:5175

## 🔧 Troubleshooting

### Porta já em uso:
```bash
# Verificar qual processo está usando a porta
netstat -ano | findstr :5175

# Matar o processo (substitua PID pelo número do processo)
taskkill /PID <PID> /F
```

### Dependências não instaladas:
```bash
cd "c:\Users\x24679188\Documents\GitHub\NewSDC\SDC"
npm install
```

### Erro de permissão:
Execute o terminal como Administrador

## 📊 Status Esperado

Quando o Vite iniciar corretamente, você verá:

```
  VITE v5.4.21  ready in XXX ms

  ➜  Local:   http://localhost:5175/
  ➜  Network: http://[seu-ip]:5175/
  ➜  press h + enter to show help
```

## 🔗 Integração com Laravel

O Vite está configurado para trabalhar com Laravel através do plugin `laravel-vite-plugin`. 

No Laravel, use:
```blade
@vite(['resources/js/app.js'])
```

## ⚠️ Importante

- O Vite precisa estar rodando durante o desenvolvimento
- Para produção, use `npm run build` (os assets são compilados)
- O HMR (Hot Module Replacement) permite ver mudanças sem recarregar a página

---

**Data:** {{ date('d/m/Y H:i:s') }}









