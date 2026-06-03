// SDC/resources/js/Workers/ai.worker.js
import { loadPyodide } from "https://cdn.jsdelivr.net/pyodide/v0.23.4/full/pyodide.mjs";

let pyodide = null;
let pythonReady = false;

async function loadPyodideAndPackages() {
    try {
        pyodide = await loadPyodide();
        // Setup Python environment
        await pyodide.runPythonAsync(`
            import difflib
            import json

            def classify_intent(user_input, patterns_json):
                patterns = json.loads(patterns_json)
                best_match = "unknown"
                highest_ratio = 0.0

                input_lower = user_input.lower().strip()

                # Mensagens muito curtas (< 3 chars) vao direto para o servidor
                if len(input_lower) < 3:
                    return "complex_task"

                # Simple fuzzy matching
                for intent, keywords in patterns.items():
                    for keyword in keywords:
                        ratio = difflib.SequenceMatcher(None, input_lower, keyword.lower()).ratio()
                        if ratio > highest_ratio:
                            highest_ratio = ratio
                            best_match = intent

                # Threshold aumentado para 0.65 para reduzir falsos positivos
                if highest_ratio < 0.65:
                    return "complex_task"

                # Intencoes locais so sao validas para frases curtas (max 4 palavras)
                if best_match.startswith('local_') and len(input_lower.split()) > 4:
                    return "complex_task"

                return best_match
        `);
        pythonReady = true;
        postMessage({ type: 'READY' });
    } catch (e) {
        postMessage({ type: 'ERROR', message: e.toString() });
    }
}

loadPyodideAndPackages();

const INTENT_PATTERNS = {
    "local_greeting": ["oi", "olá", "bom dia", "boa tarde", "quem é você", "tudo bem"],
    "local_help":     ["ajuda", "como usar", "suporte", "preciso de ajuda", "o que você faz"],
    "local_clear":    ["limpar conversa", "apagar historico", "reiniciar chat"],
    "complex_task":   ["analisar", "relatório", "cálculo", "previsão", "alerta", "chuva", "protocolo", "rat", "abrigo", "enchente", "danos"]
};

self.onmessage = async (event) => {
    const { type, payload } = event.data;

    if (type === 'CLASSIFY') {
        if (!pythonReady) {
            postMessage({ type: 'ERROR', message: 'Python environment is loading...' });
            return;
        }

        try {
            const intent = pyodide.globals.get('classify_intent')(payload.text, JSON.stringify(INTENT_PATTERNS));

            // Decision Logic: 
            // - "local_*" -> Local WASM/Response
            // - "complex_task" -> Server API
            // - "unknown" -> Server API (Safe fallback)

            const isLocal = intent.startsWith('local_');

            postMessage({
                type: 'CLASSIFICATION_RESULT',
                payload: {
                    intent,
                    isLocal,
                    confidence: 1.0, // Mock confidence for difflib
                    original: payload.text
                }
            });
        } catch (error) {
            postMessage({ type: 'ERROR', message: error.toString() });
        }
    }
};
