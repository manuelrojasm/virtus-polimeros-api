/**
 * Generador del Manual de Codificación BioPlastic (v4)
 * Lee el código fuente directamente de los repos backend (CodeIgniter4) y
 * frontend (React) montados en este sandbox y construye un .docx con
 * explicación breve + código fuente (con resaltado tipo editor), backend
 * primero y luego frontend. Incluye además los archivos de configuración
 * y dependencias "tocados" de cada framework.
 */
const fs = require("fs");
const path = require("path");
const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  ShadingType, BorderStyle, PageBreak, TableOfContents,
} = require("/usr/local/lib/node_modules_global/lib/node_modules/docx");

const API_BASE = "/sessions/fervent-dazzling-faraday/mnt/virtus-polimeros-api";
const APP_BASE = "/sessions/fervent-dazzling-faraday/mnt/virtus-polimeros-app";

const FONT = "Arial";
const MONO = "Consolas";

// ---------- estilo "editor de código" ----------
const EDITOR_BORDER = { style: BorderStyle.SINGLE, size: 6, color: "455A64" };
const HEADER_FILL = "37474F";
const CODE_FILL = "FAFAFA";
const COLOR_DEFAULT = "263238";
const COLOR_KEYWORD = "0000FF";
const COLOR_STRING = "A31515";
const COLOR_COMMENT = "008000";
const COLOR_NUMBER = "098658";
const COLOR_GUTTER = "9AA0A6";
const COLOR_GUTTER_BAR = "CFD8DC";

const KEYWORDS = {
  php: ["function", "class", "public", "private", "protected", "return", "if", "else", "elseif", "foreach", "while", "for", "const", "new", "array", "use", "namespace", "echo", "extends", "implements", "try", "catch", "finally", "static", "abstract", "interface", "trait", "throw", "switch", "case", "break", "continue", "default", "null", "true", "false", "this", "global", "require", "require_once", "include", "print", "instanceof", "as", "match", "fn"],
  js: ["function", "class", "const", "let", "var", "return", "if", "else", "foreach", "while", "for", "new", "import", "export", "default", "from", "extends", "implements", "try", "catch", "finally", "static", "throw", "switch", "case", "break", "continue", "null", "true", "false", "this", "async", "await", "yield", "typeof", "instanceof", "of", "in", "super"],
  css: [],
  json: [],
  apache: [],
  plain: [],
};

function langFor(relPath) {
  const ext = path.extname(relPath).toLowerCase();
  if (ext === ".php") return "php";
  if (ext === ".js" || ext === ".jsx") return "js";
  if (ext === ".css") return "css";
  if (ext === ".json") return "json";
  if (relPath.toLowerCase().endsWith(".htaccess")) return "apache";
  return "plain";
}
function isFullLineComment(line, lang) {
  const t = line.trim();
  if (!t) return false;
  if (lang === "php" || lang === "js") return t.startsWith("//") || t.startsWith("/*") || t.startsWith("*") || t.startsWith("*/");
  if (lang === "apache") return t.startsWith("#");
  return false;
}
function tokenizeLine(line, lang) {
  const kw = KEYWORDS[lang] || [];
  const kwPattern = kw.length ? `|\\b(?:${kw.join("|")})\\b` : "";
  const pattern = new RegExp("(`(?:\\\\.|[^`\\\\])*`|\"(?:\\\\.|[^\"\\\\])*\"|'(?:\\\\.|[^'\\\\])*'" + kwPattern + "|\\b\\d+(?:\\.\\d+)?\\b)", "g");
  const runs = [];
  let lastIndex = 0;
  let m;
  while ((m = pattern.exec(line)) !== null) {
    if (m.index > lastIndex) {
      runs.push(new TextRun({ text: line.slice(lastIndex, m.index), font: MONO, size: 16, color: COLOR_DEFAULT }));
    }
    const tok = m[0];
    let color = COLOR_DEFAULT;
    if (/^['"`]/.test(tok)) color = COLOR_STRING;
    else if (/^\d/.test(tok)) color = COLOR_NUMBER;
    else color = COLOR_KEYWORD;
    runs.push(new TextRun({ text: tok, font: MONO, size: 16, color, bold: color === COLOR_KEYWORD }));
    lastIndex = m.index + tok.length;
  }
  if (lastIndex < line.length) {
    runs.push(new TextRun({ text: line.slice(lastIndex), font: MONO, size: 16, color: COLOR_DEFAULT }));
  }
  if (runs.length === 0) runs.push(new TextRun({ text: " ", font: MONO, size: 16 }));
  return runs;
}

// ---------- helpers de documento ----------
function h1(text, opts = {}) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_1,
    pageBreakBefore: opts.pageBreakBefore !== false,
    children: [new TextRun({ text, font: FONT })],
  });
}
function h2(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_2,
    spacing: { before: 300, after: 120 },
    children: [new TextRun({ text, font: FONT })],
  });
}
function h3(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_3,
    spacing: { before: 240, after: 80 },
    children: [new TextRun({ text, font: FONT })],
  });
}
function p(text, opts = {}) {
  return new Paragraph({
    spacing: { after: 160 },
    children: [new TextRun({ text, font: FONT, italics: !!opts.italics, size: opts.size })],
  });
}

