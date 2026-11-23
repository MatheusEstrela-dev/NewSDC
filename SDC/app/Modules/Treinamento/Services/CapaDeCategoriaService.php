<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\CategoriaOferta;
use App\Modules\Treinamento\Support\OtimizadorDeImagem;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Capa (foto de fundo) das pastas de categoria da vitrine de cursos.
 *
 * Fica no disco privado do Treinamento, o mesmo da capa do curso (volume
 * persistente: sobrevive aos deploys), uma por categoria em categorias/<VALOR>/;
 * a nova substitui a anterior. Nao ha coluna no banco: a capa atual e o arquivo
 * da pasta. O nome do arquivo (UUID) vai no endereco (?v=), entao a imagem de um
 * endereco nunca muda e pode ficar no cache do navegador.
 *
 * Desempenho: o mapa das capas enviadas (todas as categorias) fica numa unica
 * chave de cache, lida uma vez por tela; o disco (pode ser Azure) so e listado
 * quando o mapa e reconstruido, e o mapa e esquecido a cada troca/remocao. Troca
 * e remocao usam trava por categoria (dois envios simultaneos nao deixam orfao).
 *
 * Sem capa enviada, vale a imagem estatica opcional em
 * public/imgs/treinamento/categorias/<valor>.(webp|jpg|png); sem nenhuma, null
 * (a pasta usa o gradiente com o icone da categoria).
 */
class CapaDeCategoriaService
{
    private const PASTA = 'categorias';

    private const PASTA_ESTATICA = 'imgs/treinamento/categorias';

    private const EXTENSOES_ESTATICAS = ['webp', 'jpg', 'png'];

    private const CHAVE = 'treinamento:categoria-capas:v2';

    private const CACHE_TTL = 86400;

    public function __construct(private readonly ArquivoCursoService $arquivos) {}

    public function trocar(CategoriaOferta $categoria, UploadedFile $imagem): void
    {
        $this->comTrava($categoria, function () use ($categoria, $imagem): void {
            $anteriores = $this->disco()->files($this->pasta($categoria));
            $nova = OtimizadorDeImagem::paraCapa(
                $imagem,
                fn (UploadedFile $otimizada) => $this->arquivos->guardar($otimizada, $this->pasta($categoria))['arquivo_path'],
            );

            $this->disco()->delete(array_values(array_diff($anteriores, [$nova])));
        });
    }

    public function remover(CategoriaOferta $categoria): void
    {
        $this->comTrava($categoria, function () use ($categoria): void {
            $this->disco()->delete($this->disco()->files($this->pasta($categoria)));
        });
    }

    /**
     * Imagem da pasta. Sem capa, 404 sem corpo (e imagem: nao ha pagina de erro
     * para montar), com cache curto para nao bater no disco a cada pedido.
     */
    public function responder(CategoriaOferta $categoria): Response
    {
        $atual = $this->enviadas()[$categoria->value] ?? null;

        if ($atual === null || ! $this->disco()->exists($atual['path'])) {
            return response('', 404, ['Cache-Control' => 'public, max-age=300']);
        }

        return $this->disco()->response($atual['path'], null, [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Foto de cada pasta, resolvida de uma vez (uma leitura de cache por tela).
     *
     * @return array<string, array{imagem: ?string, propria: bool}> por valor da categoria
     */
    public function todas(): array
    {
        $enviadas = $this->enviadas();
        $resultado = [];

        foreach (CategoriaOferta::cases() as $categoria) {
            $atual = $enviadas[$categoria->value] ?? null;
            $resultado[$categoria->value] = [
                'imagem' => $atual !== null
                    ? route('treinamentos.categorias.capa', ['categoria' => $categoria->value, 'v' => $atual['versao']])
                    : $this->estatica($categoria),
                // So a enviada pela tela conta como propria: a estatica nao se remove pela tela.
                'propria' => $atual !== null,
            ];
        }

        return $resultado;
    }

    /** @return array<string, array{path: string, versao: string}> capas enviadas, por valor da categoria */
    private function enviadas(): array
    {
        return Cache::remember(self::CHAVE, self::CACHE_TTL, function (): array {
            $mapa = [];
            foreach (CategoriaOferta::cases() as $categoria) {
                $path = $this->maisRecente($categoria);
                if ($path !== null) {
                    $mapa[$categoria->value] = ['path' => $path, 'versao' => pathinfo($path, PATHINFO_FILENAME)];
                }
            }

            return $mapa;
        });
    }

    /** O arquivo da pasta; se sobrou mais de um (falha no meio de uma troca), o mais recente. */
    private function maisRecente(CategoriaOferta $categoria): ?string
    {
        $arquivos = $this->disco()->files($this->pasta($categoria));

        if (count($arquivos) <= 1) {
            return $arquivos[0] ?? null;
        }

        usort($arquivos, fn (string $a, string $b) => $this->disco()->lastModified($b) <=> $this->disco()->lastModified($a));

        return $arquivos[0];
    }

    private function estatica(CategoriaOferta $categoria): ?string
    {
        foreach (self::EXTENSOES_ESTATICAS as $extensao) {
            $relativo = self::PASTA_ESTATICA.'/'.strtolower($categoria->value).'.'.$extensao;
            if (is_file(public_path($relativo))) {
                return asset($relativo);
            }
        }

        return null;
    }

    private function comTrava(CategoriaOferta $categoria, callable $operacao): void
    {
        Cache::lock(self::CHAVE.':trava:'.$categoria->value, 30)->block(10, $operacao);
        Cache::forget(self::CHAVE);
    }

    private function pasta(CategoriaOferta $categoria): string
    {
        return self::PASTA.'/'.$categoria->value;
    }

    private function disco(): Filesystem
    {
        return Storage::disk(ArquivoCursoService::DISK);
    }
}
