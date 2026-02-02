<?php

namespace App\Core\IA\Http\Controllers;

use App\Core\IA\IAServices;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AIPluginController extends Controller
{
    public function __construct(
        protected IAServices $aiServices
    ) {}

    /**
     * @OA\Get(
     *     path="/api/ia/plugins",
     *     summary="Lista todos os plugins de IA disponíveis e seus schemas",
     *     tags={"IA"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de plugins",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function index()
    {
        $plugins = $this->aiServices->getPlugins();
        $list = [];

        foreach ($plugins as $name => $plugin) {
            $list[] = [
                'name' => $name,
                'description' => $plugin->getDescription(),
                'schema' => $plugin->getSchema() // Retorna o JSON Schema para Function Calling
            ];
        }

        return response()->json($list);
    }

    /**
     * @OA\Post(
     *     path="/api/ia/execute-plugin",
     *     summary="Executa um plugin de IA dinamicamente",
     *     tags={"IA"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="plugin", type="string", example="consultar_vistorias"),
     *             @OA\Property(property="payload", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sucesso",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function execute(Request $request)
    {
        $request->validate([
            'plugin' => 'required|string',
            'payload' => 'array',
        ]);

        try {
            $result = $this->aiServices->executePlugin(
                $request->input('plugin'),
                $request->input('payload', [])
            );

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
