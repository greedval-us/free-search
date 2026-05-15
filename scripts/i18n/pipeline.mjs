import fs from 'node:fs/promises';
import path from 'node:path';

const projectRoot = process.cwd();
const localesDir = path.join(projectRoot, 'resources', 'js', 'locales');
const sourceDir = path.join(projectRoot, 'resources', 'js');
const localeFiles = ['en.json', 'ru.json'];
const sourceExtensions = new Set(['.vue', '.ts', '.tsx', '.js']);
const rawTextLineIgnoreToken = 'i18n-ignore-line';
const mode = process.argv.includes('--strict') ? 'strict' : 'relaxed';

const rawTextAllowList = new Set([
  'Uraboros',
  'durov',
  'HSTS',
  'CSP',
  'DNS',
  'TXT',
  'CAA',
  'MX',
  'NS',
  'SPF',
  'DMARC',
  'DNSSEC',
  'WHOIS',
  'HTTP',
  'HTTPS',
  'URL',
  'IP',
  'ID',
  'JSON',
  'CSV',
  'PDF',
  'DOC',
  'DOCX',
  'XLS',
  'XLSX',
  'PPT',
  'PPTX',
  'OK',
  'N/A',
  'breadcrumb',
  'Loading',
]);

const readJsonFile = async (filePath) => {
  const content = await fs.readFile(filePath, 'utf8');
  return JSON.parse(content);
};

const flattenObject = (obj, prefix = '', acc = new Map()) => {
  if (typeof obj !== 'object' || obj === null || Array.isArray(obj)) {
    acc.set(prefix, obj);
    return acc;
  }

  for (const [key, value] of Object.entries(obj)) {
    const fullKey = prefix ? `${prefix}.${key}` : key;
    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
      flattenObject(value, fullKey, acc);
    } else {
      acc.set(fullKey, value);
    }
  }

  return acc;
};

const listFilesRecursively = async (dir) => {
  const entries = await fs.readdir(dir, { withFileTypes: true });
  const results = [];

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      if (entry.name === 'locales') {
        continue;
      }

      results.push(...(await listFilesRecursively(fullPath)));
      continue;
    }

    if (sourceExtensions.has(path.extname(entry.name))) {
      results.push(fullPath);
    }
  }

  return results;
};

