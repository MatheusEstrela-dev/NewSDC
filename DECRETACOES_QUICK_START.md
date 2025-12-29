# 🚀 Quick Start: Módulo Decretações

## ⚡ Ativação Rápida em 5 Passos

### 1. Registrar o Service Provider

Editar: `SDC/config/app.php`

```php
'providers' => ServiceProvider::defaultProviders()->merge([
    // ... outros providers
    App\Modules\Decretacoes\DecretacoesServiceProvider::class,
])->toArray(),
```

### 2. Criar Migration Básica (temporária)

Criar: `SDC/database/migrations/2024_12_27_000001_create_processos_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processos', function (Blueprint $table) {
            $table->id();
            $table->date('data_entrada')->nullable();
            $table->date('data_ocorrencia_desastre')->nullable();
            $table->string('processo'); // MUNICIPAL ou ESTADUAL
            $table->string('tipo_decreto')->nullable(); // SE ou ECP
            $table->string('analista')->nullable();
            $table->string('n_protocolo_fide')->nullable();
            $table->string('decreto_municipal')->nullable();
            $table->unsignedBigInteger('tipo_desastre_id')->nullable();
            $table->string('tipo_desastre_nome')->nullable();
            $table->date('data_decreto_municipal')->nullable();
            $table->date('data_publicacao_mg')->nullable();
            $table->integer('prazo_vigencia')->nullable();
            $table->string('status'); // StatusProcesso enum
            $table->text('observacoes')->nullable();
            $table->string('processo_inserido_sei')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('processo_municipios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->onDelete('cascade');
            $table->unsignedBigInteger('municipio_id'); // FK para tabela de municípios
            $table->string('n_protocolo_fide')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processo_municipios');
        Schema::dropIfExists('processos');
    }
};
```

### 3. Rodar Migrations

```bash
cd SDC
php artisan migrate
```

### 4. Adicionar ao Menu

Editar: `SDC/resources/js/Components/Sidebar.vue`

Adicionar no array de menu items:

```vue
{
  href: '/decretacoes',
  icon: 'document-text',
  label: 'Decretações',
  active: route().current('decretacoes.*')
}
```

### 5. Compilar Assets

```bash
npm run dev
```

## ✅ Testar

Acesse: `http://seu-dominio/decretacoes`

Você deve ver:
- ✅ Página de listagem
- ✅ Cards de estatísticas
- ✅ Filtros de pesquisa
- ✅ Empty state (sem dados ainda)

---

## 🌱 Criar Dados de Teste (Opcional)

Criar seeder: `SDC/database/seeders/ProcessosSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Modules\Decretacoes\Domain\Entities\Processo;
use Illuminate\Database\Seeder;

class ProcessosSeeder extends Seeder
{
    public function run(): void
    {
        Processo::create([
            'data_entrada' => now(),
            'data_ocorrencia_desastre' => now()->subDays(1),
            'processo' => 'MUNICIPAL',
            'tipo_decreto' => 'SE',
            'analista' => 'João Silva',
            'n_protocolo_fide' => '12345-2024',
            'decreto_municipal' => '001/2024',
            'tipo_desastre_nome' => 'Enchente',
            'data_decreto_municipal' => now()->subDays(1),
            'data_publicacao_mg' => now(),
            'prazo_vigencia' => 90,
            'status' => 'Registro',
            'observacoes' => 'Processo de teste',
            'created_by' => 'system',
        ]);
    }
}
```

Rodar:

```bash
php artisan db:seed --class=ProcessosSeeder
```

---

## 🐛 Troubleshooting

### Erro: "Class not found"
```bash
composer dump-autoload
```

### Erro: "Route not found"
Verificar se as rotas estão carregadas em `routes/modules/decretacoes.php`

### Erro: "Component not found"
```bash
npm run dev
# Verificar console do navegador
```

### Página em branco
Abrir DevTools (F12) e verificar erros no Console

---

## 📚 Próximos Passos

Após ativação:

1. ✅ Testar listagem vazia
2. ✅ Criar dados de teste
3. ✅ Testar filtros
4. ✅ Testar visualização
5. ⏭️ Implementar formulário de criação
6. ⏭️ Implementar edição
7. ⏭️ Adicionar permissões

---

**Boa sorte! 🎉**

Se tiver dúvidas, consulte:
- `DECRETACOES_MAPEAMENTO_COMPLETO.md`
- `DECRETACOES_FRONTEND_DESIGN.md`
- `DECRETACOES_IMPLEMENTACAO_RESUMO.md`
