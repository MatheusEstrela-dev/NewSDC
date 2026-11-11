<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\TreinamentoProfessor;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Services\TrilhaService;
use App\Modules\Treinamento\Support\PermissaoDeVideo;
use App\Services\Auth\SessionVersionService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Decide se a sessao atual pode receber os arquivos HLS de um video. Chamado
 * pelo Caddy (forward_auth) a cada segmento, entao a decisao positiva fica no
 * Cache (redis, compartilhado entre as replicas) por sessao/video/versao; a
 * negativa nunca e cacheada. Sem estado em memoria do processo.
 */
class AutorizacaoVideoService
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly PermissaoDeVideo $permissao,
        private readonly SessionVersionService $versoes,
    ) {}

    /**
     * Aceita o caminho original da requisicao (`/videos/{id}/{versao}/...`,
     * com ou sem o prefixo `/videos` e com query opcional).
     */
    public function autorizarUri(string $uri): bool
    {
        $partes = $this->interpretar($uri);
        if ($partes === null) {
            return false;
        }

        // Antes do cache: sessao deslogada, conta desativada ou versao de
        // sessao vencida perdem o acesso na hora, nao ao fim do TTL.
        $servidor = $this->servidorDaSessao();
        $cidadao = $this->logadoNaSessao('cidadao');
        if ($servidor === null && $cidadao === null) {
            return false;
        }

        [$videoId, $versao] = $partes;
        $chave = $this->chaveCache($videoId, $versao);

        if (Cache::has($chave)) {
            return true;
        }

        $video = Video::query()->find($videoId);
        if ($video === null || ! $video->temVersaoPublicada() || ! $this->versaoServivel($video, $versao)) {
            return false;
        }

        if (! $this->podeAssistir($video, $servidor, $cidadao)) {
            return false;
        }

        Cache::put($chave, true, (int) config('treinamento.video.auth_cache_segundos'));

        return true;
    }

    public function podeAssistir(Video $video, ?Authenticatable $servidor, ?Authenticatable $cidadao): bool
    {
        if ($servidor === null && $cidadao === null) {
            return false;
        }

        if ($servidor instanceof User && $this->permissao->podeGerenciar($video, $servidor)) {
            return true;
        }

        $itens = $this->itensAtivos($video);
        if ($itens->isEmpty()) {
            return false;
        }

        if ($servidor instanceof User && $this->lecionaEmAlgum($servidor, $itens)) {
            return true;
        }

        foreach ([$servidor, $cidadao] as $inscrito) {
            if ($inscrito instanceof Model && $this->alunoComItemLiberado($inscrito, $itens)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mesmas regras dos middlewares CheckUserActive e EnsurePasswordChanged,
     * que nao rodam nas rotas de video: conta ativa, versao de sessao igual a
     * gravada no login e senha provisoria ja trocada.
     */
    private function servidorDaSessao(): ?Authenticatable
    {
        $servidor = $this->logadoNaSessao('web');
        if (! $servidor instanceof User) {
            return $servidor;
        }

        $valido = $servidor->canAuthenticate()
            && ! $servidor->must_change_password
            && $this->versoes->matches(session()->driver(), $servidor);

        return $valido ? $servidor : null;
    }

    /**
     * Quem a sessao diz que esta logado. O cookie "lembrar-me" nao vale aqui:
     * ele recriaria a sessao, e a destas rotas e somente leitura. Por isso a
     * busca vai direto ao provider, sem `$guard->user()`, que cai no cookie
     * quando o id da sessao nao existe mais.
     */
    private function logadoNaSessao(string $guard): ?Authenticatable
    {
        $guard = Auth::guard($guard);
        if ($guard->hasUser()) {
            return $guard->user();
        }

        $id = session()->get($guard->getName());

        return $id === null ? null : $guard->getProvider()->retrieveById($id);
    }

    /**
     * @return array{0: int, 1: string}|null [videoId, versao], ou null se o caminho nao e valido
     */
    private function interpretar(string $uri): ?array
    {
        $caminho = strstr($uri, '?', true);
        $caminho = $caminho === false ? $uri : $caminho;

        // Barra ou contrabarra codificada nunca e legitima nos caminhos do HLS.
        if (preg_match('#%(?:2f|5c)#i', $caminho) === 1) {
            return null;
        }

        // Decodifica uma unica vez; tudo o que sobrar e validado por lista branca.
        $caminho = rawurldecode($caminho);

        if (preg_match('#^(?:/videos)?/(\d{1,18})/([A-Za-z0-9_-]{1,40})/([A-Za-z0-9_.-]+(?:/[A-Za-z0-9_.-]+)*)$#D', $caminho, $m) !== 1) {
            return null;
        }

        // Segmento oculto (".tmp-*" = transcodificacao nao publicada, "." e "..").
        foreach (explode('/', $m[3]) as $segmento) {
            if (str_starts_with($segmento, '.')) {
                return null;
            }
        }

        return [(int) $m[1], $m[2]];
    }

    /** A versao pedida e a publicada ou a imediatamente anterior (janela de troca). */
    private function versaoServivel(Video $video, string $versao): bool
    {
        return $versao === $video->hls_versao
            || $versao === Cache::get("video_versao_anterior:{$video->id}");
    }

    private function chaveCache(int $videoId, string $versao): string
    {
        return 'video_auth:'.session()->getId().":{$videoId}:{$versao}";
    }

    /**
     * Itens nao excluidos, de pastas e cursos nao excluidos, que apontam para o video.
     *
     * @return Collection<int, ModuloItem>
     */
    private function itensAtivos(Video $video): Collection
    {
        return ModuloItem::query()
            ->where('video_id', $video->id)
            ->with('modulo.treinamento')
            ->get()
            ->filter(fn (ModuloItem $item) => $item->modulo?->treinamento !== null)
            ->values();
    }

    /** @param  Collection<int, ModuloItem>  $itens */
    private function lecionaEmAlgum(User $servidor, Collection $itens): bool
    {
        return TreinamentoProfessor::query()
            ->where('user_id', $servidor->id)
            ->whereIn('treinamento_id', $itens->map(fn (ModuloItem $i) => $i->modulo->treinamento_id)->unique()->all())
            ->exists();
    }

    /** @param  Collection<int, ModuloItem>  $itens */
    private function alunoComItemLiberado(Model $inscrito, Collection $itens): bool
    {
        $elegiveis = $itens->filter(function (ModuloItem $item) use ($inscrito) {
            $curso = $item->modulo->treinamento;

            return $curso->isEad() && $curso->estaPublicado() && $curso->publico_alvo->aceita($inscrito);
        });
        if ($elegiveis->isEmpty()) {
            return false;
        }

        $inscricoes = Inscricao::query()
            ->whereIn('treinamento_id', $elegiveis->map(fn (ModuloItem $i) => $i->modulo->treinamento_id)->unique()->all())
            ->where('inscrito_type', $inscrito->getMorphClass())
            ->where('inscrito_id', $inscrito->getKey())
            ->where('status', StatusInscricao::APROVADA->value)
            ->get()
            ->keyBy('treinamento_id');

        foreach ($elegiveis as $item) {
            $inscricao = $inscricoes->get($item->modulo->treinamento_id);
            if ($inscricao !== null && $this->trilha->itemLiberado($inscricao, $item)) {
                return true;
            }
        }

        return false;
    }
}