function codeBlock(absPath, relPath) {
  let content;
  try {
    content = fs.readFileSync(absPath, "utf8");
  } catch (e) {
    return [p(`[No se pudo leer el archivo: ${absPath}]`, { italics: true })];
  }
  const lang = langFor(relPath);
  const lines = content.replace(/\r\n/g, "\n").split("\n");
  if (lines.length && lines[lines.length - 1] === "") lines.pop();
  const gutterWidth = Math.max(2, String(lines.length).length);

  const out = [];
  // Barra de cabecera tipo editor con el nombre del archivo
  out.push(new Paragraph({
    spacing: { after: 0, line: 260, lineRule: "auto" },
    shading: { type: ShadingType.CLEAR, fill: HEADER_FILL, color: "auto" },
    border: { top: EDITOR_BORDER, left: EDITOR_BORDER, right: EDITOR_BORDER },
    children: [new TextRun({ text: `  ${relPath}`, font: MONO, size: 17, color: "FFFFFF", bold: true })],
  }));

  lines.forEach((line, idx) => {
    const lineNum = String(idx + 1).padStart(gutterWidth, " ");
    let runs = [
      new TextRun({ text: `  ${lineNum} `, font: MONO, size: 16, color: COLOR_GUTTER }),
      new TextRun({ text: "│ ", font: MONO, size: 16, color: COLOR_GUTTER_BAR }),
    ];
    if (!line.trim()) {
      runs.push(new TextRun({ text: " ", font: MONO, size: 16 }));
    } else if (isFullLineComment(line, lang)) {
      runs.push(new TextRun({ text: line, font: MONO, size: 16, color: COLOR_COMMENT, italics: true }));
    } else {
      runs = runs.concat(tokenizeLine(line, lang));
    }
    out.push(new Paragraph({
      spacing: { after: 0, line: 220, lineRule: "auto" },
      shading: { type: ShadingType.CLEAR, fill: CODE_FILL, color: "auto" },
      border: { left: EDITOR_BORDER, right: EDITOR_BORDER },
      children: runs,
    }));
  });
  // Línea de cierre del recuadro (borde "top" hace de borde inferior visual del bloque)
  out.push(new Paragraph({
    spacing: { after: 0, line: 40, lineRule: "auto" },
    shading: { type: ShadingType.CLEAR, fill: CODE_FILL, color: "auto" },
    border: { top: EDITOR_BORDER, left: EDITOR_BORDER, right: EDITOR_BORDER },
    children: [new TextRun({ text: " ", font: MONO, size: 4 })],
  }));
  out.push(new Paragraph({ spacing: { after: 200 }, children: [] }));
  return out;
}

function fileBlock(entry) {
  const base = entry.base === "api" ? API_BASE : APP_BASE;
  const abs = path.join(base, entry.relPath);
  const out = [h3(entry.title), p(entry.explanation)];
  if (entry.mode === "full") {
    out.push(...codeBlock(abs, entry.relPath));
  } else {
    out.push(new Paragraph({
      spacing: { after: 160 },
      children: [new TextRun({ text: `Archivo: ${entry.relPath} (resumido — ver propósito arriba)`, font: FONT, italics: true, size: 18, color: "555555" })],
    }));
  }
  return out;
}
function section(title, entries) {
  const out = [h2(title)];
  entries.forEach((e) => out.push(...fileBlock(e)));
  return out;
}

// ======================================================================
// PARTE I — BACKEND
// ======================================================================

const frameworkBackend = [
  { title: "composer.json", relPath: "composer.json", base: "api", mode: "full",
    explanation: "Manifiesto de dependencias de Composer. Sobre el esqueleto estándar de CodeIgniter 4 ('codeigniter4/appstarter') se añadieron las librerías propias del proyecto: firebase/php-jwt (emisión y verificación de JWT), guzzlehttp/guzzle y php-http/guzzle7-adapter (cliente HTTP, usado por el SDK de OpenAI), openai-php/client (generación de preguntas sugeridas), smalot/pdfparser (lectura de PDF de las secciones de curso) y zircote/swagger-php (generación de la documentación OpenAPI)." },
  { title: "Plantilla de entorno: env", relPath: "env", base: "api", mode: "summary",
    explanation: "Plantilla de variables de entorno que se copia a '.env' en cada instalación. Define, sin valores reales, las claves de configuración propias del proyecto: conexión a la base de datos (database.default.*) y la clave de OpenAI (OPENAI_API_KEY). El archivo '.env' real con credenciales nunca se incluye en el manual ni se sube al repositorio." },
  { title: "Config/App.php", relPath: "app/Config/App.php", base: "api", mode: "full",
    explanation: "Configuración general de la aplicación, generada por el framework. Los valores ajustados para el proyecto son la URL base (baseURL), la página de índice (indexPage) y la zona horaria (appTimezone = 'UTC'); el resto de las opciones se dejan en su valor por defecto de CodeIgniter 4." },
  { title: "Config/Database.php", relPath: "app/Config/Database.php", base: "api", mode: "full",
    explanation: "Configuración de conexión a la base de datos (MySQLi), tomada de las variables de entorno (.env): host, nombre de base de datos, usuario, contraseña y driver, para los entornos default y testing." },
  { title: "Config/Filters.php", relPath: "app/Config/Filters.php", base: "api", mode: "full",
    explanation: "Registra los alias de filtros disponibles para las rutas. Sobre los filtros propios de CodeIgniter (csrf, toolbar, honeypot, etc.) se agregan los tres filtros personalizados del proyecto: 'cors' → App\\Filters\\Cors, 'auth' → App\\Filters\\AuthFilter y 'admin' → App\\Filters\\AdminFilter; además, 'cors' se declara como filtro global aplicado a toda petición entrante." },
  { title: "public/index.php", relPath: "public/index.php", base: "api", mode: "full",
    explanation: "Punto de entrada (front controller) generado por CodeIgniter 4: define las rutas del sistema de archivos y delega la ejecución completa de la petición HTTP al núcleo del framework. No fue modificado respecto al esqueleto estándar." },
  { title: "public/.htaccess", relPath: "public/.htaccess", base: "api", mode: "full",
    explanation: "Configuración de Apache (mod_rewrite) que permite las URLs amigables de la API: redirige cualquier petición que no apunte a un archivo o carpeta existente hacia 'index.php', habilitando el enrutamiento interno de CodeIgniter. Es el archivo estándar del framework, esencial para que las rutas funcionen en producción (cPanel/Apache)." },
];

