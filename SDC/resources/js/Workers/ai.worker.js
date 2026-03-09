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

                # Simple fuzzy matching
                for intent, keywords in patterns.items():
                    for keyword in keywords:
                        ratio = difflib.SequenceMatcher(None, user_input.lower(), keyword.lower()).ratio()
                        if ratio > highest_ratio:
                            highest_ratio = ratio
                            best_match = intent
                
                # Threshold for confidence
                if highest_ratio < 0.4: # Lower threshold to catch more
                    return "complex_task" # Default to server if unsure but looks like a task
                
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
    "local_help": ["ajuda", "como usar", "manual", "suporte", "menu"],
    "local_clear": ["limpar", "apagar", "reiniciar"],
    "complex_task": ["analisar", "relatório", "cálculo", "previsão", "alerta", "chuva", "protocolo", "rat", "abrigo", "enchente", "danos"]
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
