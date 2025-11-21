<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Autenticação",
 *     description="Endpoints para autenticação e geração de tokens"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Autentica um usuário e retorna token",
     *     description="Realiza login e retorna um token de acesso para uso nas APIs",
     *     operationId="login",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cpf", "password"},
     *             @OA\Property(property="cpf", type="string", example="12345678900"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="1|abcdef123456..."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Admin Geral"),
     *                 @OA\Property(property="email", type="string", example="admin@defesa.mg.gov.br")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Credenciais inválidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="As credenciais fornecidas estão incorretas.")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'cpf' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('cpf', 'password');
        
        // Buscar usuário por CPF
        $user = User::where('cpf', $credentials['cpf'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'cpf' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Revogar tokens anteriores (opcional)
        $user->tokens()->delete();

        // Criar novo token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'cpf' => $user->cpf,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Revoga o token de autenticação",
     *     description="Revoga o token atual do usuário autenticado",
     *     operationId="logout",
     *     tags={"Autenticação"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logout realizado com sucesso")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="Retorna dados do usuário autenticado",
     *     description="Retorna os dados do usuário atualmente autenticado",
     *     operationId="me",
     *     tags={"Autenticação"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dados do usuário",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Admin Geral"),
     *                 @OA\Property(property="email", type="string", example="admin@defesa.mg.gov.br"),
     *                 @OA\Property(property="cpf", type="string", example="12345678900")
     *             )
     *         )
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'cpf' => $request->user()->cpf,
            ],
        ]);
    }
}

