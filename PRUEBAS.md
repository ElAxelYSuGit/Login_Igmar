# Documentación de Pruebas Realizadas

> **Proyecto:** Duna Studio - Sistema de Autenticación Multi-Factor  
> **Fecha:** 12 de junio de 2026  
> **Estándar de documentación:** PHPDoc (PSR-5)  

---

## 1. Matriz de Requisitos vs Pruebas

| # | Requisito | Prueba Realizada | Resultado |
|---|-----------|-----------------|-----------|
| 1 | 3 roles: guest, user, admin | Verificar seeders y registro de guest | ✅ Los 3 roles existen. Guest se crea desde la interfaz (/register) |
| 2 | Validación de password | Registrar con contraseñas débiles | ✅ Rechaza < 8 chars, sin mayúsculas, sin números, sin símbolos |
| 3 | reCAPTCHA en servicios abiertos | Login y registro sin reCAPTCHA | ✅ Bloquea envío sin completar reCAPTCHA |
| 4 | Sanitización (front y back) | Inyectar HTML/XSS en campos | ✅ strip_tags() en middleware + validación JS |
| 5 | Mensajes de errores claros | Provocar errores en cada formulario | ✅ Mensajes en español con SweetAlert2 |
| 6 | Logs de desarrollo | Revisar storage/logs/laravel.log | ✅ Log::info, Log::debug, Log::warning en todos los flujos |
| 7 | Logs de auditoría | Revisar storage/logs/audit.log | ✅ Formato Qué/Quién/Cuándo/Dónde en canal 'audit' |
| 8 | Componentes diferentes por rol | Login con cada rol y verificar dashboard | ✅ guest.blade, user.blade, admin.blade distintos |
| 9 | Encriptación de password y token | Verificar DB y código | ✅ Hash::make() en passwords, OTP, PIN |
| 10 | Rate Limit en registro y login | Intentar 6+ veces seguidas | ✅ Bloquea tras 5 intentos (60s cooldown) |
| 11 | Commits claros y documentados | Revisar git log | ✅ Commit con descripción detallada |
| 12 | Funciones documentadas (PHPDoc PSR-5) | Revisar código fuente | ✅ Todas las funciones con @param, @return, @throws |
| 13 | Factores de autentificación | Probar flujo completo por rol | ✅ Guest=1FA, User=2FA, Admin=3FA |
| 14 | Documentación de pruebas | Este documento | ✅ Presente documento |
| 15 | Manejo correcto de sesión | Verificar config y headers | ✅ Encrypt=true, same_site=strict, regenerate, headers seguros |

---

## 2. Pruebas por Flujo de Autenticación

### 2.1 Flujo Guest (1 Factor)

| Paso | Acción | Resultado Esperado | Resultado Obtenido |
|------|--------|--------------------|--------------------|
| 1 | Acceder a `/register` | Se muestra formulario de registro | ✅ Formulario con nombre, email, password, reCAPTCHA |
| 2 | Registrar con contraseña débil ("123") | Error de validación | ✅ "La contraseña debe tener al menos 8 caracteres" |
| 3 | Registrar sin reCAPTCHA | Error de reCAPTCHA | ✅ "Por favor, marca la casilla de seguridad reCAPTCHA" |
| 4 | Registrar con datos válidos | Cuenta creada | ✅ "Cuenta creada exitosamente" (rol guest) |
| 5 | Login como guest | Acceso directo a `/guest/dashboard` | ✅ Dashboard de invitado (solo lectura) |
| 6 | Cerrar sesión | Redirige al login | ✅ Sesión invalidada, token regenerado |

### 2.2 Flujo User (2 Factores)

| Paso | Acción | Resultado Esperado | Resultado Obtenido |
|------|--------|--------------------|--------------------|
| 1 | Login como user | Envía OTP al email | ✅ "Se envió el código al correo" |
| 2 | Ingresar OTP incorrecto | Error de validación | ✅ "Código incorrecto" |
| 3 | Ingresar OTP expirado | Error de expiración | ✅ "El código expiró o ya no es válido" |
| 4 | Ingresar OTP correcto | Acceso a `/user/dashboard` | ✅ Dashboard operativo (2FA) |

### 2.3 Flujo Admin (3 Factores)

| Paso | Acción | Resultado Esperado | Resultado Obtenido |
|------|--------|--------------------|--------------------|
| 1 | Login como admin | Envía OTP al email | ✅ "Se envió el código al correo" |
| 2 | Ingresar OTP correcto (admin nuevo) | Muestra código de identidad 6 dígitos | ✅ Código mostrado + notificación al admin primario |
| 3 | Admin primario aprueba con código | Genera PIN de 4 dígitos | ✅ PIN mostrado para entregar al solicitante |
| 4 | Ingresar PIN correcto | Acceso a `/admin/dashboard` | ✅ Dashboard administrativo (3FA) |
| 5 | Ingresar OTP correcto (admin ya verificado) | Pide PIN directamente | ✅ Salta la verificación presencial |

---

## 3. Pruebas de Seguridad

### 3.1 Sanitización de Datos (Requisito 4)

| Prueba | Input | Resultado |
|--------|-------|-----------|
| XSS en nombre | `<script>alert('xss')</script>` | ✅ Se almacena como `alert('xss')` (sin tags) |
| HTML en email | `<b>test</b>@mail.com` | ✅ Rechazado por validación de email |
| SQL Injection en login | `' OR 1=1 --` | ✅ Rechazado (Eloquent usa prepared statements) |
| Atributos `required` en HTML | Inspeccionar DOM | ✅ Ningún input tiene `required`, validación por JS |

### 3.2 Rate Limiting (Requisito 10)

