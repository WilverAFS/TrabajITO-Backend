<div align="center">
  <h3>TECNOLÓGICO NACIONAL DE MÉXICO</h3>
  <h2>INSTITUTO TECNOLÓGICO DE OAXACA</h2>
  <p><strong>Ingeniería en Sistemas Computacionales</strong></p>
  <hr>
  <h1>TrabajITO</h1>
  <h3>Backend del proyecto integrador</h3>
  <p><strong>Plataforma web para vincular clientes con trabajadores de oficio mediante perfiles basados en su reputación</strong></p>
  <p>
    <strong>Materia:</strong> SCG-1009 · Gestión de Proyectos de Software<br>
    <strong>Semestre:</strong> 7.º &nbsp; | &nbsp; <strong>Grupo:</strong> 8SC &nbsp; | &nbsp; <strong>Equipo:</strong> 5<br>
    <strong>Docente:</strong> Adelina Martínez Nieto
  </p>
  <h4>Integrantes del Equipo 5</h4>
  <p>
    Cruz Sánchez Jhoan Marvin<br>
  </p>
  <p><strong>Oaxaca de Juárez, Oaxaca · 2026</strong></p>
  <p><strong>Estado:</strong> En desarrollo &nbsp;·&nbsp; <strong>Método de trabajo:</strong> Kanban</p>
</div>

---

## 1. Descripción del proyecto

**TrabajITO** es un proyecto académico de aplicación web orientado a conectar a personas que necesitan contratar servicios de oficio con trabajadores independientes, por ejemplo, de carpintería, plomería, electricidad, albañilería, pintura y herrería.

La propuesta busca facilitar búsquedas más informadas mediante perfiles profesionales, especialidades, disponibilidad, evidencias de trabajos realizados y reputación basada en calificaciones y reseñas asociadas a servicios registrados. Su alcance inicial se concentra en Oaxaca de Juárez y su zona conurbada.

## 2. Descripción de este repositorio

Este repositorio aloja el **backend de TrabajITO**, desarrollado con **PHP y Laravel**. Su responsabilidad será recibir y procesar solicitudes desde el frontend, implementar las reglas de negocio, gestionar los permisos de acceso y comunicarse con la base de datos.

Las áreas funcionales previstas para el backend son:

- **Usuarios:** registro, autenticación, cuentas y habilitación del rol de trabajador.
- **Perfiles profesionales:** experiencia, especialidades, disponibilidad y portafolios.
- **Exploración:** consultas y filtros de trabajadores por oficio, zona y reputación.
- **Contratación:** solicitudes de servicio, seguimiento y publicaciones de trabajos realizados.
- **Administración:** supervisión de usuarios, contenidos, reportes y verificaciones.

## 3. Tecnologías

| Tecnología | Uso |
|---|---|
| PHP 8.3 o superior | Lenguaje del servidor. |
| Laravel 13 | Framework del backend. |
| Composer | Gestión de dependencias PHP. |
| PostgreSQL | Sistema de gestión de bases de datos seleccionado para TrabajITO. |
| Git y GitHub | Control de versiones y colaboración mediante ramas y Pull Requests. |
| GitHub Projects | Seguimiento de actividades mediante Kanban. |
| Visual Studio Code | Editor de desarrollo recomendado. |

**Arquitectura prevista:** frontend React ↔ backend Laravel ↔ PostgreSQL.

## 4. Requisitos previos

Antes de instalar el backend, verifica que tengas:

- **PHP 8.3 o superior**, con extensiones habituales de Laravel y `pdo_pgsql` para trabajar con PostgreSQL.
- **Composer** instalado y accesible desde la terminal.
- **Git** y acceso al repositorio.
- **PostgreSQL** y sus credenciales.

Para comprobar las herramientas principales en PowerShell:

```powershell
php -v
composer --version
git --version
```

## 5. Guía de instalación local (Windows / PowerShell)

### 5.1 Clonar el repositorio

En una terminal de **Visual Studio Code** o PowerShell:

```powershell
cd "$HOME\Projects"
git clone https://github.com/WilverAFS/TrabajITO-Backend.git
cd TrabajITO-Backend
```

Si ya tienes el repositorio clonado, **no vuelvas a clonarlo**. Entra a tu carpeta existente y actualízala cuando corresponda al flujo de colaboración.

