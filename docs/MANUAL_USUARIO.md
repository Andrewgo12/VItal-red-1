# Manual de Usuario - Sistema Vital Red

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Acceso al Sistema](#acceso-al-sistema)
3. [Interfaz Principal](#interfaz-principal)
4. [Módulo Médico Evaluador](#módulo-médico-evaluador)
5. [Módulo Administrativo](#módulo-administrativo)
6. [Notificaciones](#notificaciones)
7. [Reportes](#reportes)
8. [Preguntas Frecuentes](#preguntas-frecuentes)

## Introducción

### ¿Qué es Vital Red?

Vital Red es un sistema inteligente diseñado para automatizar y optimizar el proceso de evaluación de solicitudes de referencia y contra-referencia médica en hospitales. El sistema utiliza inteligencia artificial para:

- **Procesar automáticamente** correos electrónicos con solicitudes médicas
- **Clasificar por prioridad** los casos según criterios médicos
- **Facilitar la evaluación** por parte de médicos especialistas
- **Generar notificaciones** automáticas para casos urgentes
- **Proporcionar métricas** y reportes del sistema

### Beneficios del Sistema

✅ **Reducción de tiempos de respuesta** de horas a minutos  
✅ **Clasificación automática** de prioridades médicas  
✅ **Trazabilidad completa** de todas las decisiones  
✅ **Notificaciones en tiempo real** para casos urgentes  
✅ **Reportes detallados** para análisis y mejora continua  

## Acceso al Sistema

### Inicio de Sesión

1. **Abrir navegador web** y dirigirse a la URL del sistema
2. **Ingresar credenciales**:
   - Email institucional
   - Contraseña asignada
3. **Hacer clic en "Iniciar Sesión"**

![Login Screen](images/login-screen.png)

### Tipos de Usuario

#### 👨‍⚕️ Médico Evaluador
- Acceso a bandeja de casos médicos
- Evaluación y toma de decisiones
- Visualización de historiales
- Reportes básicos

#### 👨‍💼 Administrador
- Acceso completo al sistema
- Gestión de usuarios
- Configuración del sistema
- Reportes avanzados y métricas

### Recuperación de Contraseña

1. En la pantalla de login, hacer clic en **"¿Olvidó su contraseña?"**
2. Ingresar su email institucional
3. Revisar su correo para el enlace de recuperación
4. Seguir las instrucciones del email

## Interfaz Principal

### Navegación General

```
┌─────────────────────────────────────────────────────────┐
│ [Logo] Vital Red    [Notificaciones] [Usuario] [Salir] │
├─────────────────────────────────────────────────────────┤
│ [Dashboard] [Casos] [Reportes] [Configuración]         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│                 CONTENIDO PRINCIPAL                     │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Elementos de la Interfaz

#### Barra Superior
- **Logo y nombre del sistema**
- **Indicador de notificaciones** (🔔 con contador)
- **Menú de usuario** (nombre, configuración, cerrar sesión)

#### Menú Principal
- **Dashboard**: Resumen y métricas principales
- **Casos Médicos**: Bandeja de solicitudes
- **Reportes**: Análisis y estadísticas
- **Configuración**: Ajustes del sistema (solo admin)

## Módulo Médico Evaluador

### Bandeja de Casos Médicos

#### Vista Principal

La bandeja muestra todas las solicitudes médicas pendientes y evaluadas:

```
┌─────────────────────────────────────────────────────────┐
│ 🔍 Buscar: [_____________] 🔽 Filtros                   │
├─────────────────────────────────────────────────────────┤
│ Paciente    │ Especialidad │ Prioridad │ Estado │ Acción│
├─────────────────────────────────────────────────────────┤
│ Juan Pérez  │ Cardiología  │ 🔴 Alta   │ Pend.  │ [Ver] │
│ Ana García  │ Neurología   │ 🟡 Media  │ Eval.  │ [Ver] │
│ Luis Martín │ Ortopedia    │ 🟢 Baja   │ Acept. │ [Ver] │
└─────────────────────────────────────────────────────────┘
```

#### Códigos de Color por Prioridad
- 🔴 **Rojo (Alta)**: Casos urgentes que requieren atención inmediata
- 🟡 **Amarillo (Media)**: Casos importantes con prioridad moderada  
- 🟢 **Verde (Baja)**: Casos de rutina sin urgencia

#### Estados de Solicitudes
- **Pendiente**: Esperando evaluación médica
- **En Evaluación**: Siendo revisada por un médico
- **Evaluada**: Decisión tomada, pendiente de notificación
- **Aceptada**: Caso aceptado para traslado/atención
- **Rechazada**: Caso no cumple criterios

### Filtros y Búsqueda

#### Panel de Filtros
Hacer clic en **"🔽 Filtros"** para mostrar opciones:

- **Por Prioridad**: Alta, Media, Baja
- **Por Estado**: Pendiente, Evaluada, Aceptada, Rechazada
- **Por Especialidad**: Cardiología, Neurología, etc.
- **Por Fecha**: Rango de fechas de recepción
- **Por Institución**: Hospital o clínica remitente

#### Búsqueda Rápida
- Escribir en el campo de búsqueda
- Busca por: nombre del paciente, diagnóstico, institución
- Resultados en tiempo real mientras escribe

### Evaluación de Casos

#### Abrir Caso para Evaluación

1. **Hacer clic en "Ver"** en la fila del caso
2. Se abre la **pantalla de evaluación médica**

#### Pantalla de Evaluación

```
┌─────────────────────────────────────────────────────────┐
│                    EVALUACIÓN MÉDICA                    │
├─────────────────────────────────────────────────────────┤
│ 👤 INFORMACIÓN DEL PACIENTE                            │
│ Nombre: Juan Pérez García                               │
│ Edad: 45 años    Sexo: Masculino                       │
│ Identificación: 12345678                                │
├─────────────────────────────────────────────────────────┤
│ 🏥 INFORMACIÓN MÉDICA                                   │
│ Especialidad: Cardiología                               │
│ Diagnóstico: Dolor torácico agudo                       │
│ Motivo: Sospecha de síndrome coronario agudo            │
│ Institución: Hospital San Juan                          │
├─────────────────────────────────────────────────────────┤
│ 📊 SIGNOS VITALES                                       │
│ FC: 120 lpm    TA: 140/90 mmHg    Temp: 37.2°C        │
│ FR: 22 rpm     SpO2: 95%                               │
├─────────────────────────────────────────────────────────┤
│ 🤖 ANÁLISIS DE IA                                       │
│ Prioridad Sugerida: Alta (Score: 85/100)               │
│ Criterios: Síntomas cardíacos agudos, signos vitales   │
│ alterados, edad de riesgo                               │
├─────────────────────────────────────────────────────────┤
│ ⚕️ DECISIÓN MÉDICA                                      │
│ Decisión: [Aceptar ▼] [Rechazar] [Solicitar Info]      │
│ Observaciones: [________________________]              │
│ Prioridad Final: [Alta ▼]                              │
│ Fecha Programada: [____/__/____ __:__]                 │
│ Servicio Destino: [Urgencias ▼]                        │
│                                                         │
│ [Guardar Evaluación] [Cancelar]                        │
└─────────────────────────────────────────────────────────┘
```

#### Opciones de Decisión

**🟢 Aceptar**
- Caso cumple criterios para traslado/atención
- Requiere: observaciones, prioridad final, fecha programada
- Genera notificación automática al hospital remitente

**🔴 Rechazar**  
- Caso no cumple criterios establecidos
- Requiere: motivo del rechazo, observaciones detalladas
- Genera notificación de rechazo con justificación

**ℹ️ Solicitar Información**
- Requiere información adicional para decidir
- Especificar qué información se necesita
- Caso queda en estado "pendiente información"

#### Campos Obligatorios

- **Observaciones Médicas**: Justificación detallada de la decisión
- **Prioridad Final**: Confirmación o ajuste de la prioridad IA
- **Fecha Programada**: Solo para casos aceptados
- **Servicio Destino**: Área que recibirá al paciente

### Historial de Casos

#### Acceder al Historial
- Menú **"Casos"** → **"Historial"**
- Ver todos los casos evaluados previamente
- Filtrar por fechas, decisiones, especialidades

#### Información Disponible
- Fecha y hora de evaluación
- Decisión tomada
- Observaciones registradas
- Tiempo de respuesta
- Estado actual del caso

## Módulo Administrativo

### Dashboard Administrativo

#### Métricas Principales

```
┌─────────────────────────────────────────────────────────┐
│ 📊 RESUMEN EJECUTIVO                                    │
├─────────────────────────────────────────────────────────┤
│ [📈 Total Solicitudes] [🚨 Casos Urgentes]             │
│      1,247                    23                        │
│                                                         │
│ [✅ Tasa Aceptación]  [⏱️ Tiempo Promedio]             │
│      78.5%                   2.3 horas                 │
├─────────────────────────────────────────────────────────┤
│ 📈 GRÁFICOS                                             │
│ [Actividad Diaria] [Por Especialidad] [Tendencias]     │
└─────────────────────────────────────────────────────────┘
```

#### Indicadores Clave (KPIs)
- **Total de Solicitudes**: Volumen general del sistema
- **Casos Urgentes Pendientes**: Requieren atención inmediata
- **Tasa de Aceptación**: Porcentaje de casos aceptados
- **Tiempo Promedio de Respuesta**: Eficiencia del sistema
- **Cumplimiento SLA**: Casos resueltos dentro del tiempo objetivo

### Gestión de Usuarios

#### Lista de Usuarios

```
┌─────────────────────────────────────────────────────────┐
│ 👥 GESTIÓN DE USUARIOS                                  │
├─────────────────────────────────────────────────────────┤
│ Nombre        │ Email           │ Rol    │ Estado │ Acc. │
├─────────────────────────────────────────────────────────┤
│ Dr. García    │ garcia@hosp.com │ Médico │ Activo │ [⚙️] │
│ Dra. López    │ lopez@hosp.com  │ Médico │ Activo │ [⚙️] │
│ Admin Pérez   │ admin@hosp.com  │ Admin  │ Activo │ [⚙️] │
├─────────────────────────────────────────────────────────┤
│ [+ Nuevo Usuario] [📤 Exportar] [🔄 Actualizar]        │
└─────────────────────────────────────────────────────────┘
```

#### Crear Nuevo Usuario

1. **Hacer clic en "+ Nuevo Usuario"**
2. **Completar formulario**:
   - Nombre completo
   - Email institucional
   - Rol (Médico/Administrador)
   - Departamento
   - Especialidades (para médicos)
   - Licencia médica
3. **Guardar**: Se envía email con credenciales

#### Gestionar Usuario Existente

- **⚙️ Configurar**: Editar información, cambiar rol, desactivar
- **📊 Reportes**: Ver actividad y estadísticas del usuario
- **🔄 Resetear Contraseña**: Generar nueva contraseña

### Configuración del Sistema

#### Configuración de Gmail

```
┌─────────────────────────────────────────────────────────┐
│ 📧 CONFIGURACIÓN GMAIL                                  │
├─────────────────────────────────────────────────────────┤
│ Email: [solicitudes@hospital.com]                       │
│ App Password: [****************]                        │
│ Servidor IMAP: [imap.gmail.com]                        │
│ Puerto: [993]                                           │
│ Intervalo Verificación: [5] minutos                     │
│ Máx. Emails por Verificación: [50]                     │
│                                                         │
│ [Probar Conexión] [Guardar Configuración]              │
└─────────────────────────────────────────────────────────┘
```

#### Configuración de IA

```
┌─────────────────────────────────────────────────────────┐
│ 🤖 CONFIGURACIÓN INTELIGENCIA ARTIFICIAL               │
├─────────────────────────────────────────────────────────┤
│ APIs Gemini Configuradas: [3]                          │
│ Umbral Confianza: [0.7] (70%)                         │
│ ☑️ Análisis Semántico Avanzado                         │
│ ☑️ Clasificación de Prioridades                        │
│ ☑️ Extracción de Entidades Médicas                     │
│                                                         │
│ [Probar IA] [Guardar Configuración]                    │
└─────────────────────────────────────────────────────────┘
```

#### Configuración de Notificaciones

- **Email**: Configurar SMTP para notificaciones
- **Tiempo Real**: Activar/desactivar notificaciones push
- **Escalamiento**: Configurar alertas para casos urgentes
- **Destinatarios**: Lista de emails para alertas del sistema

## Notificaciones

### Tipos de Notificaciones

#### 🚨 Casos Urgentes
- **Cuándo**: Se detecta caso con prioridad "Alta"
- **Destinatarios**: Todos los médicos evaluadores activos
- **Contenido**: Información básica del paciente y motivo

#### ✅ Evaluaciones Completadas
- **Cuándo**: Médico completa evaluación de caso
- **Destinatarios**: Hospital remitente, administradores
- **Contenido**: Decisión tomada, observaciones, próximos pasos

#### ⚠️ Alertas del Sistema
- **Cuándo**: Errores, mantenimiento, alto volumen
- **Destinatarios**: Administradores técnicos
- **Contenido**: Descripción del problema, acciones requeridas

### Panel de Notificaciones

#### Acceder a Notificaciones
- **Hacer clic en el ícono 🔔** en la barra superior
- **Contador** muestra notificaciones no leídas

#### Gestionar Notificaciones
- **Marcar como leída**: Clic en la notificación
- **Eliminar**: Botón de eliminar individual
- **Limpiar todas**: Botón para eliminar todas las leídas

## Reportes

### Tipos de Reportes Disponibles

#### 📋 Reporte de Solicitudes Médicas
- **Contenido**: Volumen, distribución por especialidad, tiempos
- **Filtros**: Fecha, especialidad, prioridad, estado
- **Formatos**: PDF, Excel, CSV

#### 📊 Reporte de Rendimiento
- **Contenido**: Tiempos de respuesta, cumplimiento SLA, eficiencia
- **Métricas**: Por médico, por especialidad, tendencias temporales
- **Gráficos**: Líneas de tiempo, barras comparativas

#### 🔍 Reporte de Auditoría
- **Contenido**: Todas las acciones del sistema
- **Filtros**: Usuario, acción, fecha, recurso
- **Seguridad**: Solo administradores, logs inmutables

### Generar Reportes

#### Proceso Paso a Paso

1. **Ir a "Reportes"** en el menú principal
2. **Seleccionar tipo** de reporte deseado
3. **Configurar filtros**:
   - Rango de fechas
   - Especialidades específicas
   - Usuarios (si aplica)
4. **Elegir formato** de exportación
5. **Hacer clic en "Generar Reporte"**
6. **Descargar** cuando esté listo

#### Programar Reportes Automáticos

- **Frecuencia**: Diario, semanal, mensual
- **Destinatarios**: Lista de emails
- **Formato**: PDF o Excel
- **Configuración**: Filtros predefinidos

## Preguntas Frecuentes

### ❓ Uso General

**P: ¿Cómo cambio mi contraseña?**  
R: Menú usuario → Configuración → Cambiar contraseña

**P: ¿Por qué no veo algunos casos?**  
R: Verifique los filtros activos. Algunos casos pueden estar filtrados por especialidad o estado.

**P: ¿Cómo sé si hay casos urgentes?**  
R: El sistema muestra notificaciones rojas y envía alertas automáticas por email.

### ❓ Evaluación Médica

**P: ¿Puedo modificar una evaluación ya guardada?**  
R: No, por trazabilidad. Contacte al administrador si hay un error crítico.

**P: ¿Qué pasa si no estoy seguro de la decisión?**  
R: Use "Solicitar Información" para pedir datos adicionales al hospital remitente.

**P: ¿Cuánto tiempo tengo para evaluar un caso?**  
R: Casos urgentes: 2 horas, casos medios: 24 horas, casos bajos: 72 horas.

### ❓ Problemas Técnicos

**P: El sistema está lento, ¿qué hago?**  
R: Actualice la página. Si persiste, contacte soporte técnico.

**P: No recibo notificaciones por email**  
R: Verifique spam/correo no deseado. Contacte administrador para verificar configuración.

**P: Error al guardar evaluación**  
R: Verifique conexión a internet. Asegúrese de completar todos los campos obligatorios.

### 📞 Contacto y Soporte

- **Soporte Técnico**: soporte@vitalred.com
- **Teléfono**: +57 (1) 234-5678
- **Horario**: Lunes a Viernes, 7:00 AM - 7:00 PM
- **Emergencias**: 24/7 para casos críticos del sistema

---

**Versión del Manual**: 1.0.0  
**Última Actualización**: 2024-01-15  
**Sistema**: Vital Red v1.0
