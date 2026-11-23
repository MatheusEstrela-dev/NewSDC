<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Categoria;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\CategoriaOferta;
use App\Modules\Treinamento\Services\CapaDeCategoriaService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Foto de fundo da pasta de categoria. Rota publica de proposito: e imagem de
 * divulgacao, sem dado pessoal, e serve as telas internas e o portal do cidadao.
 * Roda sem sessao nem cookies (ver routes/web.php).
 */
class CapaDeCategoriaController extends Controller
{
    public function __construct(private readonly CapaDeCategoriaService $capas) {}

    public function __invoke(CategoriaOferta $categoria): Response
    {
        return $this->capas->responder($categoria);
    }
}
