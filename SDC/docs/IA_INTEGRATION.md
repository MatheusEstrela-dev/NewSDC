# Integração de IA no NewSDC

Este documento detalha a arquitetura e implementação da infraestrutura de IA no backend do NewSDC.

## Visão Geral

A arquitetura foi projetada para ser modular e extensível, permitindo que Agentes de IA (como n8n, OpenAI, etc.) interajam com o sistema através de "Plugins" padronizados.

A estrutura principal reside em `SDC/core/IA`.

## Estrutura de Diretórios

```
SDC/core/IA/
├── Contracts/          # Interfaces e Contratos
│   └── AIPluginInterface.php
├── Drivers/            # Drivers de IA (futuro: OpenAI, Gemini, etc.)
├── Http/               # Controllers e Requests
│   └── Controllers/
│       └── AIPluginController.php
├── Plugins/            # Implementações de funcionalidades
│   ├── Analysis/
│   ├── Automations/
│   └── CivilDefense/   # Plugins específicos de Defesa Civil
│       └── VistoriaPlugin.php
└── IAServices.php      # Serviço gerenciador de plugins
```

## Como Funciona

1.  **Plugins**: Cada funcionalidade exposta para a IA é encapsulada em uma classe que implementa `App\Core\IA\Contracts\AIPluginInterface`.
2.  **Registro**: Os plugins são registrados no `App\Core\IA\IAServices`.
3.  **Execução**: A API expõe um endpoint único (`/api/ia/execute-plugin`) que recebe o nome do plugin e um payload, e executa a lógica correspondente.

## Adicionando um Novo Plugin

Para criar uma nova funcionalidade acessível via IA:

1.  Crie uma classe em `SDC/core/IA/Plugins/{Categoria}/NomeDoPlugin.php`.
2.  Implemente a interface `AIPluginInterface`.
3.  Defina o nome único (`getName`) e a descrição (`getDescription`).
4.  Implemente a lógica em `execute($params)`.
5.  Registre o plugin no construtor de `SDC/core/IA/IAServices.php`.

### Exemplo de Plugin

```php
namespace App\Core\IA\Plugins\Exemplo;

use App\Core\IA\Contracts\AIPluginInterface;

class MeuPlugin implements AIPluginInterface
{
    public function getName(): string { return "meu_plugin"; }
    public function getDescription(): string { return "Faz algo incrivel."; }
    public function execute(array $params) {
        return ["status" => "sucesso", "dados" => $params];
    }
}
```

## API Endpoints

### Executar Plugin
**POST** `/api/ia/execute-plugin`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Body:**
```json
{
    

O Controller `AIPluginController` já possui anotações Swagger. Para atualizar a documentação gerada:
`php artisan l5-swagger:generate`
