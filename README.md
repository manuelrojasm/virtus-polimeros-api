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

## **Despliegue**

Para desplegar el proyecto en un servidor de producción, asegúrate de:

1. Configurar correctamente las variables de entorno en el archivo `.env`.
2. Configurar un servidor web (Apache o Nginx) para apuntar al directorio `public`.
3. Asegurarte de que las migraciones de base de datos estén ejecutadas en el servidor de producción.

---

## 📄 **Licencia**
Este proyecto es realziado para ETITC - Escuela Tecnológica Instituto Técnico Central todos los derechos son reservados.

---

## ✨ **Autores**
- **Manuel Alberto Rojas Martinez** - Investigador y Desarrollador- [GitHub](https://github.com/manuelrojasm)  
- **Jaime Alberto Paez** Docente y Investigador Supervisor

---

Si necesita ayuda con algún apartado más detallado o adaptarlo aún más, ¡dímelo! 😊 manuelrojasm13@gmail.com
