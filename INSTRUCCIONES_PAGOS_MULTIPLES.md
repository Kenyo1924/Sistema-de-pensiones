# 🚀 Instrucciones para Implementar Pagos Múltiples

## 📋 Resumen
Esta actualización agrega la funcionalidad de **pagos múltiples con un solo voucher** al sistema de pensiones existente.

## 🎯 Funcionalidades Nuevas
- ✅ **Pagos múltiples** - Un voucher puede cubrir varios conceptos (matrícula + pensiones)
- ✅ **Montos personalizables** - Ingresa el monto exacto para cada concepto
- ✅ **Descripción de voucher** - Agrega notas adicionales al voucher
- ✅ **Compatibilidad total** - Los pagos individuales siguen funcionando igual

## 📁 Archivos a Copiar

### 1. Archivos Nuevos (Copiar estos archivos)
```
add_multi_payment.php          - Formulario para pagos múltiples
view_payment_group.php         - Ver detalles de vouchers agrupados
INSTRUCCIONES_PAGOS_MULTIPLES.md - Este archivo de instrucciones
```

### 2. Archivos Modificados (Reemplazar estos archivos)
```
safe_setup.php                 - Migración actualizada
backend/core/payment.php       - Lógica de pagos actualizada
payments.php                   - Botón para pagos múltiples
```

## 🔧 Pasos de Instalación

### Paso 1: Copiar Archivos
1. Copia todos los archivos nuevos y modificados a la carpeta del sistema
2. Asegúrate de que los permisos de archivos sean correctos

### Paso 2: Ejecutar Migración
1. Accede a: `http://tu-servidor/safe_setup.php`
2. El script ejecutará automáticamente:
   - ✅ Agregar campos para pagos múltiples
   - ✅ Crear índices para mejor rendimiento
   - ✅ Actualizar registros existentes
   - ✅ Crear vista para consultas optimizadas

### Paso 3: Verificar Instalación
1. Ve a **Pagos** → **Agregar Pago Múltiple**
2. Prueba crear un pago múltiple
3. Verifica que los reportes funcionan correctamente

## 🛡️ Seguridad
- ✅ **No se pierden datos** - Todos los datos existentes se preservan
- ✅ **Migración segura** - Solo agrega campos, no elimina nada
- ✅ **Transacciones** - Garantiza integridad de datos
- ✅ **Validaciones** - Códigos únicos y montos válidos

## 📊 Estructura de Base de Datos

### Campos Agregados a la tabla `payments`:
```sql
voucher_group_id VARCHAR(50)     - Agrupa pagos del mismo voucher
is_multi_payment BOOLEAN         - Indica si es pago múltiple
voucher_description TEXT         - Descripción opcional del voucher
```

### Vista Creada:
```sql
payment_groups                   - Vista optimizada para consultas de pagos agrupados
```

## 🎮 Cómo Usar

### Para Pagos Individuales (Como antes):
1. Ve a **Pagos** → **Agregar Pago Individual**
2. Funciona exactamente igual que antes

### Para Pagos Múltiples (Nuevo):
1. Ve a **Pagos** → **Agregar Pago Múltiple**
2. Selecciona estudiante y semestre
3. Marca los tipos de pago que quieres incluir
4. Ingresa los montos para cada concepto
5. Agrega código de voucher único
6. Opcionalmente agrega descripción
7. Registra el pago

## 📈 Ejemplo de Uso

**Escenario**: Estudiante paga Matrícula + Pensión 1 con un solo voucher

```
Estudiante: Juan Pérez
Semestre: 2024-I

Tipos de Pago:
✅ Matrícula: S/ 100.00
✅ Pensión 1: S/ 300.00

Código Voucher: VCH-2024-001
Descripción: Pago de matrícula y primera pensión
Total: S/ 400.00
```

**Resultado**: Se crean 2 registros en la base de datos con el mismo `voucher_group_id`

## 🔍 Verificación Post-Instalación

### 1. Verificar Campos en Base de Datos
```sql
DESCRIBE payments;
-- Debe mostrar: voucher_group_id, is_multi_payment, voucher_description
```

### 2. Verificar Vista
```sql
SHOW TABLES LIKE 'payment_groups';
-- Debe mostrar la vista payment_groups
```

### 3. Probar Funcionalidad
- Crear un pago múltiple
- Verificar que se guarda correctamente
- Comprobar que los reportes funcionan

## 🆘 Solución de Problemas

### Error: "Campo ya existe"
- ✅ Normal - El script verifica si los campos ya existen
- ✅ No afecta la funcionalidad

### Error: "Índice duplicado"
- ✅ Normal - El script verifica si el índice ya existe
- ✅ No afecta la funcionalidad

### Error de Permisos
- ✅ Verificar permisos de archivos PHP
- ✅ Verificar permisos de base de datos

## 📞 Soporte

Si encuentras algún problema:
1. Verifica que todos los archivos se copiaron correctamente
2. Revisa los logs de error de PHP
3. Verifica la conexión a la base de datos
4. Ejecuta `safe_setup.php` nuevamente si es necesario

## 🎉 ¡Listo!

Una vez completada la instalación, tendrás:
- ✅ Pagos individuales (funcionalidad original)
- ✅ Pagos múltiples (nueva funcionalidad)
- ✅ Montos personalizables
- ✅ Descripción de vouchers
- ✅ Reportes mejorados
- ✅ Todos los datos preservados

¡Disfruta de la nueva funcionalidad! 🚀