| Prueba | Acción | Resultado |
|--------|--------|-----------|
| Login: 6 intentos fallidos | Enviar credenciales incorrectas 6 veces | ✅ "Demasiados intentos. Intenta de nuevo en X segundos" (429) |
| Registro: 6 intentos | Registrar 6 cuentas seguidas | ✅ "Demasiados intentos de registro" (429) |

### 3.3 Cabeceras de Seguridad (Requisito 15)

| Header | Valor | Verificación |
|--------|-------|-------------|
| X-Frame-Options | DENY | ✅ Previene clickjacking |
| X-Content-Type-Options | nosniff | ✅ Previene MIME sniffing |
| X-XSS-Protection | 1; mode=block | ✅ Protección XSS del navegador |
| Referrer-Policy | strict-origin-when-cross-origin | ✅ Limita fuga de referrer |
| Cache-Control | no-store, no-cache, must-revalidate | ✅ Previene cache de datos sensibles |

### 3.4 Sesión Segura (Requisito 15)

| Configuración | Valor | Archivo |
|--------------|-------|---------|
| Encriptación de sesión | `encrypt => true` | config/session.php |
| Same-Site cookie | `strict` | config/session.php |
| HTTP Only cookie | `true` | config/session.php |
| Expiración al cerrar navegador | `expire_on_close => true` | config/session.php |
| Regeneración de sesión al login | `$request->session()->regenerate()` | AuthController.php |
| Invalidación al logout | `$request->session()->invalidate()` | AuthController.php |
| Regeneración de CSRF al logout | `$request->session()->regenerateToken()` | AuthController.php |
| No crear sesión hasta login | La sesión pendiente solo almacena IDs, no autenticación | AuthController.php |

---

## 4. Pruebas de Logs

### 4.1 Logs de Desarrollo (Requisito 6)

Ubicación: `storage/logs/laravel.log`

Eventos registrados con `Log::info()`, `Log::debug()`, `Log::warning()`, `Log::error()`:
- Inicio de verificación de reCAPTCHA
- Resultado de verificación de reCAPTCHA
- Creación de cuenta guest
- Envío de OTP
- Verificación de OTP (éxito/error)
- Verificación de PIN (éxito/error)
- Cierre de sesión
- Errores de flujo (rol no soportado)

### 4.2 Logs de Auditoría (Requisito 7)

Ubicación: `storage/logs/audit.log`

Formato de cada entrada:
```
[Qué]    → Descripción del evento de seguridad
[Quién]  → Email del usuario involucrado
[Cuándo] → Timestamp del evento (Y-m-d H:i:s)
[Dónde]  → Dirección IP de la petición
```

Eventos auditados:
| Evento | Canal | Nivel |
|--------|-------|-------|
| Intento de login fallido (credenciales) | audit | warning |
| Intento de login bloqueado (rate limit) | audit | warning |
| Login exitoso de guest (1FA) | audit | info |
| OTP enviado por email | audit | info |
| Código OTP incorrecto | audit | warning |
| Código OTP expirado | audit | warning |
| Máximo de intentos OTP superado | audit | warning |
| Login exitoso de user (2FA) | audit | info |
| Solicitud de identidad 3FA creada | audit | info |
| Login exitoso de admin (3FA) | audit | info |
| PIN incorrecto | audit | warning |
| PIN no asignado aún | audit | warning |
| Solicitud de identidad aprobada | audit | info |
| Solicitud de identidad rechazada | audit | info |
| Código de identidad no válido | audit | warning |
| Registro de cuenta guest exitoso | audit | info |
| Rate limit en registro alcanzado | audit | warning |
| Cierre de sesión | audit | info |

---

## 5. Estándar de Documentación Utilizado

**Estándar: PHPDoc (PSR-5)**

Todas las funciones públicas del proyecto están documentadas con:
- `@param` → Descripción de cada parámetro de entrada
- `@return` → Tipo y descripción del valor de retorno
- `@throws` → Excepciones que puede lanzar la función
- Descripción breve (primera línea) y detallada (párrafo adicional)

Archivos documentados:
- Controllers: `AuthController`, `TwoFactorController`, `AdminAccessController`, `AdminApprovalController`, `RegisterController`
- Models: `User`, `MfaChallenge`, `AdminAccessRequest`
- Middleware: `SanitizeInput`, `SecureHeaders`, `EnsureUserRole`, `EnsurePrimaryAdmin`
- Notifications: `EmailOtpNotification`, `AdminThirdFactorRequested`
- Actions: `CreateNewUser`, `PasswordValidationRules`
- Providers: `AppServiceProvider`
- Seeders: `UserSeeder`

---

## 6. Validación de Contraseñas (Requisito 2)

Reglas configuradas en `AppServiceProvider::boot()`:

| Regla | Descripción |
|-------|-------------|
| `min(8)` | Mínimo 8 caracteres |
| `mixedCase()` | Al menos una mayúscula y una minúscula |
| `numbers()` | Al menos un número |
| `symbols()` | Al menos un símbolo especial |
| `uncompromised()` | Verificación contra base de datos de contraseñas comprometidas (HIBP) |

La validación se aplica tanto en el registro de guest (`RegisterController`) como en las acciones de Fortify (`CreateNewUser`, `UpdateUserPassword`).

---

## 7. Factores de Autenticación (Requisito 13)

| Rol | Factor 1 | Factor 2 | Factor 3 |
|-----|----------|----------|----------|
| Guest | Credenciales + reCAPTCHA | — | — |
| User | Credenciales + reCAPTCHA | OTP por email (6 dígitos, 10 min) | — |
| Admin (nuevo) | Credenciales + reCAPTCHA | OTP por email | Verificación presencial + PIN (4 dígitos) |
| Admin (verificado) | Credenciales + reCAPTCHA | OTP por email | PIN (4 dígitos permanente) |
