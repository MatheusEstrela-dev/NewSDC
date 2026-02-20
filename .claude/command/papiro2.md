Aqui está o "Boilerplate de Ouro" seguindo exatamente a linha de raciocínio: Request → DTO → Controller → Service → Interface → Model.

Imagine um sistema de Criação de Usuários.

1. O Contrato (Interface)
app/Interfaces/UserRepositoryInterface.php

PHP
namespace App\Interfaces;

use App\DTOs\UserDTO;

interface UserRepositoryInterface {
    public function save(UserDTO $data): bool;
}
2. O Envelope de Dados (DTO)
app/DTOs/UserDTO.php

PHP
namespace App\DTOs;

readonly class UserDTO {
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public bool $isAdmin = false
    ) {}
}
3. A Validação (Form Request)
app/Http/Requests/StoreUserRequest.php

PHP
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest {
    public function rules(): array {
        return [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8'
        ];
    }
}
4. O Orquestrador (Controller)
app/Http/Controllers/UserController.php

PHP
namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Services\UserService;
use App\DTOs\UserDTO;

class UserController extends Controller {
    public function __construct(protected UserService $service) {}

    public function store(StoreUserRequest $request) {
        // Transforma Request em DTO (Limpeza de dados)
        $dto = new UserDTO(...$request->validated());

        // Passa para o Service
        $this->service->register($dto);

        return response()->json(['message' => 'Usuário criado!'], 201);
    }
}
5. A Inteligência (Service)
app/Services/UserService.php

PHP
namespace App\Services;

use App\DTOs\UserDTO;
use App\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class UserService {
    // Injeta a Interface, não a classe concreta!
    public function __construct(protected UserRepositoryInterface $repository) {}

    public function register(UserDTO $data): bool {
        // Lógica de negócio: Hash de senha antes de salvar
        $data->password = Hash::make($data->password);

        return $this->repository->save($data);
    }
}
6. A Implementação Real (Repository/Model)
app/Repositories/EloquentUserRepository.php

PHP
namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\DTOs\UserDTO;
use App\Models\User;

class EloquentUserRepository implements UserRepositoryInterface {
    public function save(UserDTO $data): bool {
        // O Model só entra em cena no último passo
        return (bool) User::create((array) $data);
    }
}
Por que isso é "Extremamente Eficiente"?
Segurança Total: O UserDTO é readonly. Uma vez criado, ninguém altera os dados no meio do caminho.

Desacoplamento: O UserService não sabe que o banco é MySQL. Se você quiser salvar em um arquivo .txt, basta criar um FileUserRepository que implemente a mesma Interface.

Limpeza: O Controller é apenas um "passador de bastão".

Escalabilidade: Se você precisar criar um usuário via comando de terminal, você só precisa instanciar o UserService e passar um UserDTO. A lógica de hash e salvamento já está pronta e centralizada.


[Browser] ---post---> [FormRequest: validado?] 
                            |
                     [Controller: transforma em DTO]
                            |
                     [Service: aplica regra de negócio usando DTO]
                            |
                     [Interface/Model: salva no DB]
                            |
[Browser] <---200 OK--- [Response DTO/JSON]