const arquitecturaBackend = [
  { title: "Config/Routes.php", relPath: "app/Config/Routes.php", base: "api", mode: "full",
    explanation: "Define todas las rutas HTTP de la API y los filtros (auth, admin) que protegen cada endpoint. Centraliza el mapeo URL → método de controlador para los módulos de autenticación, cursos, secciones, preguntas, evidencias, noticias y contacto." },
  { title: "Filters/Cors.php", relPath: "app/Filters/Cors.php", base: "api", mode: "full",
    explanation: "Filtro que agrega las cabeceras CORS (Access-Control-Allow-Origin, Methods, Headers) a cada respuesta, permitiendo que el frontend, alojado en otro dominio o puerto, consuma la API sin ser bloqueado por el navegador." },
  { title: "Filters/AuthFilter.php", relPath: "app/Filters/AuthFilter.php", base: "api", mode: "full",
    explanation: "Filtro que verifica la presencia y validez de un token JWT en la cabecera Authorization de cada petición protegida. Si el token falta, es inválido o expiró, responde 401 y detiene la ejecución antes de llegar al controlador." },
  { title: "Filters/AdminFilter.php", relPath: "app/Filters/AdminFilter.php", base: "api", mode: "full",
    explanation: "Filtro que, una vez validado el JWT por AuthFilter, comprueba que el rol del usuario autenticado sea administrador; si no lo es, responde 403 Forbidden." },
  { title: "Controllers/BaseController.php", relPath: "app/Controllers/BaseController.php", base: "api", mode: "full",
    explanation: "Clase base abstracta de la que heredan todos los controladores de la aplicación. Carga los helpers comunes y expone las instancias de request, response y logger a las clases hijas." },
  { title: "Controllers/Home.php", relPath: "app/Controllers/Home.php", base: "api", mode: "full",
    explanation: "Controlador trivial que devuelve la vista de bienvenida en la ruta raíz '/'. No forma parte de la API funcional, solo confirma que el servidor está activo." },
];

const controllers = [
  { title: "Controllers/AuthController.php", relPath: "app/Controllers/AuthController.php", base: "api", mode: "full",
    explanation: "Controlador de autenticación y gestión de usuarios. Implementa registro (register), inicio de sesión con emisión de JWT (login), recuperación de contraseña por correo (sendLoginReminder), actualización de perfil, subida de foto de perfil, cambio de contraseña, listado de estudiantes, activación/desactivación de cuentas (toggleUserStatus) y aceptación del tratamiento de datos personales (acceptAutoTraDatos)." },
  { title: "Controllers/CursoController.php", relPath: "app/Controllers/CursoController.php", base: "api", mode: "full",
    explanation: "CRUD de cursos: listado paginado/filtrado (index), listado de cursos activos con sus preguntas asociadas (activosConPreguntas), detalle de un curso (show), creación de un curso —que delega en CursoService la creación de su carpeta física en el servidor— (create), actualización (update) y subida de la imagen de portada (uploadPortada)." },
  { title: "Controllers/CursoDesarrolloController.php", relPath: "app/Controllers/CursoDesarrolloController.php", base: "api", mode: "full",
    explanation: "Gestiona el avance del estudiante dentro de un curso: registra el inicio del curso (inicio) y su finalización junto con el resultado del cuestionario (finalizacion), delegando toda la lógica de negocio a CursoDesarrolloService." },
  { title: "Controllers/EvidenciasController.php", relPath: "app/Controllers/EvidenciasController.php", base: "api", mode: "full",
    explanation: "CRUD de evidencias (publicaciones/resultados del proyecto): listado completo (index), listado de evidencias activas para la vista pública (activas), detalle (show), creación (create) y actualización (update), incluyendo el manejo de las imágenes asociadas vía ImageUploadService." },
  { title: "Controllers/NoticiasEventosController.php", relPath: "app/Controllers/NoticiasEventosController.php", base: "api", mode: "full",
    explanation: "CRUD de noticias y eventos publicados en el sitio: listado (index), detalle (show), creación (create) y actualización (update), administrando además el estado de publicación (Borrador, Publicado, Borrado)." },
  { title: "Controllers/PreguntaController.php", relPath: "app/Controllers/PreguntaController.php", base: "api", mode: "full",
    explanation: "CRUD del banco de preguntas usado en los cuestionarios de los cursos: listado (index), creación (create), actualización (update) y eliminación (delete), gestionando junto a cada pregunta sus opciones de respuesta." },
  { title: "Controllers/PreguntasSugeridasController.php", relPath: "app/Controllers/PreguntasSugeridasController.php", base: "api", mode: "full",
    explanation: "Expone el endpoint (index) que genera preguntas sugeridas automáticamente a partir de los PDF de las secciones de un curso, delegando el procesamiento a PreguntasSugeridasService (integración con la API de OpenAI)." },
  { title: "Controllers/SeccionCursoController.php", relPath: "app/Controllers/SeccionCursoController.php", base: "api", mode: "full",
    explanation: "CRUD de las secciones (capítulos en PDF) de un curso: listar (index), crear subiendo el archivo PDF (create), actualizar (update), eliminar/inactivar (delete) y descargar el archivo de una sección (download)." },
  { title: "Controllers/OpenApi/OpenApiInfo.php", relPath: "app/Controllers/OpenApi/OpenApiInfo.php", base: "api", mode: "full",
    explanation: "Clase de anotaciones (atributos PHP) usada por la librería zircote/swagger-php para generar automáticamente la documentación OpenAPI/Swagger de la API (ver public/swagger.html). No contiene lógica de negocio." },
];

