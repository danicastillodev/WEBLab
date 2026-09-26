#!/usr/bin/env node
/**
 * Servidor MCP para desplegar WebLab en cPanel, sin SSH.
 *
 * Cómo funciona el circuito completo:
 *
 *   1. Se pushea a `main` en GitHub.
 *   2. GitHub Actions (.github/workflows/deploy.yml) corre composer install --no-dev
 *      y npm run build, y publica el resultado en la rama `deploy` (que ya trae
 *      vendor/ y public/build, porque el hosting compartido no tiene Node).
 *   3. cPanel (Git Version Control) tiene clonada esa rama. Este MCP le pide por
 *      HTTPS que haga el pull y que ejecute .cpanel.yml, que copia los archivos y
 *      corre artisan migrate + optimize.
 *
 * Credenciales: un solo token de API de cPanel (Security -> Manage API Tokens).
 * Para GitHub se reusa el git local, que ya está autenticado — no hace falta PAT.
 */

import { spawn } from "node:child_process";
import { readFileSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
    CallToolRequestSchema,
    ListToolsRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";

const PROJECT_ROOT = resolve(dirname(fileURLToPath(import.meta.url)), "../..");
const MAX_OUTPUT = 12000;

/* -------------------------------------------------------------- configuración */

function loadEnvFile(path) {
    let raw;
    try {
        raw = readFileSync(path, "utf8");
    } catch {
        return {};
    }

    const vars = {};
    for (const line of raw.split("\n")) {
        const match = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)$/);
        if (!match) continue;
        vars[match[1]] = match[2].trim().replace(/^["']|["']$/g, "");
    }
    return vars;
}

const fileEnv = loadEnvFile(resolve(PROJECT_ROOT, ".env.deploy"));

function env(key, fallback = undefined) {
    const value = process.env[key] ?? fileEnv[key];
    return value === undefined || value === "" ? fallback : value;
}

const config = {
    host: env("CPANEL_HOST"),
    port: env("CPANEL_PORT", "2083"),
    user: env("CPANEL_USER"),
    token: env("CPANEL_TOKEN"),
    repoRoot: env("CPANEL_REPO_ROOT"),
    appDir: env("CPANEL_APP_DIR", "weblab"),
    deployBranch: env("DEPLOY_BRANCH", "deploy"),
    sourceBranch: env("SOURCE_BRANCH", "main"),
    remote: env("GIT_REMOTE", "origin"),
    insecureTls: String(env("CPANEL_INSECURE_TLS", "false")).toLowerCase() === "true",
};

// Muchos hosts sirven :2083 con un certificado que no valida contra el dominio.
// Este proceso solo habla con cPanel, así que el impacto queda acotado.
if (config.insecureTls) {
    process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";
}

function missingConfig() {
    const requeridos = {
        CPANEL_HOST: config.host,
        CPANEL_USER: config.user,
        CPANEL_TOKEN: config.token,
        CPANEL_REPO_ROOT: config.repoRoot,
    };
    const faltantes = Object.keys(requeridos).filter((k) => !requeridos[k]);
    if (faltantes.length === 0) return null;

    return (
        `Falta configuración: ${faltantes.join(", ")}.\n\n` +
        "Copiá .env.deploy.example a .env.deploy en la raíz del proyecto y completá los valores."
    );
}

/* ---------------------------------------------------------------- utilidades */

function truncate(text) {
    if (text.length <= MAX_OUTPUT) return text;
    return `[...salida recortada, mostrando los últimos ${MAX_OUTPUT} caracteres...]\n${text.slice(-MAX_OUTPUT)}`;
}

function ok(text) {
    return { content: [{ type: "text", text: truncate(text) }] };
}

function fail(text) {
    return { content: [{ type: "text", text: truncate(text) }], isError: true };
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** git local, siempre en la raíz del proyecto. No lanza por exit code != 0. */
function git(args, { timeoutMs = 120000 } = {}) {
    return new Promise((resolvePromise) => {
        const child = spawn("git", args, { cwd: PROJECT_ROOT });
        let stdout = "";
        let stderr = "";

        const timer = setTimeout(() => child.kill("SIGKILL"), timeoutMs);
        child.stdout.on("data", (c) => (stdout += c));
        child.stderr.on("data", (c) => (stderr += c));
        child.on("error", (e) => {
            clearTimeout(timer);
            resolvePromise({ code: 1, stdout: "", stderr: e.message });
        });
        child.on("close", (code) => {
            clearTimeout(timer);
            resolvePromise({ code: code ?? 1, stdout: stdout.trim(), stderr: stderr.trim() });
        });
    });
}

async function gitOrThrow(args) {
    const { code, stdout, stderr } = await git(args);
    if (code !== 0) throw new Error(`git ${args.join(" ")} falló:\n${stderr || stdout}`);
    return stdout;
}

/**
 * Llama a la UAPI de cPanel. Devuelve { ok, data, errors, raw }.
 * La UAPI responde siempre 200 con { status: 0|1, data, errors }.
 */
async function uapi(module, func, params = {}, { timeoutMs = 120000 } = {}) {
    const query = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null) query.set(key, String(value));
    }

    const url = `https://${config.host}:${config.port}/execute/${module}/${func}?${query}`;
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);

    try {
        const response = await fetch(url, {
            headers: { Authorization: `cpanel ${config.user}:${config.token}` },
            signal: controller.signal,
        });

        const body = await response.text();

        if (!response.ok) {
            return {
                ok: false,
                errors: [`HTTP ${response.status} en ${module}::${func}`, body.slice(0, 500)],
                raw: body,
            };
        }

        let parsed;
        try {
            parsed = JSON.parse(body);
        } catch {
            // Si cPanel devuelve HTML acá, casi siempre es la pantalla de login:
            // token inválido, usuario equivocado o puerto que no es el de cPanel.
            return {
                ok: false,
                errors: [
                    `${module}::${func} no devolvió JSON. Suele ser un token inválido o un host/puerto incorrecto.`,
                    body.slice(0, 300),
                ],
                raw: body,
            };
        }

        return {
            ok: parsed.status === 1,
            data: parsed.data,
            errors: parsed.errors ?? [],
            messages: parsed.messages ?? [],
            raw: parsed,
        };
    } catch (error) {
        const motivo = error.name === "AbortError" ? "timeout" : error.message;
        return { ok: false, errors: [`No se pudo hablar con cPanel (${motivo})`], raw: null };
    } finally {
        clearTimeout(timer);
    }
}

