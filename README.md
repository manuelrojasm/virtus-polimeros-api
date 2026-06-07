---
  ## 🌱 **PLATAFORMA WEB PARA LA CAPACITACIÓN Y CONCIENTIZACIÓN EN LA CONTAMINACIÓN POR POLÍMEROS Y EL USO DE BIOPOLÍMERO** 🌳

  Este proyecto propone el desarrollo de una plataforma web diseñada para educar y concientizar a la comunidad sobre el impacto ambiental de los polímeros sintéticos y los beneficios del uso de biopolímeros. 
  La plataforma tiene como objetivo proporcionar herramientas informativas y prácticas que promuevan el reciclaje, la correcta gestión de residuos y la transición hacia alternativas más sostenibles. 
  Además, busca sensibilizar a los usuarios sobre la importancia de adoptar hábitos responsables para mitigar la contaminación y proteger el planeta.
  
 #### 🌿 Con esta iniciativa, se espera no solo generar conocimiento, sino también fomentar la acción colectiva hacia un futuro más sostenible, promoviendo el uso de biopolímeros y prácticas amigables con el medio ambiente. 🍃
---

# **Proyecto CodeIgniter 4 con MySQL**

Este proyecto es una API desarrollada en **CodeIgniter 4** con **MySQL** como base de datos. Está diseñado para proporcionar una estructura limpia y modular para construir aplicaciones web y APIs de forma rápida y eficiente.

## **Requisitos del Proyecto**

Para poder ejecutar este proyecto en tu entorno local, asegúrate de tener las siguientes herramientas instaladas:

- **PHP** (v7.4 o superior)
- **Composer** (para gestionar dependencias)
- **MySQL** (o MariaDB)
- **Servidor web** (Apache o Nginx)

---

## **Configuración del Proyecto**

### **1. Clonar el Repositorio**

Primero, clona el repositorio en tu máquina local:

```bash
git clone https://github.com/manuelrojasm/virtus-polimeros-api.git
cd virtus-polimeros-api
```

### **2. Instalar Dependencias**

Instala las dependencias del proyecto usando **Composer**:

```bash
composer install
```

### **3. Configurar la Base de Datos**

Configura la conexión a la base de datos en el archivo `.env`. Renómbralo desde `.env.example` a `.env`:

```bash
cp .env.example .env
```

Edita las siguientes líneas en el archivo `.env` para conectar la base de datos:

```env
database.default.hostname = localhost
database.default.database = nombre_de_tu_base_de_datos
database.default.username = tu_usuario
database.default.password = tu_contraseña
database.default.DBDriver = MySQLi
```

**Preguntas sugeridas (OpenAI):** Para usar el endpoint `GET /cursos/{id}/preguntas-sugeridas` (generar preguntas a partir de los PDF de cada sección), añade en `.env`:

```env
OPENAI_API_KEY = sk-tu-clave-de-openai
```

### **4. Ejecutar las Migraciones (si las tienes)**

Si tienes migraciones de base de datos, ejecuta el siguiente comando para que se creen las tablas en tu base de datos:

```bash
php spark migrate
```

---

## **Ejecutar el Proyecto**

### **5. Servidor Local de Desarrollo**

Puedes usar el servidor de desarrollo de PHP para ejecutar la aplicación localmente:

```bash
php spark serve
```

