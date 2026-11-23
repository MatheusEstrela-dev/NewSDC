<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Enums\CategoriaOferta;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\CertificadoInstrutorService;
use App\Modules\Treinamento\Services\ProfessorService;
use App\Modules\Treinamento\Support\ClassificacaoDeOferta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MeusCursosController extends Controller
{
    public function __construct(
        private readonly ProfessorService $professores,
        private readonly CertificadoInstrutorService $certificadosInstrutor,
    ) {}

    public function __invoke(Request $request): Response
    {
        $usuario = $request->user();
        $designados = array_flip($this->professores->idsDesignados($usuario));
        $categoria = CategoriaOferta::daRequisicao($request->query('categoria'));

        // A contagem das pastas vale para todos os cursos que a pessoa ve aqui;
        // na raiz (sem categoria) so as pastas aparecem, dentro de uma pasta so os cursos dela.
        $todos = $this->professores->cursosDe($usuario);

        $cursos = $todos
            ->filter(fn (Treinamento $t) => $categoria !== null && $t->categoriaOferta() === $categoria)
            ->loadCount('inscricoesAprovadas')
            ->map(fn (Treinamento $t) => [
                'id' => $t->id,
                'titulo' => $t->titulo,
                'descricao' => $t->descricao,
                'carga_horaria' => $t->carga_horaria,
                'instrutor' => $t->instrutor,
                'capa_url' => $t->capaUrl(),
                'publico_alvo_label' => $t->publico_alvo?->getLabel(),
                'categoria_oferta' => $t->categoriaOferta()->value,
                'status' => $t->status->value,
                'status_label' => $t->status->getLabel(),
                'publicado' => $t->estaPublicado(),
                'situacao' => $t->situacaoEfetiva()->value,
                'situacao_label' => $t->situacaoEfetiva()->getLabel(),
                'alunos' => (int) $t->inscricoes_aprovadas_count,
                // Presencial, semipresencial, online e evento nao tem Area do Gestor propria: o card abre o detalhe e os inscritos.
                'eh_ead' => $t->isEad(),
                'tipo_label' => $t->tipo->getLabel(),
                'data_inicio' => $t->data_inicio?->toDateString(),
                'data_fim' => $t->data_fim?->toDateString(),
                'local' => $t->local,
                'url_abrir' => route('treinamentos.show', $t->id),
                'url_inscritos' => route('treinamentos.inscricoes.index', $t->id),
                'url_gestao' => route('treinamentos.gestor.show', $t->id),
                'designado' => isset($designados[(int) $t->id]),
                // Certificado de instrutor: so de quem ministrou, depois que o curso termina.
                'certificado_instrutor_url' => isset($designados[(int) $t->id]) && $this->certificadosInstrutor->cursoEncerrado($t)
                    ? route('treinamentos.certificados-instrutor.meu', $t->id)
                    : null,
            ])
            ->values();

        return Inertia::render('Treinamento/Professor/MeusCursos', [
            'cursos' => $cursos,
            'categorias' => CategoriaOferta::mosaico(ClassificacaoDeOferta::totaisDe($todos), $categoria),
            // Abrir curso e so de quem ja cria treinamento (admin); o backend recusa os demais.
            'pode_abrir' => (bool) $request->user()->can('treinamento.cursos.create'),
            // Trocar a foto das pastas de categoria e de quem administra cursos.
            'pode_gestao' => (bool) $request->user()->can('treinamento.gestor.acessar'),
            'pode_editar_capa' => (bool) $request->user()->can('treinamento.cursos.edit'),
        ]);
    }
}