const services = [
  { title: "Services/CursoService.php", relPath: "app/Services/CursoService.php", base: "api", mode: "full",
    explanation: "Lógica de negocio de cursos: valida que no exista un curso con el mismo nombre, calcula y crea la carpeta física donde se almacenarán los materiales del curso, y orquesta la creación completa de un curso (registro en base de datos + carpeta en disco)." },
  { title: "Services/CursoDesarrolloService.php", relPath: "app/Services/CursoDesarrolloService.php", base: "api", mode: "full",
    explanation: "Lógica de negocio del avance del estudiante en un curso: crea/actualiza la fila en cursodesarrolloestudiante al iniciar el curso (inicioCurso) y al finalizarlo (finalizacionCurso), calculando si el estudiante aprobó según el porcentaje de respuestas correctas exigido." },
  { title: "Services/SeccionCursoService.php", relPath: "app/Services/SeccionCursoService.php", base: "api", mode: "full",
    explanation: "Lógica de negocio de las secciones de un curso: valida y almacena los archivos PDF subidos, genera nombres de archivo únicos, crea/actualiza/inactiva secciones, calcula el siguiente número de orden y resuelve la ruta física para la descarga de un archivo." },
  { title: "Services/PreguntasSugeridasService.php", relPath: "app/Services/PreguntasSugeridasService.php", base: "api", mode: "full",
    explanation: "Integra la API de OpenAI (Responses API) para leer el PDF de una sección de curso y generar automáticamente preguntas de cuestionario: sube el PDF de forma temporal, envía el prompt al modelo, parsea la respuesta JSON de forma robusta ante variaciones de formato y normaliza las preguntas obtenidas antes de devolverlas al controlador." },
  { title: "Services/ImageUploadService.php", relPath: "app/Services/ImageUploadService.php", base: "api", mode: "full",
    explanation: "Servicio genérico para guardar imágenes subidas por el usuario (portadas de curso, fotos de perfil, evidencias), validando el tamaño máximo y el tipo MIME real del archivo (no solo la extensión), y para eliminar imágenes previamente almacenadas cuando se reemplazan o se elimina el registro." },
];

const models = [
  { title: "Models/UserModel.php", relPath: "app/Models/UserModel.php", base: "api", mode: "full",
    explanation: "Modelo de la tabla 'usuario'. Define los campos permitidos para registro, login y gestión de perfil (nombre, correo, contraseña, rol, fecha de nacimiento, foto, estado, aceptación del tratamiento de datos, entre otros)." },
  { title: "Models/CursoModel.php", relPath: "app/Models/CursoModel.php", base: "api", mode: "full",
    explanation: "Modelo de la tabla 'curso'. Define los campos del curso (nombre, descripción, portada, cantidad y porcentaje de aprobación de preguntas, estado, fechas)." },
  { title: "Models/SeccionCursoModel.php", relPath: "app/Models/SeccionCursoModel.php", base: "api", mode: "full",
    explanation: "Modelo de la tabla 'seccioncurso' (secciones/capítulos en PDF de cada curso). Además de los campos estándar, incluye el método propio seccionesPorCurso() para obtener las secciones activas de un curso ordenadas." },
  { title: "Models/CursoDesarrolloEstudianteModel.php", relPath: "app/Models/CursoDesarrolloEstudianteModel.php", base: "api", mode: "full",
    explanation: "Modelo de la tabla 'cursodesarrolloestudiante', que registra el progreso y resultado de cada estudiante en cada curso (inicio, finalización, calificación, aprobación)." },
  { title: "Models/PreguntaModel.php", relPath: "app/Models/PreguntaModel.php", base: "api", mode: "full",
    explanation: "Modelo de la tabla 'pregunta' del banco de preguntas (texto, tipo, descripción, rango de edad objetivo, estado)." },
  { title: "Models/OpcionRespuestaModel.php", relPath: "app/Models/OpcionRespuestaModel.php", base: "api", mode: "full",
    explanation: "Modelo de la tabla 'opcionrespuesta', las opciones de respuesta asociadas a cada pregunta (texto de la opción y si es la correcta)." },
  { title: "Models/EvidenciasModel.php", relPath: "app/Models/EvidenciasModel.php", base: "api", mode: "full",
    explanation: "Modelo de la tabla 'evidencias' (publicaciones/resultados del proyecto mostrados públicamente, con imagen y enlace opcional)." },
  { title: "Models/NoticiasEventosModel.php", relPath: "app/Models/NoticiasEventosModel.php", base: "api", mode: "full",
    explanation: "Modelo de la tabla 'noticias_eventos' (noticias y eventos publicados, con fecha de publicación, enlace y estado de publicación)." },
  { title: "Models/ContactoModel.php", relPath: "app/Models/ContactoModel.php", base: "api", mode: "full",
    explanation: "Modelo de la tabla 'contacto', los mensajes enviados desde el formulario público de contacto (nombre, correo, mensaje, fecha, estado)." },
];

// ======================================================================
// PARTE II — FRONTEND
// ======================================================================