El servidor se iniciará en [http://localhost:8080](http://localhost:8080).

### **6. Acceder a la API**

Asegúrate de que el servidor esté en ejecución y prueba algunos de los endpoints de la API utilizando **Postman** o **cURL**.

Ejemplo para obtener todos los usuarios:

```bash
curl http://localhost:8080/api/users
```

## **Despliegue en producción**

La API corre en un servidor Linux (Apache + PHP + MySQL), normalmente mediante **cPanel / File Manager**. En producción el sistema de archivos **distingue mayúsculas y minúsculas**, a diferencia de Windows en desarrollo local.

### Requisitos del servidor

- **PHP** 8.1 o superior
- **MySQL** o MariaDB
- **Apache** con `mod_rewrite` habilitado
- Extensiones PHP: `intl`, `mbstring`, `json`, `mysqlnd`, `curl`

El **document root** del dominio debe apuntar a la carpeta `public/`, no a la raíz del proyecto.

---

### Primera vez: despliegue completo por carpetas (cPanel)

#### Paso 1. Preparar el proyecto en tu PC

**Opción automática (Windows):** ejecuta el script `pack-deploy.bat` en la raíz del proyecto (doble clic). El script:

1. Ejecuta `composer install --no-dev --optimize-autoloader`
2. Limpia caché y logs temporales de `writable/`
3. Genera un archivo `virtus-polimeros-api-deploy-YYYYMMDD-HHMM.zip` con todas las carpetas necesarias

**Opción manual:** instala las dependencias y comprime tú mismo:

```bash
composer install --no-dev --optimize-autoloader
```

Esto genera la carpeta `vendor/`, que es necesaria en el servidor.

#### Paso 2. Carpetas y archivos que debes subir

Comprime y sube **exactamente** lo siguiente, conservando la estructura:

| Elemento | Obligatorio | Descripción |
|----------|-------------|-------------|
| `app/` | Sí | Código de la API (controladores, modelos, config, filtros, servicios) |
| `public/` | Sí | Punto de entrada web (`index.php`, `.htaccess`, `uploads/`) |
| `writable/` | Sí | Logs, caché, sesiones y archivos generados por la app |
| `vendor/` | Sí | Dependencias de Composer (se crea con el comando del paso 1) |
| `scripts/` | Opcional | Scripts SQL manuales para la base de datos |
| `env` | Sí | Plantilla para crear el `.env` en el servidor |
| `spark` | Sí | CLI de CodeIgniter |
| `preload.php` | Sí | Archivo de arranque del framework |
| `composer.json` | Sí | Definición de dependencias |
| `composer.lock` | Sí | Versiones bloqueadas de dependencias |

**No subas** estas carpetas/archivos en el primer despliegue:

| Elemento | Motivo |
|----------|--------|
| `tests/` | Solo para desarrollo |
| `docs/` | Documentación local |
| `.git/` | Control de versiones, no necesario en el servidor |
| `.env` | Se crea directamente en el servidor con datos de producción |
| `app.zip` u otros `.zip` viejos | Archivos temporales |
| `writable/cache/*` (contenido) | Se regenera solo; deja la carpeta vacía con su `index.html` |
| `writable/logs/*` (contenido) | Se regenera solo; deja la carpeta vacía con su `index.html` |

#### Paso 3. Estructura final en el servidor

Después de subir y descomprimir, el servidor debe quedar así:

```
/home/tu-usuario/
└── virtus-polimeros-api/          ← carpeta raíz del proyecto (fuera de public_html)
    ├── app/
    │   ├── Config/
    │   ├── Controllers/
    │   ├── Models/
    │   ├── Filters/
    │   ├── Services/
    │   └── ...
    ├── public/                    ← el dominio debe apuntar AQUÍ
    │   ├── index.php
    │   ├── .htaccess
    │   └── uploads/
    ├── writable/
    │   ├── cache/
    │   ├── logs/
    │   ├── session/
    │   ├── uploads/
    │   └── cursos/
    ├── vendor/
    ├── scripts/
    ├── env
    ├── spark
    ├── preload.php
    ├── composer.json
    ├── composer.lock
    └── .env                       ← lo creas en el paso 5
```

> **Ubicación recomendada:** sube el proyecto a una carpeta como `virtus-polimeros-api` en el home del hosting (`/home/tu-usuario/`), **no** directamente dentro de `public_html`. Luego configuras el dominio para que su document root sea `virtus-polimeros-api/public`.

#### Paso 4. Configurar el dominio en cPanel

1. Ve a **cPanel → Dominios** (o **Subdominios**).
2. Edita el dominio o subdominio de la API.
3. En **Document Root**, apunta a la carpeta `public` del proyecto:

```
/home/tu-usuario/virtus-polimeros-api/public
```

4. Guarda los cambios.

#### Paso 5. Crear el archivo `.env` en el servidor

1. En **File Manager**, entra a la carpeta raíz del proyecto.
2. Duplica el archivo `env` y renómbralo a `.env`.
3. Edítalo con los datos reales del hosting:

```env
CI_ENVIRONMENT = production

app.baseURL = 'https://tu-dominio.com/'

database.default.hostname = localhost
database.default.database = nombre_de_tu_base_de_datos
database.default.username = tu_usuario
database.default.password = tu_contraseña
database.default.DBDriver = MySQLi
database.default.port = 3306

# Solo si usas preguntas sugeridas con OpenAI
OPENAI_API_KEY = sk-tu-clave-de-openai
```

> **Importante:** el archivo `.env` nunca se sube desde tu PC. Créalo solo en el servidor.

#### Paso 6. Permisos de escritura

En **File Manager**, asigna permisos de escritura (775 o 755 según el hosting) a:

- `writable/`
- `writable/cache/`
- `writable/logs/`
- `writable/session/`
- `writable/uploads/`
- `writable/cursos/`
- `public/uploads/`

#### Paso 7. Base de datos

1. Crea la base de datos y el usuario en **cPanel → MySQL Databases**.
2. Importa el esquema SQL desde **phpMyAdmin** (archivo `.sql` del proyecto o scripts en `scripts/`).
3. Las tablas deben estar en **minúscula** (`curso`, `usuario`, `evidencias`, `contacto`, etc.).

#### Paso 8. Verificar que funciona

Prueba estos endpoints desde el navegador o Postman:

- `GET https://tu-dominio.com/cursos`
- `GET https://tu-dominio.com/evidencias`
- `POST https://tu-dominio.com/login`

Si algo falla, revisa los logs en `writable/logs/`.

---

### Actualizaciones posteriores (cPanel / File Manager)

Cuando ya existe el proyecto en el servidor y solo necesitas publicar cambios:

1. Sube **únicamente los archivos modificados** (por ejemplo `app/Models/EvidenciasModel.php`).
2. Mantén la misma ruta relativa (`app/Controllers/...`, `app/Models/...`, etc.).
3. Verifica que los archivos PHP terminen en `.php` en **minúsculas**.
4. Si renombraste un archivo, **elimina la versión antigua** en el servidor.
5. Si cambiaste dependencias en `composer.json`, regenera `vendor/` en tu PC y vuelve a subir la carpeta `vendor/`.
6. Si tienes terminal en cPanel, ejecuta `php spark cache:clear`. Si no, reinicia PHP desde el selector de versión.

### Problemas frecuentes en producción

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| `Call to a member function findAll() on null` | El modelo no se cargó (nombre de archivo con mayúsculas) | Renombrar a `.php` en minúsculas, por ejemplo `EvidenciasModel.php` |
| `Table 'xxx.Curso' doesn't exist` | MySQL en Linux distingue mayúsculas en nombres de tabla | Usar nombres de tabla en minúscula en modelos y consultas |
| Error 404 en todas las rutas | Document root incorrecto o `mod_rewrite` desactivado | Apuntar el dominio a `public/` y habilitar rewrite |
| Página en blanco o error 500 | Falta `vendor/` o `.env` mal configurado | Subir `vendor/` y revisar credenciales en `.env` |
| Cambios no se reflejan | Caché de CodeIgniter u opcache | `php spark cache:clear` y reiniciar PHP |

---

## 📄 **Licencia**
Este proyecto es realziado para ETITC - Escuela Tecnológica Instituto Técnico Central todos los derechos son reservados.

---

## ✨ **Autores**
- **Manuel Alberto Rojas Martinez** - Investigador y Desarrollador- [GitHub](https://github.com/manuelrojasm)  
- **Jaime Alberto Paez** Docente y Investigador Supervisor

---

Si necesita ayuda con algún apartado más detallado o adaptarlo aún más, ¡dímelo! 😊 manuelrojasm13@gmail.com