function uapiError(result, contexto) {
    const detalle = (result.errors ?? []).join("\n") || "sin detalle";
    return `${contexto}:\n${detalle}`;
}

/* ------------------------------------------------------------- estado en git */

/** El commit de `main` del que salió un build: lo escribe el workflow como trailer. */
function sourceOf(mensaje) {
    const match = mensaje.match(/Source:\s*([0-9a-f]{7,40})/i);
    return match ? match[1] : null;
}

async function fetchRefs() {
    await git(["fetch", "--quiet", config.remote, config.sourceBranch, config.deployBranch]);
    // La rama deploy puede no existir todavía; en ese caso el fetch de arriba falla
    // entero, así que se reintenta solo con la rama fuente.
    await git(["fetch", "--quiet", config.remote, config.sourceBranch]);
}

async function deployBranchState() {
    const ref = `${config.remote}/${config.deployBranch}`;
    const { code, stdout } = await git(["log", "-1", "--format=%H%x00%s%x00%b", ref]);
    if (code !== 0) return null;

    const [sha, subject, body] = stdout.split("\0");
    return { sha, subject, source: sourceOf(body ?? "") };
}

/* ---------------------------------------------------- estado y deploy en cPanel */

async function cpanelRepo() {
    const result = await uapi("VersionControl", "retrieve", {
        repository_root: config.repoRoot,
    });
    if (!result.ok) return { error: uapiError(result, "No se pudo leer el repositorio en cPanel") };

    const repo = Array.isArray(result.data) ? result.data[0] : result.data;
    if (!repo) return { error: `cPanel no devolvió ningún repositorio en ${config.repoRoot}` };
    return { repo };
}

