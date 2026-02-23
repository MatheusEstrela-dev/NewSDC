1. preciso tranpostar o backend de Decretacoes para nova arquitetura seguindo o padrao a seguir 

2. Mantenha o DRY e Clean Code, sem arquivos desnecessarios

Estrutura de Pastas
Plaintext
app/
├── Http/
│   ├── Controllers/
│   │   └── UsuarioController.php     <-- [ORQUESTRAÇÃO]
│   └── Requests/
│       └── UsuarioStoreRequest.php   <-- [VALIDAÇÃO]
├── DTOs/
│   ├── UsuarioInputDTO.php           <-- [DADOS DE ENTRADA]
│   └── UsuarioOutputDTO.php          <-- [DADOS DE SAÍDA]
├── Services/
│   └── UsuarioService.php            <-- [LÓGICA E CONVERSÃO]
└── Models/
    └── User.php                      <-- [MODEL / ENTIDADE]
🛠️ Implementação Camada por Camada
1. Request [VALIDAÇÃO]
O Laravel usa o FormRequest para garantir que os dados brutos do formulário sejam válidos antes de chegar no Controller.

PHP
// app/Http/Requests/UsuarioStoreRequest.php
public function rules(): array {
    return [
        'nome'  => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'senha' => 'required|min:8',
    ];
}
2. DTOs [Entrada e Saída]
Objetos simples apenas para transporte de dados.

PHP
// app/DTOs/UsuarioInputDTO.php (Entrada)
public readonly string $nome;
public readonly string $email;
public readonly string $senha;

// app/DTOs/UsuarioOutputDTO.php (Saída)
public readonly int $id;
public readonly string $email;
public readonly string $mensagem; // Ex: "Usuário criado com sucesso"
3. Controller [ORQUESTRAÇÃO]
Ele recebe o Request validado, converte em DTO e chama o serviço.

PHP
// app/Http/Controllers/UsuarioController.php
public function store(UsuarioStoreRequest $request, UsuarioService $service) {
    // 1. Transforma Request em DTO de Entrada
    $inputDTO = new UsuarioInputDTO($request->validated());

    // 2. Orquestra a chamada para o Service
    $outputDTO = $service->cadastrar($inputDTO);

    // 3. Retorna o DTO de Saída como JSON
    return response()->json($outputDTO, 201);
}
4. Service [LÓGICA E MAPPING]
Aqui a Model é manipulada e os DTOs são convertidos.

PHP
// app/Services/UsuarioService.php
public function cadastrar(UsuarioInputDTO $data): UsuarioOutputDTO {
    // A. Lógica de Negócio (ex: Hash de senha)
    $senhaHash = bcrypt($data->senha);

    // B. Conversão DTO -> Model (Entidade)
    $usuario = User::create([
        'name'     => $data->nome,
        'email'    => $data->email,
        'password' => $senhaHash,
    ]);

    // C. Conversão Model -> DTO de Saída
    return new UsuarioOutputDTO(
        id: $usuario->id,
        email: $usuario->email,
        mensagem: "Conta criada em: " . $usuario->created_at->format('d/m/Y')
    );
}
Por que usar esse caminho no Laravel?
Controller Magro: Seu controller tem apenas 3 ou 4 linhas. Ele não sabe o que é um bcrypt ou como o banco funciona.

Service Testável: Você consegue testar a lógica de cadastro sem precisar simular uma requisição HTTP completa.

Segurança de Tipos: Usando DTOs com readonly (PHP 8.2+), você garante que os dados não sejam alterados no meio do caminho.