const frameworkFrontend = [
  { title: "package.json", relPath: "package.json", base: "app", mode: "full",
    explanation: "Manifiesto de dependencias de Create React App. Sobre la base de react/react-dom/react-scripts se añadieron las librerías propias del proyecto: @mui/material y librerías @emotion (interfaz Material UI), axios (consumo de la API), react-router-dom v7 (enrutamiento), jwt-decode (lectura del token JWT en el cliente), react-pdf y pdfjs-dist (visor de PDF de las secciones de curso), sweetalert2 (alertas), swiper (carrusel) y date-fns (manejo de fechas)." },
  { title: "public/index.html", relPath: "public/index.html", base: "app", mode: "full",
    explanation: "Plantilla HTML raíz de la SPA, generada por Create React App. Se personalizaron el título ('BioPlastic'), la meta-descripción, el ícono y la fuente tipográfica (Google Fonts 'Lato' y 'Material Icons'); el resto del archivo conserva los comentarios y la estructura estándar de CRA." },
  { title: "public/manifest.json", relPath: "public/manifest.json", base: "app", mode: "full",
    explanation: "Manifiesto de aplicación web (PWA) generado por Create React App, personalizado con el nombre 'BioPlastic' y sus íconos, usado por los navegadores para permitir 'instalar' la app en escritorio o móvil." },
  { title: "src/styles.css", relPath: "src/styles.css", base: "app", mode: "full",
    explanation: "Hoja de estilos global propia del proyecto (no generada por CRA): define la clase utilitaria 'bright-button' y el recorte de texto a 4 líneas con puntos suspensivos usado en las tarjetas de evidencias." },
  { title: "src/reportWebVitals.js", relPath: "src/reportWebVitals.js", base: "app", mode: "full",
    explanation: "Archivo estándar de Create React App para medir métricas de rendimiento web (CLS, FID, FCP, LCP, TTFB); no fue modificado respecto a la plantilla generada por CRA." },
  { title: "src/serviceWorkerRegistration.js", relPath: "src/serviceWorkerRegistration.js", base: "app", mode: "full",
    explanation: "Archivo estándar de Create React App que registra el service worker para el soporte PWA/offline de la aplicación; no fue modificado respecto a la plantilla generada por CRA." },
  { title: "src/setupTests.js", relPath: "src/setupTests.js", base: "app", mode: "full",
    explanation: "Archivo estándar de Create React App que configura los matchers de jest-dom para las pruebas; no fue modificado respecto a la plantilla generada por CRA." },
];

const arquitecturaFrontend = [
  { title: "src/App.js", relPath: "src/App.js", base: "app", mode: "full",
    explanation: "Componente raíz de la aplicación React: envuelve todo el árbol de componentes con el AuthProvider (contexto de autenticación) y renderiza el AppRouter." },
  { title: "src/index.js", relPath: "src/index.js", base: "app", mode: "full",
    explanation: "Punto de entrada estándar de Create React App: monta el componente App en el DOM (elemento 'root') y registra el service worker de la PWA." },
  { title: "src/api/client.js", relPath: "src/api/client.js", base: "app", mode: "full",
    explanation: "Instancia de Axios configurada con la URL base de la API y cabeceras por defecto. Incluye un interceptor de petición que adjunta el token JWT a cada llamada (excepto a las rutas públicas de autenticación) y un interceptor de respuesta que detecta la expiración de sesión." },
  { title: "src/api/constants.js", relPath: "src/api/constants.js", base: "app", mode: "full",
    explanation: "Constantes de configuración de la capa de API: URL base (tomada de la variable de entorno REACT_APP_API_URL), prefijo de rutas, cabeceras por defecto y tiempo de espera (timeout) de las peticiones." },
  { title: "src/api/index.js", relPath: "src/api/index.js", base: "app", mode: "full",
    explanation: "Archivo barril que reexporta el cliente Axios y las constantes anteriores para simplificar las importaciones en el resto del proyecto." },
  { title: "src/router/AppRouter.jsx", relPath: "src/router/AppRouter.jsx", base: "app", mode: "full",
    explanation: "Define el árbol de rutas de la aplicación con react-router-dom: rutas públicas (inicio, login, registro, noticias, etc.) y rutas privadas, protegidas con PrivateRoute y envueltas en MainLayout." },
  { title: "src/router/PrivateRoute.jsx", relPath: "src/router/PrivateRoute.jsx", base: "app", mode: "full",
    explanation: "Componente de orden superior que consulta el AuthContext para saber si hay una sesión activa; si no la hay, redirige a la página de login, y si la hay, renderiza la ruta protegida solicitada." },
  { title: "src/context/AuthContext.jsx", relPath: "src/context/AuthContext.jsx", base: "app", mode: "full",
    explanation: "Contexto global de autenticación: decodifica y conserva la sesión del usuario, expone login/logout/updateUser, redirige según el rol del usuario autenticado y controla el flujo de aceptación obligatoria del tratamiento de datos personales." },
  { title: "src/theme.js", relPath: "src/theme.js", base: "app", mode: "full",
    explanation: "Tema de Material UI (paleta de colores y tipografía) usado en toda la aplicación. No contiene lógica de negocio, solo configuración visual." },
  { title: "src/layouts/MainLayout.jsx", relPath: "src/layouts/MainLayout.jsx", base: "app", mode: "full",
    explanation: "Layout compartido por las páginas privadas: combina el HeaderDashboard y el SidebarDashboard alrededor del contenido propio de cada página." },
];

const servicesFrontend = [
  { title: "src/services/authService.js", relPath: "src/services/authService.js", base: "app", mode: "full",
    explanation: "Funciones que consumen los endpoints de autenticación de la API: login, register y recoverPassword." },
  { title: "src/services/usuarioService.js", relPath: "src/services/usuarioService.js", base: "app", mode: "full",
    explanation: "Funciones para listar estudiantes, cambiar su estado (activo/inactivo), actualizar el perfil, cambiar la contraseña y aceptar el tratamiento de datos personales." },
  { title: "src/services/cursosService.js", relPath: "src/services/cursosService.js", base: "app", mode: "full",
    explanation: "Funciones que cubren todo el ciclo de vida de un curso desde el frontend: crear, listar, obtener detalle, gestionar secciones (crear/actualizar/eliminar/descargar PDF), subir la portada y registrar el inicio y la finalización del curso por parte del estudiante." },
  { title: "src/services/evidenciasService.js", relPath: "src/services/evidenciasService.js", base: "app", mode: "full",
    explanation: "Funciones CRUD para las evidencias del proyecto (listar, obtener una, crear y actualizar)." },
  { title: "src/services/noticiasEventosService.js", relPath: "src/services/noticiasEventosService.js", base: "app", mode: "full",
    explanation: "Funciones CRUD para noticias y eventos (listar, obtener una, crear y actualizar)." },
  { title: "src/services/preguntasService.js", relPath: "src/services/preguntasService.js", base: "app", mode: "full",
    explanation: "Funciones CRUD para el banco de preguntas (listar por curso, crear, actualizar y eliminar)." },
  { title: "src/services/contactoService.js", relPath: "src/services/contactoService.js", base: "app", mode: "full",
    explanation: "Función para enviar el formulario público de contacto a la API." },
  { title: "src/services/index.js", relPath: "src/services/index.js", base: "app", mode: "full",
    explanation: "Archivo barril que reexporta todos los servicios anteriores para simplificar las importaciones." },
];

