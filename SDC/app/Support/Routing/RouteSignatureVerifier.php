<?php

declare(strict_types=1);

namespace App\Support\Routing;

use Throwable;

/**
 * Verifica se nomes de classe referenciados por assinaturas de rota podem ser
 * resolvidos pelo autoloader. Existe para detectar, em build-time, o modo de
 * falha do incidente de 10/06: um FormRequest type-hintado em controller cujo
 * arquivo nao e resolvivel (classmap obsoleto ou classe ausente) derrubava
 * toda pagina via reflexao do Ziggy (signatureParameters -> class_exists).
 */
class RouteSignatureVerifier
{
    /**
     * @param  iterable<string>  $classNames
     * @return list<string>  nomes que o autoloader nao conseguiu resolver
     */
    public function unresolvable(iterable $classNames): array
    {
        $unresolvable = [];

        foreach ($classNames as $class) {
            if (! $this->isResolvable($class)) {
                $unresolvable[] = $class;
            }
        }

        return $unresolvable;
    }

    private function isResolvable(string $class): bool
    {
        try {
            return class_exists($class)
                || interface_exists($class)
                || enum_exists($class)
                || trait_exists($class);
        } catch (Throwable) {
            // Autoload que aponta p/ arquivo inexistente levanta erro no include;
            // tratamos como irresolvivel em vez de propagar fatal.
            return false;
        }
    }
}
