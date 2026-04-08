<?php

declare(strict_types=1);

namespace Tests\Unit\Decretacoes\Requests;

use App\Modules\Decretacoes\Requests\DesastreDataRequest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\TestCase;

class DesastreDataRequestTest extends TestCase
{
    private Factory $validatorFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $loader = new FileLoader(new Filesystem(), __DIR__);
        $translator = new Translator($loader, 'en');
        $this->validatorFactory = new Factory($translator);
    }

    private function makeData(): array
    {
        return [
            'municipios' => [
                [
                    'id' => 1,
                    'n_protocolo_fide' => null,
                    'categorias' => [
                        [
                            'id' => 1,
                            'desastres' => [
                                [
                                    'id' => 1,
                                    'descricao' => null,
                                    'items' => [
                                        [
                                            'id' => 1,
                                            'campos' => [
                                                [
                                                    'id' => 1,
                                                    'titulo' => 'Quantidade',
                                                    'valor' => '100',
                                                    'tipo' => 'number',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Retorna apenas as regras que nao dependem de banco de dados.
     * Remove a regra `exists:` para manter o teste unitario puro.
     *
     * @return array<string, mixed>
     */
    private function getRulesWithoutDb(): array
    {
        $request = new DesastreDataRequest();
        $rules = $request->rules();

        return array_map(static function ($rule) {
            if (is_string($rule)) {
                $parts = explode('|', $rule);
                $filtered = array_filter($parts, static fn($part) => !str_starts_with($part, 'exists:'));
                return implode('|', $filtered);
            }
            return $rule;
        }, $rules);
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return $this->validatorFactory->make($data, $this->getRulesWithoutDb());
    }

    public function test_tipo_number_e_valido(): void
    {
        $data = $this->makeData();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'number';
        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_currency_e_valido(): void
    {
        $data = $this->makeData();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'currency';
        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_radio_e_valido(): void
    {
        $data = $this->makeData();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'radio';
        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_select_e_valido(): void
    {
        $data = $this->makeData();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'select';
        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_textarea_e_valido(): void
    {
        $data = $this->makeData();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'textarea';
        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_text_e_valido(): void
    {
        $data = $this->makeData();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'text';
        $this->assertFalse($this->validate($data)->fails());
    }

    public function test_tipo_invalido_falha(): void
    {
        $data = $this->makeData();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['tipo'] = 'checkbox';
        $this->assertTrue($this->validate($data)->fails());
    }

    public function test_valor_nulo_e_aceito(): void
    {
        $data = $this->makeData();
        $data['municipios'][0]['categorias'][0]['desastres'][0]['items'][0]['campos'][0]['valor'] = null;
        $this->assertFalse($this->validate($data)->fails());
    }
}
