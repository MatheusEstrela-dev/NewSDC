/**
 * AI Worker - Processamento Local Otimizado para SPA
 *
 * ESTRATEGIA DE PERFORMANCE:
 * 1. JS puro como processador primario (< 1ms)
 * 2. Pyodide carrega em background (opcional)
 * 3. Cache LRU para queries repetidas
 * 4. Zero blocking - resposta imediata sempre
 */

// Cache LRU simples
const cache = new Map();
const CACHE_MAX_SIZE = 100;

// Estado do Pyodide (enhancement opcional)
let pyodide = null;
let pyodideStatus = 'idle'; // idle | loading | ready | failed

// Configuracao de intents e entidades
const INTENT_CONFIG = {
    rat: {
        keywords: ['rat', 'protocolo', 'vistoria', 'relatorio', 'atendimento', 'tecnico'],
        weight: 1.5
    },
    meteorologia: {
        keywords: ['tempo', 'chuva', 'previsao', 'alerta', 'clima', 'meteorologico', 'tempestade'],
        weight: 1.0
    },
    ajuda_humanitaria: {
        keywords: ['ajuda', 'humanitaria', 'cesta', 'doacao', 'abrigo', 'kit'],
        weight: 1.0
    },
    emergencia: {
        keywords: ['emergencia', 'socorro', 'urgente', 'acidente', 'desastre', 'risco'],
        weight: 2.0
    },
    documento: {
        keywords: ['documento', 'arquivo', 'pdf', 'exportar', 'gerar', 'relatorio'],
        weight: 0.8
    },
    cadastro: {
        keywords: ['cadastro', 'cadastrar', 'registrar', 'novo', 'criar'],
        weight: 0.8
    },
    consulta: {
        keywords: ['consultar', 'buscar', 'pesquisar', 'verificar', 'checar', 'status'],
        weight: 0.7
    }
};

