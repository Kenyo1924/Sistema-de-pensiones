## Sistema de Pensiones – Documentación de Flujo Completo

### 1. Descripción general
Sistema web en PHP para gestionar estudiantes, programas, semestres y pagos, con reportes PDF/Excel. Usa MySQL (PDO), sesiones PHP para autenticación (administradores y estudiantes) y librerías FPDF y PhpSpreadsheet.

### 2. Arquitectura y estructura de carpetas
- **backend/config**: conexión a base de datos (`database.php`).
- **backend/core**: lógica de negocio por entidad (`user.php`, `student.php`, `payment.php`, `semester.php`, `report.php`).
- **backend/lib/fpdf**: librería FPDF para PDF.
- **frontend**: estilos y assets; vistas son PHP en la raíz.
- **vendor**: dependencias Composer (PhpSpreadsheet, PSR, etc.).
- Hay una copia duplicada del proyecto en `Sistema Pensiones/` con estructura similar.

### 3. Conexión a base de datos
- Archivo: `backend/config/database.php`.
- Implementa `Database::getConnection()` con PDO MySQL (UTF-8). Variables: host, nombre BD, usuario, contraseña.

### 4. Autenticación y sesiones
#### 4.1 Administradores
- Pantalla: `login.php`.
- Flujo:
  1) `session_start()`. Si existe `$_SESSION['user_id']`, redirige a `dashboard.php`.
  2) POST recibe `username` y `password`.
  3) `backend/core/user.php::login($password)` busca por `username` y valida con `password_verify`.
  4) Si OK, setea `$_SESSION['user_id']`, `$_SESSION['username']`, `$_SESSION['role']` y redirige a `dashboard.php`.
  5) Si falla, muestra «Usuario o contraseña no válidos».
- Cierre de sesión: `logout.php` destruye la sesión y redirige a `login.php`.

#### 4.2 Estudiantes
- Pantalla: `student_login.php`.
- Flujo:
  1) `session_start()` y verificación de `$_SESSION['student_id']`.
  2) POST recibe `student_code` y `password`.
  3) Consulta directa a `students` por `student_code`; valida con `password_verify`.
  4) Si OK, setea `$_SESSION['student_id']`, `$_SESSION['student_name']` y redirige a `student_dashboard.php`.

#### 4.3 Protección de páginas
- La mayoría de páginas administrativas validan `$_SESSION['user_id']` al inicio y redirigen a `login.php` si no está presente.
- Páginas de estudiante validan `$_SESSION['student_id']`.
- Nota: verificar y reforzar chequeos de rol donde sea necesario (ver sección 11).

### 5. Roles de usuario (admins)
- Roles manejados en tabla `users.role`. En la raíz hay soporte para `superadmin`, `editor`, `viewer`. La copia duplicada puede no incluir `superadmin` en su `setup.php`.
- Uso típico esperado:
  - `superadmin`: gestión total (usuarios, estudiantes, pagos, semestres, reportes).
  - `editor`: gestión de estudiantes/pagos/semestres.
  - `viewer`: solo lectura (listados y reportes).

### 6. Modelo de datos (resumen)
- `users(id, username, password, role, created_at)`
- `students(id, first_name, last_name, dni, student_code, password?, created_at)`
- `student_programs(id, student_id, program_type, start_date, end_date, is_scholarship_holder, created_at)`
- `semesters(id, name, start_date, end_date)`
- `student_semester_progress(id, student_id, program_type, semester_number, semester_id)`
- `payments(id, student_id, semester_id, program_type?, payment_type, amount, payment_date, voucher_path, payment_code, created_at)`

Archivos de creación/migración:
- `setup.php`: crea tablas base (raíz y duplicado tienen diferencias en `role` y `program_type`).
- `safe_setup.php`: migra valores antiguos de `program_type` (p. ej., `maestria` → `maestria_gestion_educativa`) antes de cambiar estructura.
- `migracion_student_programs.sql`: script SQL de apoyo.

### 7. Lógica de negocio (backend/core)
- `user.php`: login, `getAll()`, `search()`, `create()` (hash de contraseña con `password_hash` asumido al crear).
- `student.php`: CRUD de estudiantes, búsqueda, obtención por ID y sus programas.
- `payment.php`:
  - `getByStudentId($student_id)`: pagos + semestre asociado.
  - `getAll()`: pagos + estudiante + semestre.
  - `search($search_term, $semester_id, $payment_type, $start_date, $end_date)`: filtros para listados/reportes.
- `semester.php`: CRUD/listado de semestres.
- `report.php`: consultas agregadas (total recaudado, morosos, etc.).

### 8. Flujos principales (UI)
#### 8.1 Dashboard (`dashboard.php`)
- Requiere sesión admin. Muestra accesos a módulos clave y (opcionalmente) logos institucionales si están en la raíz.

