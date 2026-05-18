#!/usr/bin/env node
/*
 * Verifica conformidade com o contrato de responsividade.
 * Documentacao: resources/js/design-system/responsive-contract.md
 *
 * Falha se encontrar:
 *   1. <table> direto em Pages/Components/Modulo (sem estar dentro de ResponsiveTable.vue ou TableMobileCard)
 *   2. overflow-x-auto envolvendo <table> como unica estrategia mobile
 *
 * Uso:
 *   node scripts/check-responsive-contract.js
 *
 * Como pre-commit:
 *   adicionar em .husky/pre-commit ou rodar via "npm run lint:responsive"
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ROOT = path.resolve(__dirname, '..');

const SCAN_DIRS = [
  'resources/js/Pages',
  'resources/js/Components/Organisms',
  'resources/js/Components/Molecules',
];

const ALLOWLIST = new Set([
  // Componentes que IMPLEMENTAM a responsividade - usam <table> internamente:
  'resources/js/Components/Organisms/Table/ResponsiveTable.vue',
  'resources/js/Components/Molecules/Table/TableMobileCard.vue',
]);

// Componentes em pastas/com nomes Print sao views de impressao (PDF/print stylesheet) e usam <table> puro intencionalmente.
const PRINT_EXEMPT_PATTERNS = [
  /\/Print\//,
  /Print[A-Z][a-zA-Z]*\.vue$/,
  /Boletim[A-Z][a-zA-Z]*\.vue$/,
];

const violations = [];

function* walk(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      yield* walk(full);
    } else if (entry.isFile() && full.endsWith('.vue')) {
      yield full;
    }
  }
}

function checkFile(filePath) {
  const rel = path.relative(ROOT, filePath).replace(/\\/g, '/');
  if (ALLOWLIST.has(rel)) return;
  if (PRINT_EXEMPT_PATTERNS.some((p) => p.test(rel))) return;

  const content = fs.readFileSync(filePath, 'utf8');

  // Extrai apenas o <template>
  const templateMatch = content.match(/<template[\s\S]*?<\/template>/);
  if (!templateMatch) return;
  const template = templateMatch[0];

  // Regra 1: <table> sem estar dentro de <ResponsiveTable>
  if (/<table[\s>]/.test(template)) {
    const usesResponsiveTable = /<ResponsiveTable[\s>]/.test(template);
    if (!usesResponsiveTable) {
      violations.push({
        file: rel,
        rule: 'no-bare-table',
        message: '<table> usado sem <ResponsiveTable>. Veja resources/js/design-system/responsive-contract.md secao 3.',
      });
    }
  }

  // Regra 2: overflow-x-auto envolvendo <table> sem ResponsiveTable
  const overflowWrappingTable =
    /class="[^"]*overflow-x-auto[^"]*"[\s\S]{0,200}?<table/.test(template) &&
    !/<ResponsiveTable/.test(template);
  if (overflowWrappingTable) {
    violations.push({
      file: rel,
      rule: 'no-overflow-x-only',
      message: 'overflow-x-auto envolvendo <table> nao e estrategia mobile valida (corta colunas). Use <ResponsiveTable>.',
    });
  }
}

for (const dir of SCAN_DIRS) {
  const abs = path.join(ROOT, dir);
  if (!fs.existsSync(abs)) continue;
  for (const file of walk(abs)) {
    checkFile(file);
  }
}

// Baseline: violacoes pre-existentes (catalogadas em Fase 0) que serao migradas em Fase 8.
// Cada migracao de modulo na Fase 8 remove sua entrada daqui. Quando vazio, o script bloqueia 100%.
const BASELINE_FILE = path.join(ROOT, 'scripts/responsive-contract.baseline.json');
let baseline = [];
try {
  baseline = JSON.parse(fs.readFileSync(BASELINE_FILE, 'utf8'));
} catch {
  baseline = [];
}
const baselineSet = new Set(baseline.map((v) => `${v.rule}::${v.file}`));

const argv = process.argv.slice(2);
const updateBaseline = argv.includes('--update-baseline');

if (updateBaseline) {
  fs.writeFileSync(
    BASELINE_FILE,
    JSON.stringify(
      violations.map(({ file, rule }) => ({ file, rule })),
      null,
      2,
    ) + '\n',
  );
  console.log(`[responsive-contract] Baseline atualizado: ${violations.length} entradas em ${path.relative(ROOT, BASELINE_FILE)}`);
  process.exit(0);
}

const newViolations = violations.filter((v) => !baselineSet.has(`${v.rule}::${v.file}`));
const existing = violations.length - newViolations.length;

if (violations.length === 0) {
  console.log('[responsive-contract] OK - 0 violacoes encontradas.');
  process.exit(0);
}

console.log(`[responsive-contract] Pre-existentes (baseline): ${existing} | Novas: ${newViolations.length}`);

if (newViolations.length === 0) {
  console.log('[responsive-contract] OK - nenhuma violacao nova alem do baseline.');
  process.exit(0);
}

console.error(`\n[responsive-contract] FALHA - ${newViolations.length} nova(s) violacao(oes):\n`);
for (const v of newViolations) {
  console.error(`  [${v.rule}] ${v.file}`);
  console.error(`     -> ${v.message}\n`);
}
console.error('Documentacao: resources/js/design-system/responsive-contract.md');
console.error('Para atualizar o baseline (apos migrar modulo): node scripts/check-responsive-contract.js --update-baseline');
process.exit(1);