const ENTITY_PATTERNS = {
    protocolo: /\b(\d{4,10})\b/g,
    municipio: /(?:de|em|para|cidade)\s+([A-Z][a-záéíóúãõ]+(?:\s+[A-Z][a-záéíóúãõ]+)*)/gi,
    data: /\b(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\b/g,
    telefone: /\b(\d{2}\s?\d{4,5}[\-\s]?\d{4})\b/g,
    cpf: /\b(\d{3}\.?\d{3}\.?\d{3}[\-\.]?\d{2})\b/g
};

/**
 * Processador JS Puro - ULTRA RAPIDO (< 1ms)
 */
function processJS(text) {
    const cacheKey = text.trim().toLowerCase();

    // Check cache first
    if (cache.has(cacheKey)) {
        return { ...cache.get(cacheKey), cached: true };
    }

    const startTime = performance.now();

    // Clean text
    const cleaned = text.replace(/\s+/g, ' ').trim();
    const textLower = cleaned.toLowerCase();

    // Detect intent with scoring
    const scores = {};
    for (const [intent, config] of Object.entries(INTENT_CONFIG)) {
        let score = 0;
        for (const keyword of config.keywords) {
            if (textLower.includes(keyword)) {
                score += config.weight;
            }
        }
        if (score > 0) {
            scores[intent] = score;
        }
    }

    // Get best intent
    let detectedIntent = 'geral';
    let maxScore = 0;
    for (const [intent, score] of Object.entries(scores)) {
        if (score > maxScore) {
            maxScore = score;
            detectedIntent = intent;
        }
    }

    // Extract entities
    const entities = {};
    for (const [type, pattern] of Object.entries(ENTITY_PATTERNS)) {
        const matches = [...cleaned.matchAll(pattern)].map(m => m[1]);
        if (matches.length > 0) {
            entities[type] = matches.length === 1 ? matches[0] : matches;
        }
    }

    const result = {
        text: cleaned,
        intent: detectedIntent,
        confidence: maxScore > 0 ? Math.min(maxScore / 3, 1) : 0,
        entities,
        processor: 'js',
        processingTime: performance.now() - startTime
    };

    // Add to cache
    if (cache.size >= CACHE_MAX_SIZE) {
        const firstKey = cache.keys().next().value;
        cache.delete(firstKey);
    }
    cache.set(cacheKey, result);

    return result;
}

/**
 * Carrega Pyodide em background (NAO BLOQUEIA)
 */
async function loadPyodideBackground() {
    if (pyodideStatus !== 'idle') return;

    pyodideStatus = 'loading';

    try {
        // Carrega de forma lazy
        importScripts('https://cdn.jsdelivr.net/pyodide/v0.25.0/full/pyodide.js');

        pyodide = await loadPyodide({
            indexURL: 'https://cdn.jsdelivr.net/pyodide/v0.25.0/full/',
        });

        // Codigo Python para Fuzzy Matching com difflib (Standard Lib)
        await pyodide.runPythonAsync(`
import difflib
import json

# Config de intents (espelho do JS, mas para fuzzy)
INTENTS = {
    "rat": ["rat", "protocolo", "vistoria", "relatorio", "atendimento"],
    "meteorologia": ["tempo", "chuva", "previsao", "alerta", "clima"],
    "ajuda_humanitaria": ["ajuda", "humanitaria", "cesta", "doacao", "abrigo"],
    "emergencia": ["emergencia", "socorro", "urgente", "acidente", "desastre"],
    "documento": ["documento", "arquivo", "pdf", "exportar", "gerar"],
    "cadastro": ["cadastro", "cadastrar", "registrar", "novo", "criar"],
    "consulta": ["consultar", "buscar", "pesquisar", "verificar", "status"]
}

def advanced_extract(text):
    text = text.lower()
    scores = {}
    
    # 1. Fuzzy Matching nas Keywords
    for intent, keywords in INTENTS.items():
        max_score = 0.0
        
        # Verifica presenca exata (rapido)
        for k in keywords:
            if k in text:
                max_score = 1.0
                break
        
        # Se nao achou exato, tenta fuzzy
        if max_score < 1.0:
            for k in keywords:
                # SequenceMatcher para similaridade
                ratio = difflib.SequenceMatcher(None, text, k).ratio()
                # Boost se a keyword for parte do texto (ex: "vistoria" em "fazer vistoria")
                if k in text or text in k: 
                    ratio = 0.9
                
                if ratio > max_score:
                    max_score = ratio
        
        if max_score > 0.4: # Cutoff minimo
            scores[intent] = max_score

    # Escolhe o melhor
    best_intent = "geral"
    best_score = 0.0
    
    if scores:
        best_intent = max(scores, key=scores.get)
        best_score = scores[best_intent]
    
    return {
        "intent": best_intent,
        "confidence": best_score,
        "scores": scores,
        "processor": "python-difflib"
    }
        `);

        pyodideStatus = 'ready';
        self.postMessage({ type: 'pyodide_ready' });
    } catch (e) {
        pyodideStatus = 'failed';
        // Falha silenciosa - JS puro continua funcionando
    }
}

/**
 * Handler de mensagens - RESPOSTA IMEDIATA
 */
self.onmessage = (event) => {
    const { type, text, requestId } = event.data;

    switch (type) {
        case 'preprocess': {
            // 1. Processamento JS (Instantaneo)
            let result = processJS(text);
            
            // 2. Tenta Melhorar com Python (se disponivel)
            if (pyodideStatus === 'ready' && pyodide) {
                try {
                    // Importa dados do JS para Python
                    const pyResultProxy = await pyodide.runPythonAsync(`advanced_extract('${text.replace(/'/g, "\\'")}')`);
                    const pyResult = pyResultProxy.toJs();
                    
                    // Se Python tiver confianca maior ou igual, usa ele (pois difflib eh mais robusto)
                    if (pyResult.confidence >= result.confidence) {
                        result = {
                            ...result,
                            intent: pyResult.intent,
                            confidence: pyResult.confidence,
                            processor: pyResult.processor,
                            python_scores: pyResult.scores
                        };
                    }
                    
                    // Cleanup proxy
                    pyResultProxy.destroy();
                } catch (e) {
                    console.error('Python processing failed:', e);
                }
            }

            self.postMessage({
                ...result,
                requestId,
                type: 'result'
            });
            break;
        }

        case 'init': {
            // Pre-aquece o cache com termos comuns
            const commonTerms = ['rat', 'protocolo', 'ajuda', 'tempo', 'chuva'];
            commonTerms.forEach(term => processJS(term));

            self.postMessage({ type: 'ready', cacheSize: cache.size });

            // Carrega Pyodide em background (opcional, nao bloqueia)
            // Descomente se precisar de processamento Python avancado
            // setTimeout(() => loadPyodideBackground(), 5000);
            break;
        }

        case 'clear_cache': {
            cache.clear();
            self.postMessage({ type: 'cache_cleared' });
            break;
        }

        case 'status': {
            self.postMessage({
                type: 'status',
                cacheSize: cache.size,
                pyodideStatus,
                ready: true
            });
            break;
        }

        default:
            self.postMessage({ type: 'error', error: 'Unknown message type' });
    }
};

// Auto-inicializa
self.postMessage({ type: 'ready', processor: 'js', instant: true });