#### 8.2 Estudiantes
- Listado: `students.php`.
- Crear/editar/eliminar: `add_student.php`, `edit_student.php`, `delete_student.php`.
- Ver detalle: `view_student.php` muestra datos, programas, pagos y progreso por semestre; permite agregar progreso (`student_semester_progress`).

#### 8.3 Pagos
- Listado: `payments.php` con filtros (semestre, tipo, rango de fechas, alumno opcional).
- Crear/editar/eliminar: `add_payment.php`, `edit_payment.php`, `delete_payment.php`.
- Subida de voucher: guarda archivos en `backend/uploads/` y referencia en `payments.voucher_path`.

#### 8.4 Semestres
- Listado y CRUD: `add_semester.php`, `edit_semester.php`, `add_semester_progress.php` (para progreso curricular ligado a semestre académico).

### 9. Reportes
- PDF por estudiante: `generate_pdf_report.php` usa FPDF. Requiere sesión admin o de estudiante; genera tabla con pagos del estudiante.
- Excel general: `generate_excel.php` usa PhpSpreadsheet y `payment->search()` con filtros GET para descargar `pagos.xlsx`.
- CSV por estudiante: `generate_excel_report.php` para un solo estudiante (historial de pagos).
- Página de reportes: `reports.php` integra consultas de `report.php` (total recaudado, alumnos tardíos, filtros por semestre, tipo de pago y fechas).

### 10. Configuración y despliegue
- Requisitos: PHP 8+, MySQL/MariaDB, Composer (para PhpSpreadsheet), extensión PDO.
- Variables de DB: editar `backend/config/database.php` (host, db_name, username, password).
- Dependencias: en la raíz, ejecutar `composer install` para `vendor/` (si aún no existe).
- Inicialización de tablas: ejecutar `setup.php` o `safe_setup.php` (este último preserva datos y migra valores antiguos).

### 11. Consideraciones de seguridad y buenas prácticas
- Autenticación:
  - Usar `password_hash`/`password_verify` (ya implementado). Evitar mostrar detalles de error de conexión en producción.
  - Asegurar `session_regenerate_id(true)` al iniciar sesión para mitigar fijación de sesión.
- Autorización por rol:
  - Añadir verificación de `$_SESSION['role']` en páginas críticas (gestión de usuarios, eliminaciones, reportes sensibles).
- Validación/escape:
  - Consolidar validaciones de entrada en `add_*`/`edit_*`. Usar `htmlspecialchars` al imprimir y sentencias preparadas (ya usadas) para consultas.
- Archivos subidos:
  - Validar tipo/máximo de tamaño, renombrar con hash, almacenar fuera de la raíz pública o proteger por .htaccess / validación de sesión.
- Configuración:
  - Extraer credenciales a variables de entorno o archivo no versionado cuando sea posible.

### 12. Duplicación del proyecto y unificación
Se detectan dos copias: raíz y `Sistema Pensiones/`. Diferencias observadas:
- `setup.php` en raíz soporta `superadmin`; en la copia no.
- Vistas y textos pueden variar ligeramente.

Recomendación de unificación:
1) Elegir una única ubicación (preferible la raíz) como fuente de verdad.
2) Alinear `setup.php` y `safe_setup.php` (roles, `program_type`, claves foráneas).
3) Eliminar o archivar la carpeta duplicada tras migrar.
4) Verificar rutas relativas en vistas (`frontend/css/style.css`, `backend/...`).

### 13. Matriz de acceso sugerida
- superadmin: todo.
- editor: estudiantes, pagos, semestres (CRUD), reportes.
- viewer: solo lectura (listados, reportes, exportaciones).

### 14. Ciclo de una solicitud típica (ejemplo crear pago)
1) Vista `add_payment.php` recibe POST con `student_id`, `semester_id`, `payment_type`, `amount`, `payment_date`, `voucher`.
2) Valida/normaliza, sube archivo a `backend/uploads/` si corresponde.
3) Llama a métodos de `backend/core/payment.php` para insertar.
4) Redirige a `payments.php` o `view_student.php` con mensaje.

### 15. Recursos clave
- Autenticación admin: `login.php`, `backend/core/user.php`.
- Autenticación estudiante: `student_login.php`.
- Estudiantes: `students.php`, `add_student.php`, `edit_student.php`, `view_student.php`.
- Pagos: `payments.php`, `add_payment.php`, `edit_payment.php`.
- Semestres: `add_semester.php`, `edit_semester.php`.
- Reportes: `reports.php`, `generate_pdf_report.php`, `generate_excel.php`, `generate_excel_report.php`.
- DB: `backend/config/database.php`, `setup.php`, `safe_setup.php`.

### 16. Logos
- Colocar `logo_posgrado.jpg` y `logo_educacion.jpg` en la raíz del proyecto (ver `README_LOGOS.md`).

---
Si necesitas, puedo añadir diagramas (secuencia/ER) y checks de rol en páginas críticas, o preparar un script de unificación de la copia duplicada.