const utilsFrontend = [
  { title: "src/utils/cuestionarioCurso.js", relPath: "src/utils/cuestionarioCurso.js", base: "app", mode: "full",
    explanation: "Lógica del cuestionario de un curso en el cliente: normaliza las preguntas recibidas de la API, filtra las que son jugables, selecciona un subconjunto aleatorio según la cantidad configurada, calcula los índices de las opciones correctas y evalúa la respuesta del estudiante (incluye soporte para preguntas de completar con guiones '_____')." },
  { title: "src/utils/session.js", relPath: "src/utils/session.js", base: "app", mode: "full",
    explanation: "Funciones de manejo de sesión en el navegador: valida si el token JWT sigue vigente, limpia el almacenamiento local, marca y consume la bandera de 'sesión expirada' y redirige al usuario cuando esta expira." },
  { title: "src/utils/authUser.js", relPath: "src/utils/authUser.js", base: "app", mode: "full",
    explanation: "Funciones de apoyo sobre el usuario en sesión: obtener la ruta privada inicial según su rol, obtener su id y verificar si ya aceptó el tratamiento de datos." },
  { title: "src/utils/edadUsuario.js", relPath: "src/utils/edadUsuario.js", base: "app", mode: "full",
    explanation: "Calcula la edad en años a partir de la fecha de nacimiento del usuario en sesión, usada para filtrar preguntas del cuestionario según el rango de edad." },
  { title: "src/utils/logAxiosError.js", relPath: "src/utils/logAxiosError.js", base: "app", mode: "full",
    explanation: "Función auxiliar que registra en consola, de forma legible, los errores de peticiones Axios (código de estado, datos de la respuesta y mensaje)." },
  { title: "src/utils/resolveImageRef.js", relPath: "src/utils/resolveImageRef.js", base: "app", mode: "full",
    explanation: "Resuelve una referencia de imagen (ruta relativa del servidor, cadena base64 o URL completa) a una URL utilizable directamente en un elemento <img>." },
  { title: "src/hooks/useResolvableImageSrc.js", relPath: "src/hooks/useResolvableImageSrc.js", base: "app", mode: "full",
    explanation: "Hook que recibe un valor de imagen (ruta, archivo o base64) y devuelve una URL de objeto (blob) lista para usarse como src, liberando la URL anterior cuando el valor cambia." },
];

const componentesFull = [
  ["AccountSettings.jsx", "Pantalla de configuración de cuenta del usuario autenticado: alterna entre el formulario de datos de perfil (ProfileForm) y el de cambio de contraseña (ChangePasswordForm)."],
  ["AddCourseQuestionsForm.jsx", "Formulario para crear/editar preguntas de un curso (en modo lote o individual): construye el formulario de la pregunta con sus opciones de respuesta, valida los datos —incluidas las preguntas de completar con guiones '_____'— y las guarda mediante el servicio de preguntas."],
  ["ChangePasswordForm.jsx", "Formulario para cambiar la contraseña del usuario autenticado, con validación y alternancia de visibilidad de los campos de contraseña."],
  ["ContactForm.jsx", "Formulario público de contacto: captura nombre, correo y mensaje, valida los campos y los envía mediante contactoService."],
  ["CreateCourseForm.jsx", "Formulario multipaso para crear un curso nuevo: datos generales y portada en el primer paso, y carga de secciones (PDF) en el segundo, coordinando las llamadas a cursosService."],
  ["CreateEvidenceForm.jsx", "Formulario para crear una nueva evidencia del proyecto, con vista previa de imagen y envío a evidenciasService."],
  ["EditCourseForm.jsx", "Formulario multipaso para editar un curso existente: permite buscar el curso por id, modificar sus datos y portada, y administrar (crear/editar/eliminar) sus secciones."],
  ["EvidenceForm.jsx", "Formulario genérico de evidencia, reutilizado para creación y edición, con manejo de imagen y validación de campos."],
  ["Header.jsx", "Barra de navegación pública (AppBar de Material UI) responsive, con menú para escritorio y móvil y accesos que cambian según el estado de autenticación."],
  ["NoticiasEventosForm.jsx", "Formulario para crear/editar una noticia o evento, con conversión de fechas al formato esperado por la API y envío a noticiasEventosService."],
  ["ProfileForm.jsx", "Formulario de edición de los datos de perfil del usuario autenticado, incluida la foto de perfil."],
  ["QuestionModal.jsx", "Modal para crear una nueva pregunta del banco de preguntas, con gestión dinámica de opciones de respuesta y validación en tiempo real."],
  ["Slider.jsx", "Carrusel de imágenes/contenido de la página pública de inicio."],
  ["StudentCoursePdfViewer.jsx", "Visor de PDF embebido para que el estudiante lea cada sección de un curso, detectando cuándo llegó al final del documento."],
  ["StudentCourseQuiz.jsx", "Cuestionario interactivo que el estudiante responde al finalizar un curso: presenta las preguntas seleccionadas, captura las respuestas y calcula el resultado usando las utilidades de cuestionarioCurso."],
  ["TableEvidencias.jsx", "Tabla administrativa de evidencias, con búsqueda, paginación y acceso a la edición de cada registro."],
  ["TableNoticiasEventos.jsx", "Tabla administrativa de noticias y eventos, con búsqueda, paginación y chip de estado de publicación."],
  ["TableQuestions.jsx", "Tabla administrativa del banco de preguntas, con creación y edición mediante modales."],
  ["TableUsers.jsx", "Tabla administrativa de usuarios/estudiantes, con búsqueda, paginación y cambio de estado (activo/inactivo)."],
  ["TeamSection.jsx", "Sección de la página pública que presenta al equipo del proyecto."],
  ["TratamientoDatosDialog.jsx", "Diálogo modal que obliga al usuario a leer y aceptar el tratamiento de datos personales antes de continuar usando la plataforma."],
  ["UpdateEvidenceForm.jsx", "Formulario de edición de una evidencia existente."],
  ["UpdateQuestionModal.jsx", "Modal para editar una pregunta existente del banco de preguntas."],
  ["UpdateUserForm.jsx", "Formulario de edición de los datos de un usuario/estudiante por parte del administrador."],
].map(([file, exp]) => ({ title: `src/components/${file}`, relPath: `src/components/${file}`, base: "app", mode: "full", explanation: exp }));