async function lastDeployment() {
    const result = await uapi("VersionControlDeployment", "retrieve", {
        repository_root: config.repoRoot,
    });
    if (!result.ok) return { error: uapiError(result, "No se pudo leer el historial de deploys") };

    const lista = Array.isArray(result.data) ? result.data : [result.data].filter(Boolean);
    return { deployment: lista[lista.length - 1] ?? null, todos: lista };
}

/** cPanel varía los nombres de campo entre versiones; se lee de forma defensiva. */
function deploymentState(deployment) {
    if (!deployment) return { estado: "sin deploys registrados" };

    const timestamps = deployment.timestamps ?? deployment;
    const sha = deployment.sha ?? deployment.head_sha ?? deployment.revision ?? "?";
    const log = deployment.log_path ?? deployment.log ?? null;

    let estado = "en curso";
    if (timestamps.failed || deployment.failed) estado = "FALLÓ";
    else if (timestamps.succeeded || deployment.succeeded) estado = "ok";
    else if (timestamps.canceled) estado = "cancelado";

    const terminado = estado !== "en curso" && estado !== "sin deploys registrados";
    return { estado, sha, log, terminado, timestamps };
}

/* -------------------------------------------------------------- herramientas */

async function deployStatus() {
    await fetchRefs();

    const lineas = [];
    const mainSha = await git(["rev-parse", `${config.remote}/${config.sourceBranch}`]);
    const mainSubject = await git([
        "log", "-1", "--format=%h %s", `${config.remote}/${config.sourceBranch}`,
    ]);
    const localSha = await git(["rev-parse", "HEAD"]);
    const sucio = await git(["status", "--porcelain"]);

    lineas.push(`GitHub · ${config.sourceBranch}: ${mainSubject.stdout || "?"}`);
    if (localSha.stdout !== mainSha.stdout) {
        lineas.push(`  [!] Tu HEAD local (${localSha.stdout.slice(0, 7)}) no coincide con ${config.remote}/${config.sourceBranch}: falta pushear.`);
    }
    if (sucio.stdout) {
        lineas.push(`  [!] Tenés ${sucio.stdout.split("\n").length} archivos sin commitear.`);
    }

    const build = await deployBranchState();
    if (!build) {
        lineas.push(`GitHub · ${config.deployBranch}: la rama no existe todavía (el workflow no corrió nunca).`);
    } else {
        const alDia = build.source && mainSha.stdout.startsWith(build.source);
        lineas.push(
            `GitHub · ${config.deployBranch}: ${build.sha.slice(0, 7)} — ${build.subject}` +
                `\n  compilado desde ${build.source?.slice(0, 7) ?? "?"}${alDia ? " (al día)" : " (ATRASADO respecto de " + config.sourceBranch + ")"}`
        );
    }

    lineas.push("");

    const { repo, error } = await cpanelRepo();
    if (error) {
        lineas.push(`cPanel: ${error}`);
    } else {
        lineas.push(`cPanel · repo: ${repo.repository_root ?? config.repoRoot}`);
        lineas.push(`cPanel · rama checkouteada: ${repo.branch ?? repo.active_branch ?? "?"}`);
        const desplegado = repo.last_deployment?.sha ?? repo.head_sha ?? null;
        if (desplegado) {
            const igual = build && build.sha.startsWith(desplegado.slice(0, 7));
            lineas.push(`cPanel · commit desplegado: ${desplegado.slice(0, 7)}${igual ? " (coincide con el último build)" : " (hay un build más nuevo sin desplegar)"}`);
        }
    }

    const { deployment, error: errorDeploy } = await lastDeployment();
    if (errorDeploy) {
        lineas.push(`cPanel · último deploy: ${errorDeploy}`);
    } else {
        const estado = deploymentState(deployment);
        lineas.push(`cPanel · último deploy: ${estado.estado}${estado.sha ? ` (${String(estado.sha).slice(0, 7)})` : ""}`);
        if (estado.log) lineas.push(`  log: ${estado.log}`);
    }

    return ok(lineas.join("\n"));
}