### 5.2 Instalar dependencias

```powershell
composer install
```

Este comando utiliza las dependencias registradas en `composer.lock`.

### 5.3 Crear la configuración local

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Abre `.env` y ajusta, para una comprobación inicial de Laravel sin depender todavía de una base de datos:

```dotenv
APP_NAME=TrabajITO
APP_URL=http://127.0.0.1:8000
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

La configuración de PostgreSQL (`DB_CONNECTION=pgsql`, host, puerto, base de datos, usuario y contraseña) deberá establecerse **localmente**, de acuerdo con la configuración que proporcione el responsable de base de datos. **No subas credenciales al repositorio.**

> **Importante:** no ejecutar `php artisan migrate` ni `composer run setup` sin coordinarse con el equipo. El comando `setup` del proyecto puede ejecutar migraciones.

### 5.4 Verificar Laravel e iniciar el servidor

```powershell
php artisan --version
php artisan about
php artisan serve --host=127.0.0.1 --port=8000
```

Visita **http://127.0.0.1:8000**. La página de bienvenida confirma que el servidor base responde; no significa que todos los módulos de TrabajITO estén implementados.

Para detener el servidor, presiona **Ctrl + C** en la terminal.

## 6. Estructura general del backend

```text
app/              Lógica de la aplicación
bootstrap/        Inicio y configuración de Laravel
config/           Archivos de configuración
database/        Migraciones, seeders y fábricas (según avance del proyecto)
public/           Punto de entrada web y recursos públicos
resources/        Vistas y recursos del proyecto
routes/           Definiciones de rutas
storage/          Archivos generados y registros locales
tests/            Pruebas automatizadas
.env.example      Plantilla de configuración, sin secretos
composer.json     Declaración de dependencias PHP
composer.lock     Versiones bloqueadas de dependencias
```

## 7. Flujo de colaboración con Git y GitHub

Las contribuciones se desarrollan en **ramas personales creadas a partir de `main`**. No se deben subir cambios directamente a `main` sin el proceso de revisión del equipo.

### 7.1 Actualizar la rama principal local

```powershell
git status
git switch main
git pull --ff-only origin main
```

Antes de cambiar de rama, asegúrate de no tener modificaciones locales pendientes que puedas perder. Si ya tienes una rama personal, revísala antes de intentar crear otra con el mismo nombre.

### 7.2 Crear una rama personal

```powershell
git switch -c back-jmcs
```

`back-jmcs` es un ejemplo de rama personal. Los demás integrantes deben utilizar sus propios identificadores.

### 7.3 Editar, revisar y guardar cambios

```powershell
git status
git diff
git add README.md
git commit -m "docs: actualizar README del backend de TrabajITO"
```

Agrega únicamente los archivos de tu actividad para evitar incluir cambios ajenos o archivos de configuración local.

### 7.4 Publicar la rama

```powershell
git push -u origin back-jmcs
```

### 7.5 Crear un Pull Request

En GitHub, selecciona **Compare & pull request** (o **Pull requests → New pull request**), usando:

- **Base:** `main`
- **Compare:** tu rama personal, por ejemplo `back-jmcs`

Describe qué modificaste, cómo lo verificaste y solicita la revisión del jefe del proyecto. **No fusiones el PR por tu cuenta** si el equipo requiere aprobación previa.

## 8. Buenas prácticas

- Mantener un cambio o actividad claramente identificable por rama y Pull Request.
- No subir `.env`, credenciales, `vendor/` o archivos generados que deban ignorarse.
- Conservar `composer.lock` para reproducir las mismas versiones de las dependencias.
- Documentar cambios relevantes y seguir los criterios de aceptación de los Work Items.
- Respetar las revisiones y acuerdos de calidad establecidos en el tablero Kanban.

## 9. Equipo y enlaces

Proyecto académico del **Equipo 5** de Ingeniería en Sistemas Computacionales, Instituto Tecnológico de Oaxaca.

- **Repositorio:** https://github.com/WilverAFS/TrabajITO-Backend
- **Documentación de Laravel:** https://laravel.com/docs
- **Documentación de PHP:** https://www.php.net/manual/es/

---

*TrabajITO — Proyecto integrador de Gestión de Proyectos de Software.*