const componentesSummary = [
  ["CardAction.jsx", "Tarjeta visual reutilizable (imagen, título, descripción y botón) usada en distintas secciones públicas."],
  ["CardActionSection.jsx", "Sección que organiza varias tarjetas CardAction en la página pública."],
  ["CardsSection.jsx", "Sección de tarjetas informativas de la página pública, sin lógica de negocio."],
  ["Footer.jsx", "Pie de página del sitio público."],
  ["HeaderApp.jsx", "Encabezado simplificado usado dentro de la app privada, con botón para abrir el menú lateral."],
  ["HeaderDashboard.jsx", "Encabezado del panel administrativo/privado, con acceso para cerrar sesión."],
  ["Logo.jsx", "Componente del logotipo de la marca, con redirección al inicio al hacer clic."],
  ["Sidebar.jsx", "Menú lateral de navegación de la zona pública."],
  ["SidebarDashboard.jsx", "Menú lateral de navegación del panel privado/administrativo, con opciones que cambian según el rol del usuario."],
  ["UpdateEvidenceModal.jsx", "Modal que envuelve a UpdateEvidenceForm para editar una evidencia desde la tabla administrativa."],
  ["UpdateUserModal.jsx", "Modal que envuelve a UpdateUserForm para editar un usuario desde la tabla administrativa."],
].map(([file, exp]) => ({ title: `src/components/${file}`, relPath: `src/components/${file}`, base: "app", mode: "full", explanation: exp }));

const paginasPublicasFull = [
  ["Login.jsx", "Página de inicio de sesión: formulario de correo/contraseña, validación, llamada a authService.login y redirección según el rol del usuario autenticado."],
  ["Register.jsx", "Página de registro de nuevos usuarios, con validaciones de formato de correo, coincidencia de contraseñas y fecha de nacimiento."],
  ["Remember.jsx", "Página de recuperación de contraseña: solicita el correo registrado y dispara el envío de una nueva contraseña temporal."],
  ["NewsEvents.jsx", "Página pública que lista las noticias y eventos publicados, con paginación y un modal de detalle por elemento."],
  ["EvidenceResults.jsx", "Página pública que muestra las evidencias/resultados activos del proyecto, con un modal de detalle por evidencia."],
  ["AboutUs.jsx", "Página pública 'Quiénes somos', de contenido mayormente estático."],
].map(([file, exp]) => ({ title: `src/pages/public/${file}`, relPath: `src/pages/public/${file}`, base: "app", mode: "full", explanation: exp }));

const paginasPublicasSummary = [
  ["Home.jsx", "Página de inicio pública: compone las secciones de presentación (slider, tarjetas, equipo) dentro del layout público."],
  ["Blog.jsx", "Página placeholder, reservada para una sección de blog aún sin implementar."],
].map(([file, exp]) => ({ title: `src/pages/public/${file}`, relPath: `src/pages/public/${file}`, base: "app", mode: "full", explanation: exp }));

const paginasPrivadasFull = [
  ["AvailableCourses.jsx", "Página que lista los cursos disponibles para el estudiante, con búsqueda y paginación, mostrando el progreso o la calificación obtenida cuando ya existe."],
  ["Course.jsx", "Página de gestión de cursos: alterna entre el listado de cursos y los formularios de creación/edición, e integra el modal de preguntas del curso."],
  ["StudentCourseStudy.jsx", "Página donde el estudiante avanza sección por sección (lectura de cada PDF) hasta completar el curso y acceder al cuestionario final."],
  ["StudentCourseCertificate.jsx", "Página que genera y muestra el certificado del estudiante al aprobar un curso, dibujándolo sobre un canvas para permitir su descarga."],
  ["EvidenciaFormPage.jsx", "Página contenedora que carga, si corresponde, una evidencia existente por id y renderiza el formulario de creación/edición correspondiente."],
  ["NoticiasEventosFormPage.jsx", "Página contenedora que carga, si corresponde, una noticia/evento existente por id y renderiza su formulario de creación/edición."],
].map(([file, exp]) => ({ title: `src/pages/private/${file}`, relPath: `src/pages/private/${file}`, base: "app", mode: "full", explanation: exp }));

const paginasPrivadasSummary = [
  ["AddEvidence.jsx", "Página privada que envuelve a CreateEvidenceForm dentro del layout administrativo."],
  ["Evidencias.jsx", "Página privada que envuelve a TableEvidencias dentro del layout administrativo."],
  ["NoticiasEventos.jsx", "Página privada que envuelve a TableNoticiasEventos dentro del layout administrativo."],
  ["Profile.jsx", "Página privada que envuelve a AccountSettings dentro del layout administrativo."],
  ["ManageUsers.jsx", "Página privada que envuelve a TableUsers dentro del layout administrativo."],
  ["ManageQuestions.jsx", "Página privada que envuelve a TableQuestions dentro del layout administrativo."],
  ["Certification.jsx", "Página placeholder, reservada para una funcionalidad futura del panel."],
  ["ControlQuestions.jsx", "Página placeholder, reservada para una funcionalidad futura del panel."],
  ["ControlStudent.jsx", "Página placeholder, reservada para una funcionalidad futura del panel."],
  ["Test.jsx", "Página placeholder de pruebas internas, sin uso en producción."],
  ["UpdateStudent.jsx", "Página placeholder, reservada para una funcionalidad futura del panel."],
].map(([file, exp]) => ({ title: `src/pages/private/${file}`, relPath: `src/pages/private/${file}`, base: "app", mode: "full", explanation: exp }));