/** Espera a que la rama deploy contenga el build de `targetSha`. */
async function waitForBuild(targetSha, minutos) {
    const limite = Date.now() + minutos * 60 * 1000;
    let ultimo = null;

    while (Date.now() < limite) {
        const build = await deployBranchState();
        ultimo = build;
        if (build?.source && targetSha.startsWith(build.source)) {
            return { listo: true, build };
        }
        await sleep(20000);
        await fetchRefs();
    }

    return { listo: false, build: ultimo };
}

async function deploy(args) {
    const esperar = args.wait_minutes === undefined ? 12 : Number(args.wait_minutes);
    const soloDeploy = args.skip_build_wait === true;

    await fetchRefs();

    const refPedida = args.ref || `${config.remote}/${config.sourceBranch}`;
    const target = await git(["rev-parse", `${refPedida}^{commit}`]);
    if (target.code !== 0) {
        return fail(`No pude resolver la ref "${refPedida}" en el repo local:\n${target.stderr}`);
    }
    const targetSha = target.stdout;

    const salida = [`Objetivo: ${targetSha.slice(0, 7)} (${refPedida})`];

    if (!soloDeploy) {
        let build = await deployBranchState();
        if (!build?.source || !targetSha.startsWith(build.source)) {
            salida.push(`Esperando a que GitHub Actions publique el build (hasta ${esperar} min)...`);
            const resultado = await waitForBuild(targetSha, esperar);
            build = resultado.build;

            if (!resultado.listo) {
                return fail(
                    salida.join("\n") +
                        `\n\nSe agotó la espera. La rama ${config.deployBranch} sigue en ` +
                        `${build?.sha?.slice(0, 7) ?? "(inexistente)"} (build de ${build?.source?.slice(0, 7) ?? "?"}).\n` +
                        "Revisá el workflow en GitHub → Actions. Nada se tocó en el servidor."
                );
            }
        }
        salida.push(`Build listo: ${build.sha.slice(0, 7)} — ${build.subject}`);
    }

    salida.push("Pidiéndole a cPanel que traiga los cambios...");
    const update = await uapi("VersionControl", "update", {
        repository_root: config.repoRoot,
        branch: config.deployBranch,
    });
    if (!update.ok) {
        return fail(salida.join("\n") + "\n\n" + uapiError(update, "Falló el pull en cPanel"));
    }

    salida.push("Ejecutando .cpanel.yml...");
    const create = await uapi("VersionControlDeployment", "create", {
        repository_root: config.repoRoot,
    });
    if (!create.ok) {
        return fail(salida.join("\n") + "\n\n" + uapiError(create, "cPanel rechazó el deploy"));
    }

    // El deploy es asincrónico: se sondea el historial hasta que termine.
    const limite = Date.now() + 15 * 60 * 1000;
    let estado = { estado: "en curso", terminado: false };

    while (Date.now() < limite) {
        await sleep(10000);
        const { deployment, error } = await lastDeployment();
        if (error) {
            salida.push(`(no pude leer el estado: ${error})`);
            break;
        }
        estado = deploymentState(deployment);
        if (estado.terminado) break;
    }

    salida.push("");
    salida.push(`Resultado: ${estado.estado}`);
    if (estado.log) salida.push(`Log del deploy: ${estado.log}  (leelo con la herramienta deploy_log)`);

    if (estado.estado === "FALLÓ") {
        return fail(
            salida.join("\n") +
                "\n\nEl deploy falló del lado de cPanel. Usá deploy_log para ver en qué tarea de .cpanel.yml se cortó."
        );
    }
    if (!estado.terminado) {
        return ok(salida.join("\n") + "\n\nSeguía corriendo cuando dejé de sondear. Revisá con deploy_status.");
    }

    return ok(salida.join("\n"));
}

