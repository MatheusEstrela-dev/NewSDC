<?php

namespace App\Core\IA;

use App\Core\IA\Contracts\AIPluginInterface;
use App\Core\IA\Plugins\CivilDefense\VistoriaPlugin;
use App\Core\IA\Plugins\Communication\WhatsAppPlugin;
use App\Core\IA\Plugins\Analysis\DocumentAnalysisPlugin;
use InvalidArgumentException;

use App\Core\IA\Models\AIExecutionLog;
use Illuminate\Support\Facades\Auth;

class IAServices
{
    protected array $plugins = [];

    public function __construct()
    {
        // Register plugins
        $this->register(new VistoriaPlugin());
        $this->register(new WhatsAppPlugin());
        $this->register(new DocumentAnalysisPlugin());
    }

    public function register(AIPluginInterface $plugin): void
    {
        $this->plugins[$plugin->getName()] = $plugin;
    }

    public function executePlugin(string $name, array $payload)
    {
        if (!isset($this->plugins[$name])) {
            throw new InvalidArgumentException("Plugin [{$name}] not found.");
        }

        $start = microtime(true);
        $status = 'success';
        $error = null;
        $result = null;

        try {
            $result = $this->plugins[$name]->execute($payload);
            return $result;
        } catch (\Exception $e) {
            $status = 'error';
            $error = $e->getMessage();
            throw $e;
        } finally {
            $duration = (microtime(true) - $start) * 1000;

            AIExecutionLog::create([
                'plugin_name' => $name,
                'input_params' => $payload,
                'output_result' => $status === 'success' ? (array) $result : null,
                'status' => $status,
                'error_message' => $error,
                'execution_time_ms' => $duration,
                'user_id' => Auth::id(), // Captura o usuário se estiver logado
                'ip_address' => request()->ip(),
            ]);
        }
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }
}
