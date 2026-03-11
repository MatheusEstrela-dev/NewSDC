<?php

namespace App\Http\Controllers\Log;

use App\Http\Controllers\Controller;
use App\Models\Log\Logs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $query = Logs::query()->orderBy('created_at', 'desc');

        if ($request->filled('level')) {
            $query->where('level', strtolower($request->level));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('layer')) {
            $query->where('layer', $request->layer);
        }

        if ($request->filled('class')) {
            $query->where('class', 'like', '%' . $request->class . '%');
        }

        if ($request->filled('method')) {
            $query->where('method', 'like', '%' . $request->method . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', '%' . $search . '%')
                  ->orWhere('formatted', 'like', '%' . $search . '%')
                  ->orWhere('class', 'like', '%' . $search . '%')
                  ->orWhere('url', 'like', '%' . $search . '%');
            });
        }

        if ($request->boolean('errors_only')) {
            $query->errors();
        }

        $limit = min($request->get('limit', 50), 1000);

        return response()->json([
            'success' => true,
            'logs' => $query->paginate($limit),
        ]);
    }

    public function show($id)
    {
        $log = Logs::findOrFail($id);
        return response()->json([
            'success' => true,
            'log' => $log,
        ]);
    }

    public function levels()
    {
        $levels = Logs::select('level')
            ->distinct()
            ->whereNotNull('level')
            ->pluck('level');

        return response()->json([
            'success' => true,
            'levels' => $levels,
        ]);
    }

    public function channels()
    {
        $channels = Logs::select('channel')
            ->distinct()
            ->whereNotNull('channel')
            ->pluck('channel');

        return response()->json([
            'success' => true,
            'channels' => $channels,
        ]);
    }

    public function stats()
    {
        return response()->json([
            'success' => true,
            'stats' => [
                'total' => Logs::count(),
                'today' => Logs::whereDate('created_at', today())->count(),
                'errors_today' => Logs::whereDate('created_at', today())
                    ->whereIn('level', ['error', 'critical', 'emergency', 'alert'])
                    ->count(),
                'by_level' => Logs::selectRaw('level, count(*) as count')
                    ->groupBy('level')
                    ->pluck('count', 'level'),
                'by_layer' => Logs::selectRaw('layer, count(*) as count')
                    ->whereNotNull('layer')
                    ->groupBy('layer')
                    ->pluck('count', 'layer'),
                'recent_errors' => Logs::errors()
                    ->recent(24)
                    ->select(['id', 'level', 'message', 'class', 'method', 'layer', 'url', 'created_at'])
                    ->limit(10)
                    ->get(),
            ],
        ]);
    }

    public function layers()
    {
        $layers = Logs::select('layer')
            ->distinct()
            ->whereNotNull('layer')
            ->pluck('layer');

        return response()->json([
            'success' => true,
            'layers' => $layers,
        ]);
    }

    public function files()
    {
        $logPath = storage_path('logs');
        $files = [];

        if (is_dir($logPath)) {
            $allFiles = scandir($logPath);
            foreach ($allFiles as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $fullPath = $logPath . DIRECTORY_SEPARATOR . $file;
                    $files[] = [
                        'name' => $file,
                        'size' => filesize($fullPath),
                        'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                    ];
                }
            }
        }

        usort($files, fn($a, $b) => strtotime($b['modified']) - strtotime($a['modified']));

        return response()->json([
            'success' => true,
            'files' => $files,
        ]);
    }

    public function store(Request $request)
    {
        $logs = new Logs();
        $logs->level = $request->level;
        $logs->message = $request->message;
        $logs->user_id = $request->user_id;
        $logs->save();

        return response()->json([
            'success' => true,
            'log' => $logs,
        ]);
    }
}