const findUsedI18nKeys = (content) => {
  const keys = [];
  const regex = /(?:\bt|\$t)\(\s*['"]([A-Za-z0-9_.-]+)['"]\s*[\),]/g;

  let match;
  while ((match = regex.exec(content)) !== null) {
    keys.push(match[1]);
  }

  return keys;
};

const isNoiseToken = (value) => {
  if (!value) {
    return true;
  }

  const normalized = value.trim();
  if (!normalized) {
    return true;
  }

  if (rawTextAllowList.has(normalized)) {
    return true;
  }

  if (normalized.startsWith('{') || normalized.endsWith('}')) {
    return true;
  }

  if (/^(?:v-|:|@|#|\$|\.)/.test(normalized)) {
    return true;
  }

  if (/^[a-z0-9_.-]+$/i.test(normalized) && (normalized.includes('.') || normalized.includes('-') || normalized.includes('_'))) {
    return true;
  }

  if (/^(?:https?:\/\/|mailto:|tel:|\/)/i.test(normalized)) {
    return true;
  }

  if (/^(?:true|false|null|undefined)$/i.test(normalized)) {
    return true;
  }

  if (/^(?:[a-z_$][a-z0-9_$]*)(?:\.[a-z_$][a-z0-9_$]*)+$/i.test(normalized)) {
    return true;
  }

  if (/^(?:[A-Za-z0-9]+-){1,}[A-Za-z0-9-]+$/.test(normalized)) {
    return true;
  }

  return false;
};

const findTemplateRawTextViolations = (filePath, content) => {
  const violations = [];
  const templateMatch = content.match(/<template[\s\S]*?<\/template>/i);

  if (!templateMatch) {
    return violations;
  }

  const templateContent = templateMatch[0];
  const lines = templateContent.split(/\r?\n/);
  const textPattern = />[^<]*\p{L}[^<]*</gu;
  const mustLocalizeAttrPattern = /(?<![:@])\b(?:placeholder|title|aria-label|alt)\s*=\s*"([^"]*\p{L}[^"]*)"/gu;

  for (let lineIndex = 0; lineIndex < lines.length; lineIndex += 1) {
    const line = lines[lineIndex];

    if (
      !line ||
      line.includes(rawTextLineIgnoreToken) ||
      line.trim().startsWith('<!--') ||
      line.includes('{{') ||
      line.includes("t('") ||
      line.includes('t("') ||
      line.includes('t(`') ||
      line.includes(':placeholder=') ||
      line.includes(':title=') ||
      line.includes(':aria-label=') ||
      line.includes(':alt=')
    ) {
      continue;
    }

    let textMatch;
    while ((textMatch = textPattern.exec(line)) !== null) {
      const raw = textMatch[0].slice(1, -1).trim();

      if (isNoiseToken(raw)) {
        continue;
      }

      if (/^[0-9\s:;,.+/%()[\]{}\-_=*&|!?\'"`~<>]+$/.test(raw)) {
        continue;
      }

      if (/^(?:HTTP|HTTPS|DNS|TXT|CAA|MX|NS|SPF|DMARC|DNSSEC|WHOIS|HSTS|CSP|KB|MB|URL|ID|IP|OK|N\/A)$/i.test(raw)) {
        continue;
      }

      violations.push({
        file: filePath,
        line: lineIndex + 1,
        message: `Raw template text: "${raw}"`,
      });
    }

    let attrMatch;
    while ((attrMatch = mustLocalizeAttrPattern.exec(line)) !== null) {
      const attrValue = attrMatch[1].trim();

      if (isNoiseToken(attrValue)) {
        continue;
      }

      if (
        attrValue.includes('{{') ||
        attrValue.includes('}}') ||
        attrValue.includes('?') ||
        attrValue.includes(':') ||
        attrValue.includes('`') ||
        attrValue.includes('${')
      ) {
        continue;
      }

      violations.push({
        file: filePath,
        line: lineIndex + 1,
        message: `Raw localizable attribute text: "${attrValue}"`,
      });
    }
  }

  return violations;
};

const typeLabel = (value) => {
  if (Array.isArray(value)) {
    return 'array';
  }

  if (value === null) {
    return 'null';
  }

  return typeof value;
};

const run = async () => {
  const localeEntries = [];

  for (const localeFile of localeFiles) {
    const localePath = path.join(localesDir, localeFile);
    const localeName = localeFile.replace('.json', '');
    const parsed = await readJsonFile(localePath);
    const flattened = flattenObject(parsed);

    localeEntries.push({
      locale: localeName,
      path: localePath,
      parsed,
      flattened,
    });
  }

  const [baseLocale, ...otherLocales] = localeEntries;
  const issues = [];

  for (const locale of otherLocales) {
    for (const key of baseLocale.flattened.keys()) {
      if (!locale.flattened.has(key)) {
        issues.push(`[locale-missing] ${locale.locale} is missing key "${key}"`);
      }
    }

    if (mode === 'strict') {
      for (const key of locale.flattened.keys()) {
        if (!baseLocale.flattened.has(key)) {
          issues.push(`[locale-extra] ${locale.locale} has extra key "${key}"`);
        }
      }
    }

    for (const [key, value] of baseLocale.flattened.entries()) {
      if (!locale.flattened.has(key)) {
        continue;
      }

      const otherValue = locale.flattened.get(key);
      const baseType = typeLabel(value);
      const otherType = typeLabel(otherValue);

      if (baseType !== otherType) {
        issues.push(
          `[locale-type] key "${key}" has type "${baseType}" in ${baseLocale.locale} and "${otherType}" in ${locale.locale}`,
        );
      }
    }
  }

  const sourceFiles = await listFilesRecursively(sourceDir);
  const knownKeys = new Set(localeEntries.flatMap((entry) => [...entry.flattened.keys()]));

  for (const filePath of sourceFiles) {
    const relativePath = path.relative(projectRoot, filePath);
    const content = await fs.readFile(filePath, 'utf8');

    const usedKeys = findUsedI18nKeys(content);
    for (const key of usedKeys) {
      if (!knownKeys.has(key)) {
        issues.push(`[i18n-key-missing] ${relativePath}: missing key "${key}" in locales`);
      }
    }

    if (filePath.endsWith('.vue')) {
      const rawTextViolations = findTemplateRawTextViolations(relativePath, content);

      for (const violation of rawTextViolations) {
        issues.push(
          `[raw-text] ${violation.file}:${violation.line} ${violation.message} (use t('...') or add ${rawTextLineIgnoreToken})`,
        );
      }
    }
  }

  if (issues.length > 0) {
    console.error('\n[i18n-pipeline] Failed with issues:\n');
    for (const issue of issues) {
      console.error(`- ${issue}`);
    }
    console.error(`\nTotal issues: ${issues.length}\n`);
    process.exit(1);
  }

  console.log(`[i18n-pipeline] OK (mode: ${mode})`);
};

run().catch((error) => {
  console.error('[i18n-pipeline] Fatal error:', error);
  process.exit(1);
});
