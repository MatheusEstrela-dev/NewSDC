<?php

declare(strict_types=1);

namespace App\Support\Routing;

use App\Models\User;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Spatie\Permission\Middleware\PermissionMiddleware;

/**
 * Para onde o Voltar e a trilha levam sem sair do modulo.
 *
 * O modulo e o prefixo do nome da rota (treinamentos.aluno.index ->
 * treinamentos). Sobe nivel a nivel pelo nome procurando a pagina de cada
 * nivel e so aceita a que o usuario pode abrir: o professor sem acesso a
 * listagem geral nao pode ser mandado para um 403. Sem ancestral acessivel a
 * pagina e a raiz do modulo, e so dali o Voltar vai para o Inicio.
 *
 * A trilha montada no front pelo nome do COMPONENTE nao acha essas rotas
 * (Treinamento/Aluno/MeusCursos -> treinamento.index nao existe; a rota e
 * treinamentos.index), deixava o modulo sem link e o Voltar caia no dashboard.
 */
final class NavegacaoDoModulo
{
    /** Nomes que a pagina de um nivel pode ter, alem do proprio nivel. */
    private const PAGINAS_DO_NIVEL = ['index', 'dashboard'];

    public function __construct(private readonly Router $router) {}

    /**
     * @return array{voltar: ?array{rota: string, params: array<string, string>, url: string}, raiz: ?array{rota: string, params: array<string, string>, url: string}}
     */
    public function para(Route $atual, User $usuario): array
    {
        $ancestrais = $this->ancestraisAcessiveis($atual, $usuario);

        return [
            'voltar' => $ancestrais[0] ?? null,
            'raiz' => $ancestrais === [] ? null : $ancestrais[array_key_last($ancestrais)],
        ];
    }

    /** Do mais proximo ao mais alto. */
    private function ancestraisAcessiveis(Route $atual, User $usuario): array
    {
        $nome = $atual->getName();
        if ($nome === null) {
            return [];
        }

        $partes = explode('.', $nome);
        $achados = [];

        for ($nivel = count($partes) - 1; $nivel >= 1; $nivel--) {
            $pagina = $this->paginaDoNivel(implode('.', array_slice($partes, 0, $nivel)), $atual, $usuario);

            if ($pagina !== null && ! in_array($pagina['rota'], array_column($achados, 'rota'), true)) {
                $achados[] = $pagina;
            }
        }

        return $achados;
    }

    private function paginaDoNivel(string $nivel, Route $atual, User $usuario): ?array
    {
        $candidatos = [...array_map(fn (string $pagina) => "{$nivel}.{$pagina}", self::PAGINAS_DO_NIVEL), $nivel];

        foreach ($candidatos as $nome) {
            $rota = $nome === $atual->getName() ? null : $this->router->getRoutes()->getByName($nome);

            if ($rota === null || ! in_array('GET', $rota->methods(), true)) {
                continue;
            }

            $parametros = $this->parametros($rota, $atual);

            if ($parametros !== null && $this->autorizado($rota, $usuario)) {
                return ['rota' => $nome, 'params' => $parametros, 'url' => route($nome, $parametros, false)];
            }
        }

        return null;
    }

    /** Parametros que o alvo exige, tirados da rota atual; null se faltar algum. */
    private function parametros(Route $alvo, Route $atual): ?array
    {
        $disponiveis = $atual->originalParameters();
        $parametros = [];

        foreach ($alvo->parameterNames() as $nome) {
            if (array_key_exists($nome, $disponiveis)) {
                $parametros[$nome] = (string) $disponiveis[$nome];
            } elseif (! str_contains($alvo->uri(), '{'.$nome.'?}')) {
                return null;
            }
        }

        return $parametros;
    }

    /**
     * Avalia as regras de acesso declaradas na rota. `can:` com argumento de
     * modelo depende do registro do request e nao da para avaliar aqui: o
     * destino e descartado em vez de arriscar um 403.
     */
    private function autorizado(Route $rota, User $usuario): bool
    {
        foreach ($rota->middleware() as $middleware) {
            if (! is_string($middleware) || ! str_contains($middleware, ':')) {
                continue;
            }

            [$tipo, $argumentos] = explode(':', $middleware, 2);

            $permitido = match ($tipo) {
                'can', Authorize::class => ! str_contains($argumentos, ',') && $usuario->can($argumentos),
                'permission', PermissionMiddleware::class => $usuario->canAny(explode('|', explode(',', $argumentos)[0])),
                default => true,
            };

            if (! $permitido) {
                return false;
            }
        }

        return true;
    }
}
