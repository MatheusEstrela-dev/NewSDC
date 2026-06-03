# Credenciais locais

Segredos de integracao (Azure, Postgres, Redis, SMTP, Gemini, Notion) ficam **fora do git** em:

```
.claude/.credentials.local.json
```

O arquivo e ignorado pelo `.gitignore`. O template publico fica em `.claude/.credentials.example.json`.

## Setup

1. Copiar o template:
   ```powershell
   Copy-Item .claude/.credentials.example.json .claude/.credentials.local.json
   ```
2. Preencher os valores reais. **Nunca** commitar.

## Leitura via Python

```python
import sys
sys.path.insert(0, ".claude")
from credentials import load_credentials

todas = load_credentials()
redis = load_credentials("redis_prod")
```

CLI:

```powershell
python .claude/credentials.py --list
python .claude/credentials.py --section redis_prod
```

## Checklist de rotacao

O arquivo antigo (`.claude/docs/CREDENTIALS.JSON`) **nao foi commitado** (`git log --all -- .claude/docs/CREDENTIALS.JSON` vazio), mas ficou no working tree em texto plano por tempo indeterminado. Decidir junto ao time se rotaciona os seguintes segredos:

- [ ] **Azure Service Principal** `sp-newsdc-claude` (`client_id` + `client_secret`) - Portal Azure -> Microsoft Entra ID -> App registrations
- [ ] **Postgres prod** `newsdc.postgres.database.azure.com` - usuario `newsdc`
- [ ] **Azure Storage Account** `newsdc` - chave de acesso
- [ ] **Azure Redis Cache** `newsdc-redis.redis.cache.windows.net`
- [ ] **SMTP Prodemge** usuario `defesa_civil_sdc`
- [ ] **Gemini API key** (Google AI Studio)
- [ ] **Notion integration token** `ntn_*`

Sugestao: pelo menos rotacionar o Azure SP client_secret e o Storage key (sao os de maior blast radius). Postgres e Redis estao atras de VNet/firewall, prioridade media.

## Nao colocar em outros lugares

- `SDC/.env` ja tem variaveis Laravel separadas; **nao copiar** os segredos do `.credentials.local.json` para la a menos que o Laravel realmente precise.
- Scripts ad-hoc que precisem desses segredos devem importar via `.claude/credentials.py`, nao redeclarar.
- `settings.local.json` do Claude e para permissoes da CLI, nao para segredos de servico.
