import { execFileSync, spawnSync } from 'node:child_process';

// Inspect the Git index so partially staged files are checked exactly as they will be committed.
const ignored = execFileSync(
    'git',
    ['ls-files', '--cached', '--ignored', '--exclude-standard'],
    { encoding: 'utf8' }
);

if (ignored) {
    console.error(
        'Remove these ignored files from the Git index:\n' + ignored.trimEnd()
    );
    process.exitCode = 1;
}

execFileSync(
    'git',
    [
        'ls-files',
        '--error-unmatch',
        'composer.lock',
        'package-lock.json',
        '.env.example',
    ],
    {
        stdio: ['ignore', 'ignore', 'inherit'],
    }
);

const credentialPatterns = [
    '-----BEGIN (RSA |DSA |EC |OPENSSH |ENCRYPTED )?PRIVATE KEY-----',
    'gh[pousr]_[A-Za-z0-9]{34,}|github_pat_[A-Za-z0-9_]{60,}',
    '[1-9][0-9]{6,10}:[A-Za-z0-9_-]{33,}',
    'AIza[A-Za-z0-9_-]{35}|(AKIA|ASIA)[A-Z0-9]{16}',
    `APP_KEY=["']?base64:[A-Za-z0-9+/]{43}=`,
];
const matches = spawnSync(
    'git',
    [
        'grep',
        '--cached',
        '-I',
        '--files-with-matches',
        '--extended-regexp',
        ...credentialPatterns.flatMap((pattern) => ['-e', pattern]),
        '--',
    ],
    { encoding: 'utf8' }
);

if (matches.error) {
    throw matches.error;
}

if (matches.status === 0) {
    console.error(
        'Possible credentials in staged files:\n' + matches.stdout.trimEnd()
    );
    process.exitCode = 1;
} else if (matches.status !== 1) {
    throw new Error(matches.stderr || 'git grep failed');
}

if (!process.exitCode) {
    console.log('Git index passed repository checks.');
}
