<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Routing\RouteSignatureVerifier;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteSignatureParameters;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

/**
 * Gate de build: percorre as assinaturas de TODAS as rotas e confirma que cada
 * classe type-hintada (FormRequest, etc.) e resolvivel pelo autoloader. Mesmo
 * caminho que o Ziggy exercita em runtime (signatureParameters -> reflexao);
 * roda no Dockerfile apos o dump-autoload para falhar o BUILD em vez de a
 * aplicacao inteira em producao.
 */
class VerifyRouteSignaturesCommand extends Command
{
    protected $signature = 'route:verify-signatures';

    protected $description = 'Verifica que todas as classes das assinaturas de rota sao resolviveis (gate contra 500 de classmap obsoleto).';

    public function handle(Router $router, RouteSignatureVerifier $verifier): int
    {
        $classNames = [];
        $rotasQuebradas = [];

        foreach ($router->getRoutes() as $route) {
            try {
                $parameters = RouteSignatureParameters::fromAction($route->getAction());
            } catch (Throwable $e) {
                $rotasQuebradas[] = sprintf('%s [%s]: %s', $route->uri(), $route->getActionName(), $e->getMessage());

                continue;
            }

            foreach ($parameters as $parameter) {
                foreach ($this->classTypes($parameter->getType()) as $fqcn) {
                    $classNames[$fqcn] = true;
                }
            }
        }

        $irresolviveis = $verifier->unresolvable(array_keys($classNames));

        if ($rotasQuebradas === [] && $irresolviveis === []) {
            $this->info(sprintf('OK: %d classes de assinatura resolvidas em %d rotas.', count($classNames), count($router->getRoutes())));

            return self::SUCCESS;
        }

        foreach ($rotasQuebradas as $msg) {
            $this->error('Rota irresolvivel: '.$msg);
        }

        foreach ($irresolviveis as $class) {
            $this->error('Classe de assinatura irresolvivel: '.$class);
        }

        return self::FAILURE;
    }

    /**
     * Extrai os nomes de classe (FQCN) de um tipo de parametro, ignorando tipos
     * nativos e pseudo-tipos (self/static/parent).
     *
     * @return list<string>
     */
    private function classTypes(?ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return [];
            }

            $name = $type->getName();

            return in_array($name, ['self', 'static', 'parent'], true) ? [] : [$name];
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $names = [];

            foreach ($type->getTypes() as $inner) {
                foreach ($this->classTypes($inner) as $name) {
                    $names[] = $name;
                }
            }

            return $names;
        }

        return [];
    }
}