async function rollback(args) {
    await fetchRefs();

    const ref = `${config.remote}/${config.deployBranch}`;
    const pasos = args.steps === undefined ? 1 : Number(args.steps);
    const destino = args.commit || `${ref}~${pasos}`;

    const tree = await git(["rev-parse", `${destino}^{tree}`]);
    if (tree.code !== 0) {
        const historial = await git(["log", "--oneline", "-10", ref]);
        return fail(
            `No pude resolver "${destino}" en la rama ${config.deployBranch}.\n\nÚltimos builds:\n${historial.stdout}`
        );
    }

    const head = await gitOrThrow(["rev-parse", ref]);
    const descripcion = await gitOrThrow(["log", "-1", "--format=%h %s", destino]);

    // Se crea un commit nuevo cuyo árbol es el del build viejo, encima del head
    // actual. Así la rama deploy sigue siendo aditiva y el pull de cPanel no se
    // rompe (un force-push sí lo rompería).
    const nuevo = await gitOrThrow([
        "commit-tree", tree.stdout,
        "-p", head,
        "-m", `Rollback a ${descripcion}`,
    ]);

    const push = await git(["push", config.remote, `${nuevo}:refs/heads/${config.deployBranch}`]);
    if (push.code !== 0) {
        return fail(`No pude pushear el rollback:\n${push.stderr || push.stdout}`);
    }

    await fetchRefs();
    const resultado = await deploy({ skip_build_wait: true });

    const encabezado =
        `Rollback al árbol de ${descripcion}\n` +
        `Commit de rollback: ${nuevo.slice(0, 7)}\n\n` +
        "OJO: las migraciones NO se revierten. Si el deploy que estás deshaciendo migró la base,\n" +
        "hay que arreglarla a mano (phpMyAdmin) — sin shell no hay migrate:rollback.\n\n";

    return {
        ...resultado,
        content: [{ type: "text", text: truncate(encabezado + resultado.content[0].text) }],
    };
}

/** Lee un archivo del servidor por UAPI y devuelve las últimas N líneas. */
async function leerArchivo(dir, file, lines, filtro) {
    const result = await uapi("Fileman", "get_file_content", { dir, file });
    if (!result.ok) {
        return { error: uapiError(result, `No pude leer ${dir}/${file}`) };
    }

    const contenido = result.data?.content ?? result.data ?? "";
    let renglones = String(contenido).split("\n");
    if (filtro) {
        const needle = filtro.toLowerCase();
        renglones = renglones.filter((l) => l.toLowerCase().includes(needle));
    }
    return { texto: renglones.slice(-lines).join("\n") };
}

async function remoteLogs(args) {
    const lines = Math.min(Math.max(parseInt(args.lines ?? 100, 10) || 100, 1), 2000);
    const relativo = args.file || "storage/logs/laravel.log";

    if (relativo.startsWith("/") || relativo.includes("..")) {
        return fail("file debe ser una ruta relativa dentro de la app, sin '..'.");
    }

    const partes = relativo.split("/");
    const archivo = partes.pop();
    const dir = ["", config.appDir, ...partes].filter(Boolean).join("/");

    const { texto, error } = await leerArchivo(`~/${dir}`, archivo, lines, args.filter);
    if (error) return fail(error);

    return ok(`== ~/${dir}/${archivo} (últimas ${lines} líneas) ==\n${texto}`);
}

async function deployLog(args) {
    const lines = Math.min(Math.max(parseInt(args.lines ?? 200, 10) || 200, 1), 2000);

    const { deployment, error } = await lastDeployment();
    if (error) return fail(error);

    const estado = deploymentState(deployment);
    if (!estado.log) {
        return ok(
            `Último deploy: ${estado.estado}\n\ncPanel no informó una ruta de log. Respuesta cruda:\n` +
                JSON.stringify(deployment, null, 2)
        );
    }

    const ruta = String(estado.log);
    const idx = ruta.lastIndexOf("/");
    const dir = idx > 0 ? ruta.slice(0, idx) : "~";
    const archivo = idx > 0 ? ruta.slice(idx + 1) : ruta;

    const { texto, error: errorLectura } = await leerArchivo(dir, archivo, lines, args.filter);
    if (errorLectura) {
        return fail(`Último deploy: ${estado.estado}\nLog: ${ruta}\n\n${errorLectura}`);
    }

    return ok(`Último deploy: ${estado.estado}\nLog: ${ruta}\n\n${texto}`);
}

/* ------------------------------------------------------------------ servidor */

