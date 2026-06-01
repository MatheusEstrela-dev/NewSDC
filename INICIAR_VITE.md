# Vite local com Laravel no Docker

O ambiente de desenvolvimento atual usa:

- Laravel/FrankenPHP no Docker: `https://localhost:19444`
- Vite no host: `http://localhost:8081`
- Aplicacao PAE: `https://localhost:19444/pae`

## Subir o ambiente

Na raiz `NewSDC/`, suba o Laravel e os servicos:

```powershell
just dev-up
```

Em outro terminal, inicie o Vite no host:

```powershell
just dev-vite
```

Tambem e possivel iniciar diretamente:

```powershell
cd SDC
bun run dev
```

Para gerar os assets de producao sem derrubar o HMR:

```powershell
cd SDC
bun run build
```

Se o PHP instalado no host for anterior ao `8.3`, a build gera o Ziggy
automaticamente pelo container `newsdc_dev_app`.

No Windows, os atalhos abaixo executam o mesmo fluxo:

```powershell
cd SDC
.\iniciar-vite.ps1
```

ou:

```batch
cd SDC
iniciar-vite.bat
```

## Como a integracao funciona

O `laravel-vite-plugin` cria `SDC/public/hot` quando o Vite inicia e remove o
arquivo quando o processo encerra normalmente. O compose de desenvolvimento
monta `SDC/public` em `/app/public`, portanto o Laravel no container le o mesmo
hot-file criado pelo Bun no host.

Uma build local pode coexistir com o HMR e nao remove `public/hot`. Em ambiente
local, o Laravel preserva o hot-file enquanto o Vite estiver acessivel e remove
qualquer arquivo residual se o servidor cair. Em ambientes nao locais, o
Laravel sempre remove o hot-file residual e usa `public/build/manifest.json`.

## Verificacao rapida

Com o Vite ativo:

```powershell
Get-Content SDC\public\hot
docker exec newsdc_dev_app cat /app/public/hot
```

Os dois comandos devem mostrar:

```text
http://localhost:8081
```

Se a porta `8081` estiver ocupada, o script encerra antes de carregar uma
segunda instancia do Vite. Isso preserva o hot-file da instancia existente e
evita a troca silenciosa para outra porta.
