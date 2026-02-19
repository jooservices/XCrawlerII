import { execSync } from 'child_process';
import path from 'path';

const PROJECT_ROOT = path.resolve(__dirname, '..', '..');
const EXEC_OPTIONS = {
    cwd: PROJECT_ROOT,
    encoding: 'utf-8' as const,
    timeout: 60_000,
};

interface CommandResult {
    exitCode: number;
    stdout: string;
    stderr: string;
}

function run(command: string): CommandResult {
    try {
        const stdout = execSync(command, {
            ...EXEC_OPTIONS,
            stdio: ['pipe', 'pipe', 'pipe'],
        });
        return { exitCode: 0, stdout: stdout.toString(), stderr: '' };
    } catch (error: any) {
        return {
            exitCode: error.status ?? 1,
            stdout: error.stdout?.toString() ?? '',
            stderr: error.stderr?.toString() ?? '',
        };
    }
}

// ── analytics:flush ────────────────────────────────────────────────────

export interface FlushResult extends CommandResult {
    keysProcessed: number;
    errors: number;
}

export function runFlush(): FlushResult {
    const result = run('php artisan analytics:flush');
    // Parse: "Flushed 5 keys, 0 errors."
    const match = result.stdout.match(/Flushed (\d+) keys?, (\d+) errors?/i);
    return {
        ...result,
        keysProcessed: match ? parseInt(match[1], 10) : 0,
        errors: match ? parseInt(match[2], 10) : 0,
    };
}

// ── analytics:parity-check ─────────────────────────────────────────────

export interface ParityResult extends CommandResult {
    checked: number;
    mismatches: number;
    json: any | null;
}

export function runParityCheck(options?: { limit?: number }): ParityResult {
    const limit = options?.limit ?? 100;
    const result = run(`php artisan analytics:parity-check --limit=${limit} --print-json`);

    // Parse: "Checked: 50, Mismatches: 0"
    const statsMatch = result.stdout.match(/Checked:\s*(\d+),\s*Mismatches:\s*(\d+)/i);

    // Extract JSON line (last non-empty line that starts with { )
    let json: any = null;
    const lines = result.stdout.split('\n').filter((l) => l.trim());
    for (const line of lines) {
        const trimmed = line.trim();
        if (trimmed.startsWith('{')) {
            try {
                json = JSON.parse(trimmed);
            } catch {
                // not valid JSON
            }
        }
    }

    return {
        ...result,
        checked: statsMatch ? parseInt(statsMatch[1], 10) : 0,
        mismatches: statsMatch ? parseInt(statsMatch[2], 10) : 0,
        json,
    };
}

// ── analytics:report:generate ──────────────────────────────────────────

export interface ReportGenerateResult extends CommandResult {
    outputDir: string;
}

export function runReportGenerate(options?: {
    days?: number;
    limit?: number;
    dir?: string;
    archive?: boolean;
    rollback?: boolean;
}): ReportGenerateResult {
    const days = options?.days ?? 3;
    const limit = options?.limit ?? 200;
    const dir = options?.dir ?? '/tmp/pw-test-evidence';
    const archive = options?.archive !== false ? '--archive' : '';
    const rollback = options?.rollback !== false ? '--rollback' : '';

    const result = run(
        `php artisan analytics:report:generate --days=${days} --limit=${limit} --dir=${dir} ${archive} ${rollback}`.trim(),
    );

    return { ...result, outputDir: dir };
}

// ── analytics:report:verify ────────────────────────────────────────────

export interface ReportVerifyResult extends CommandResult {
    verified: number;
    invalid: number;
}

export function runReportVerify(options: {
    dir?: string;
    archive?: string;
    strict?: boolean;
}): ReportVerifyResult {
    let cmd = 'php artisan analytics:report:verify';
    if (options.archive) {
        cmd += ` --archive=${options.archive}`;
    } else if (options.dir) {
        cmd += ` --dir=${options.dir}`;
    }
    if (options.strict !== false) {
        cmd += ' --strict';
    }

    const result = run(cmd);

    // Parse: "Verified 3 artifact(s), invalid 0."
    const match = result.stdout.match(/Verified (\d+) artifact\(s\), invalid (\d+)/i);
    return {
        ...result,
        verified: match ? parseInt(match[1], 10) : 0,
        invalid: match ? parseInt(match[2], 10) : 0,
    };
}

// ── schedule:run ───────────────────────────────────────────────────────

export function runScheduleRun(): CommandResult {
    return run('php artisan schedule:run');
}

// ── Rate limiter reset ─────────────────────────────────────────────────

export function resetRateLimiter(): void {
    // Clear the analytics throttle rate limiter so tests don't carry over 429s
    // We use artisan tinker to call RateLimiter::clear for all keys
    // Note: Do NOT use cache:clear as it destroys CSRF tokens and breaks session
    run(`php artisan tinker --execute="
        \\$redis = app('redis');
        \\$keys = \\$redis->keys('laravel-database-*:throttle:*');
        if (count(\\$keys) > 0) { \\$redis->connection()->del(...\\$keys); }
        echo 'Rate limiter cleared: ' . count(\\$keys) . ' keys';
    " 2>/dev/null`);
}