// ======================================================================
// Documento
// ======================================================================

const children = [];

// Portada
children.push(
  new Paragraph({
    spacing: { after: 0 },
    alignment: AlignmentType.CENTER,
    children: [new TextRun({ text: "MANUAL DE CODIFICACIÓN", bold: true, size: 56, font: FONT })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 600 },
    children: [new TextRun({ text: "Plataforma BioPlastic — Versión 4", bold: true, size: 32, font: FONT, color: "2E7D32" })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 120 },
    children: [new TextRun({ text: "Plataforma web para la capacitación y concientización en la contaminación por polímeros y el uso de biopolímeros", font: FONT, italics: true })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { after: 600 },
    children: [new TextRun({ text: "ETITC — Escuela Tecnológica Instituto Técnico Central", font: FONT })],
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    children: [new TextRun({ text: "Autores: Manuel Alberto Rojas Martínez · Jaime Alberto Páez", font: FONT, size: 22 })],
  }),
  new Paragraph({ children: [new PageBreak()] }),
  h2("Tabla de contenido"),
  new TableOfContents("Tabla de contenido", { hyperlink: true, headingStyleRange: "1-3" }),
  new Paragraph({ children: [new PageBreak()] }),
);

// Introducción
children.push(
  h1("Introducción", { pageBreakBefore: false }),
  p("Este manual documenta el código fuente de la plataforma BioPlastic, compuesta por dos repositorios: un backend tipo API REST construido en CodeIgniter 4 (PHP) con base de datos MySQL, y un frontend de cliente único (SPA) construido en React. Para cada archivo se incluye una breve explicación de su funcionamiento y, en los archivos clave del sistema —incluidos los archivos de configuración y dependencias propios de cada framework—, el código fuente completo con resaltado tipo editor (numeración de línea y colores por tipo de token). Los archivos puramente estándar del framework que no fueron modificados, los modelos CRUD simples y las páginas que solo envuelven un componente ya documentado se presentan de forma resumida."),
  p("El manual se organiza en dos partes: primero el backend (configuración y dependencias del framework, arquitectura interna, controladores, servicios y modelos) y luego el frontend (configuración y dependencias del framework, arquitectura interna, servicios de consumo de la API, utilidades, componentes y páginas)."),
);

// PARTE I — BACKEND
children.push(h1("Parte I — Backend (CodeIgniter 4 + MySQL)"));
children.push(p("El backend expone una API REST consumida por el frontend. Usa el framework CodeIgniter 4 sobre PHP, con autenticación basada en JWT, filtros de ruta para autenticación/autorización, y una capa de servicios que concentra la lógica de negocio fuera de los controladores."));
children.push(...section("1. Configuración y dependencias del framework (CodeIgniter 4)", frameworkBackend));
children.push(...section("2. Arquitectura interna (rutas y filtros propios)", arquitecturaBackend));
children.push(...section("3. Controladores (app/Controllers)", controllers));
children.push(...section("4. Servicios (app/Services)", services));
children.push(...section("5. Modelos (app/Models)", models));

// PARTE II — FRONTEND
children.push(h1("Parte II — Frontend (React)"));
children.push(p("El frontend es una aplicación de página única (SPA) construida con React y Material UI, organizada en componentes, páginas, servicios de consumo de la API (sobre Axios), un contexto de autenticación y un enrutador con rutas públicas y privadas."));
children.push(...section("1. Configuración y dependencias del framework (Create React App)", frameworkFrontend));
children.push(...section("2. Arquitectura interna (App, rutas, contexto y cliente API)", arquitecturaFrontend));
children.push(...section("3. Servicios (src/services y src/api)", servicesFrontend));
children.push(...section("4. Utilidades y hooks (src/utils, src/hooks)", utilsFrontend));
children.push(...section("5. Componentes — formularios, tablas y vistas clave (src/components)", componentesFull));
children.push(...section("6. Componentes — elementos de soporte (src/components)", componentesSummary));
children.push(...section("7. Páginas públicas (src/pages/public)", paginasPublicasFull));
children.push(...section("7.1 Páginas públicas adicionales (src/pages/public)", paginasPublicasSummary));
children.push(...section("8. Páginas privadas (src/pages/private)", paginasPrivadasFull));
children.push(...section("8.1 Páginas privadas adicionales (src/pages/private)", paginasPrivadasSummary));

const doc = new Document({
  styles: {
    default: { document: { run: { font: FONT, size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 36, bold: true, font: FONT, color: "1B5E20" },
        paragraph: { spacing: { before: 240, after: 240 }, outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 28, bold: true, font: FONT, color: "2E7D32" },
        paragraph: { spacing: { before: 240, after: 160 }, outlineLevel: 1 } },
      { id: "Heading3", name: "Heading 3", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 24, bold: true, font: FONT, color: "333333" },
        paragraph: { spacing: { before: 200, after: 100 }, outlineLevel: 2 } },
    ],
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 },
      },
    },
    children,
  }],
});

const outPath = process.argv[2] || "Manual_Codificacion_BioPlasticV4.docx";
Packer.toBuffer(doc).then((buffer) => {
  fs.writeFileSync(outPath, buffer);
  console.log("OK ->", outPath, buffer.length, "bytes");
});