const TOOLS = [
    {
        name: "deploy_status",
        description:
            "Solo lectura. Compara los tres puntos de la cadena: qué hay en main en GitHub, qué build publicó GitHub Actions en la rama deploy, y qué commit tiene desplegado cPanel. Avisa si te falta pushear o si hay un build sin desplegar. Usalo antes de cualquier deploy.",
        inputSchema: { type: "object", properties: {}, additionalProperties: false },
        annotations: { readOnlyHint: true, openWorldHint: true },
        handler: deployStatus,
    },
    {
        name: "deploy",
        description:
            "Despliega en cPanel: espera a que GitHub Actions publique el build de la rama deploy, le pide a cPanel que haga el pull (VersionControl::update) y que ejecute .cpanel.yml (VersionControlDeployment::create), y sondea hasta que termina. Las migraciones y el optimize corren dentro de .cpanel.yml. No hace falta SSH.",
        inputSchema: {
            type: "object",
            properties: {
                ref: {
                    type: "string",
                    description: `Commit o rama de origen a desplegar. Por defecto ${config.remote}/${config.sourceBranch}.`,
                },
                wait_minutes: {
                    type: "number",
                    description: "Minutos a esperar el build de GitHub Actions. Por defecto 12.",
                },
                skip_build_wait: {
                    type: "boolean",
                    description: "No esperar build: desplegar lo que ya esté publicado en la rama deploy.",
                },
            },
            additionalProperties: false,
        },
        annotations: { readOnlyHint: false, destructiveHint: true, idempotentHint: false, openWorldHint: true },
        handler: deploy,
    },
    {
        name: "rollback",
        description:
            "Vuelve a un build anterior. Publica en la rama deploy un commit nuevo con el árbol del build viejo (aditivo, sin force-push, para no romper el pull de cPanel) y lo despliega. NO revierte migraciones.",
        inputSchema: {
            type: "object",
            properties: {
                steps: { type: "integer", description: "Cuántos builds retroceder. Por defecto 1." },
                commit: { type: "string", description: "Commit exacto de la rama deploy al que volver. Tiene prioridad sobre steps." },
            },
            additionalProperties: false,
        },
        annotations: { readOnlyHint: false, destructiveHint: true, openWorldHint: true },
        handler: rollback,
    },
    {
        name: "remote_logs",
        description:
            "Lee un archivo de la app en el servidor por la UAPI de cPanel (Fileman), por defecto storage/logs/laravel.log, con filtro opcional. Solo lectura.",
        inputSchema: {
            type: "object",
            properties: {
                lines: { type: "integer", description: "Últimas N líneas (1-2000). Por defecto 100." },
                filter: { type: "string", description: "Mostrar solo las líneas que contengan este texto." },
                file: { type: "string", description: "Ruta relativa a la app. Por defecto storage/logs/laravel.log." },
            },
            additionalProperties: false,
        },
        annotations: { readOnlyHint: true, openWorldHint: true },
        handler: remoteLogs,
    },
    {
        name: "deploy_log",
        description:
            "Muestra el log del último deploy de cPanel: es la única forma de ver en qué tarea de .cpanel.yml se cortó (migrate, rsync, rutas de PHP). Solo lectura.",
        inputSchema: {
            type: "object",
            properties: {
                lines: { type: "integer", description: "Últimas N líneas. Por defecto 200." },
                filter: { type: "string", description: "Filtrar por texto." },
            },
            additionalProperties: false,
        },
        annotations: { readOnlyHint: true, openWorldHint: true },
        handler: deployLog,
    },
];

const server = new Server(
    { name: "weblab-deploy", version: "2.0.0" },
    { capabilities: { tools: {} } }
);

server.setRequestHandler(ListToolsRequestSchema, async () => ({
    tools: TOOLS.map(({ handler, ...tool }) => tool),
}));

server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const tool = TOOLS.find((t) => t.name === request.params.name);
    if (!tool) return fail(`Herramienta desconocida: ${request.params.name}`);

    const problema = missingConfig();
    if (problema) return fail(problema);

    try {
        return await tool.handler(request.params.arguments ?? {});
    } catch (error) {
        return fail(`Error en ${tool.name}: ${error.message}`);
    }
});

await server.connect(new StdioServerTransport());
