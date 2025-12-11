Laravel Sanctum e Bearer Token
Para implementar essa arquitetura, você usará o Laravel Sanctum para gerar e gerenciar os Personal Access Tokens.

Instalação e Configuração:

Instale o Sanctum: composer require laravel/sanctum

Publique as configurações e migrações: php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

Execute as migrações: php artisan migrate (Isso cria a tabela personal_access_tokens no seu MySQL).

Modelo de Usuário:

Você deve usar o trait HasApiTokens no seu modelo App\Models\User (ou no modelo que representa seu usuário):

PHP

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
use HasApiTokens;
// ...
}
Geração do Token:

Após o login bem-sucedido de um usuário, você gera o token:

PHP

$token = $user->createToken('authToken')->plainTextToken;
// Retorna o token ao cliente (frontend, mobile, etc.)
return response()->json(['token' => $token]);
O token gerado é armazenado na tabela MySQL personal_access_tokens, juntamente com as abilities (escopos/props) que podem ser definidas.

Autenticação (Bearer Token):

O cliente deve incluir este token em todas as requisições protegidas, no cabeçalho Authorization, no formato Bearer Token:

Authorization: Bearer SEU_TOKEN_GERADO_AQUI

Proteção de Rotas:

No seu arquivo routes/api.php, você protege as rotas usando o middleware auth:sanctum:

PHP

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
Route::get('/user', function (Request $request) {
return $request->user();
});
// Suas rotas protegidas
});
Banco de Dados MySQL:

O MySQL é usado para persistir os dados do usuário (tabela users) e os tokens de acesso gerados (tabela personal_access_tokens). Quando uma requisição chega com um Bearer Token, o Laravel Sanctum consulta a tabela personal_access_tokens no MySQL para verificar a validade e associar o token ao usuário.

🔄 Inversão de Controle (IoC) e Props
A arquitetura do Laravel, incluindo o uso de Service Containers para Inversão de Controle (IoC) e Service Providers para bootstrapping, é intrinsecamente adaptável a qualquer "nova arquitetura" que você esteja planejando, especialmente para APIs RESTful.

IoC: Use a injeção de dependência no construtor de seus Controllers e Services para gerenciar as dependências (como repositórios ou services de terceiros), tornando o código modular e testável.

Exemplo: injetar uma classe UserService em vez de acessar o User Model diretamente no Controller.

Props (Escopos/Habilidades): No contexto do Sanctum, os "props" (propriedades) que um token possui são chamados de abilities (habilidades) ou scopes. Você pode definir o que cada token pode fazer ao criá-lo:

PHP

// Criando um token com a habilidade 'read' e 'create-post'
$token = $user->createToken('admin-token', ['read', 'create-post'])->plainTextToken;
Você pode então verificar essas habilidades nas rotas ou nos middlewares:

PHP

// No routes/api.php ou em um middleware
Route::middleware(['auth:sanctum', 'ability:create-post'])->post('/posts', ...);
Dessa forma, o Laravel Sanctum e o MySQL fornecem uma base sólida e simples para uma API moderna com autenticação baseada em Bearer Token. Laravel Sanctum: Secure Your API with Token-Based Authentication Este vídeo demonstra a configuração e uso do Laravel Sanctum para autenticação de API com Bearer Token.
