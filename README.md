# Gimnasio Huracán - Sistema de gestión de socios

Trabajo Práctico Final de Informática.

Sistema web hecho con PHP y MySQL para el gimnasio de un club. Permite que los socios se registren, inicien sesión y vean sus datos, y que un administrador gestione las cuentas: alta, búsqueda, edición, archivado y reactivación.

## Funcionalidades

**Socios**
- Registro con nombre, apellido, DNI, correo y contraseña.
- Inicio y cierre de sesión.
- Panel personal protegido con sus datos.

**Administrador**
- Panel de acceso exclusivo para el rol `admin`.
- Alta de usuarios desde el panel.
- Listado de socios activos con buscador por nombre, apellido, DNI o correo.
- Edición de datos.
- Archivado de socios (sin borrarlos de la base) y listado de archivados.
- Reactivación de cuentas archivadas.

**Portada**
- Página de inicio con el escudo, una foto del gimnasio, los planes por meses y el descuento para socios con cuota social del club (informativo).

## Tecnologías
- PHP 8 con PDO
- MySQL / MariaDB
- HTML y CSS
- XAMPP, Visual Studio Code y phpMyAdmin

## Estructura del proyecto

```
gimnasio_huracan/
├── index.php                  Portada con los planes
├── conexion.php               Conexión a la base con PDO
├── seguridad.php              Funciones del token CSRF
├── registro.php               Registro público de socios
├── login.php                  Inicio de sesión
├── panel.php                  Panel personal
├── cerrar_sesion.php          Cierre de sesión
├── admin_usuarios.php         Listado y búsqueda (admin)
├── crear_usuario.php          Alta de usuarios (admin)
├── editar_usuario.php         Edición de datos (admin)
├── archivar_usuario.php       Archivado de socios (admin)
├── usuarios_archivados.php    Listado de archivados (admin)
├── reactivar_usuario.php      Reactivación de socios (admin)
├── css/estilos.css            Estilos
├── img/                       Escudo y foto del gimnasio
└── sql/gimnasio_huracan.sql   Script de la base de datos
```

## Instalación (XAMPP)

1. Instalar XAMPP y abrir el **XAMPP Control Panel**.
2. Copiar la carpeta del proyecto en `C:\xampp\htdocs\` con el nombre `gimnasio_huracan`.
3. Iniciar **Apache** y **MySQL** (tienen que quedar en verde).
4. Entrar a `http://localhost/phpmyadmin`, abrir la pestaña **SQL**, pegar el contenido de `sql/gimnasio_huracan.sql` y hacer clic en **Continuar**. Esto crea la base `gimnasio_huracan` y sus tablas.
5. Abrir `http://localhost/gimnasio_huracan/` en el navegador.

La conexión usa el usuario `root` sin contraseña, que es la configuración por defecto de XAMPP local (archivo `conexion.php`).

### Crear el primer administrador
El registro público siempre crea socios comunes. Para tener el primer admin:
1. Registrarse desde `registro.php`.
2. En phpMyAdmin, abrir **gimnasio_huracan → usuarios → Examinar**, editar la fila y cambiar la columna `rol` de `usuario` a `admin`.
3. Cerrar sesión y volver a iniciarla.

## Cómo funciona

1. El usuario completa un **formulario HTML** que se envía por POST a un archivo PHP.
2. PHP **valida** los datos en el servidor (campos obligatorios, formato de DNI y correo, largo de contraseña).
3. PHP se conecta a MySQL con **PDO** y ejecuta **consultas preparadas**.
4. En el registro, la contraseña se guarda con `password_hash()`. En el login, se compara con `password_verify()`.
5. Al iniciar sesión se guardan el id y el rol en `$_SESSION`. Cada página privada revisa la sesión antes de mostrar nada, y las páginas de administración revisan además que el rol sea `admin`.
6. Archivar no elimina al socio: guarda la fecha en la columna `archivado_en`. Los usuarios activos son los que tienen esa columna vacía (`IS NULL`) y los archivados los que tienen fecha. Un usuario archivado no puede iniciar sesión.

## Medidas de seguridad

- Contraseñas guardadas con `password_hash()` y comprobadas con `password_verify()`. Nunca se muestran ni su hash.
- Consultas preparadas con PDO en todas las operaciones (evita la inyección SQL).
- Escape de todos los datos mostrados en HTML con `htmlspecialchars()`.
- Verificación de sesión en todas las páginas privadas y de rol `admin` en las funciones administrativas.
- `session_regenerate_id(true)` al iniciar sesión.
- Token CSRF en los formularios de alta, edición, archivado y reactivación.
- Archivar y reactivar solo funcionan por POST, nunca por un link.
- El registro público siempre asigna el rol `usuario`; el rol no se puede elegir ahí. Al editar un usuario tampoco se puede cambiar el rol, y en el alta del admin solo se aceptan los valores `usuario` y `admin`.
- Validación de datos en el servidor, no solo en el navegador.
- DNI y correo `UNIQUE` en la base y control de duplicados en PHP.
- Mismo mensaje de error si falla el correo o la contraseña, para no revelar cuál es.
- Un administrador no puede archivar su propia cuenta.
- Mensajes de error comprensibles sin mostrar detalles internos; el detalle técnico se guarda con `error_log()`.

## Pruebas realizadas

- [ ] Se puede registrar un usuario correctamente.
- [ ] El sistema rechaza DNI o correo duplicados.
- [ ] Un usuario registrado puede iniciar y cerrar sesión.
- [ ] No se puede entrar al panel personal sin iniciar sesión.
- [ ] Un usuario común no puede acceder a las funciones de administración.
- [ ] El administrador puede registrar, buscar y editar usuarios.
- [ ] Los usuarios archivados desaparecen del listado de activos.
- [ ] Se pueden consultar y reactivar los usuarios archivados.
- [ ] El sistema muestra mensajes claros ante errores de validación o de acceso.

## Dificultades encontradas y cómo se resolvieron

- **Acceso denegado en el panel de administración:** el usuario seguía con rol `usuario`. Se cambió el rol a `admin` en phpMyAdmin y se volvió a iniciar sesión, porque la sesión guarda el rol al momento del login.
- **El formulario de edición no se mostraba:** había un archivo sin guardar en Visual Studio Code. Se resolvió guardando con Ctrl + S.
- **No aparecía el registro en phpMyAdmin:** el formulario se abría desde el navegador sin pasar por `localhost`. Se corrigió abriendo el proyecto desde `http://localhost/gimnasio_huracan/`.
- **Imágenes que no se veían:** el nombre o la extensión del archivo no coincidía con el del código.

## Capturas de pantalla

_(agregar acá las capturas del registro, login, panel, listado de socios, edición y archivados)_

## Autor

Lucas Castiñeira
