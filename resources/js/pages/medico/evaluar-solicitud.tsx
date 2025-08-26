import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Textarea } from '@/Components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { AlertCircle, User, Building, Calendar, FileText, Heart, Activity, Thermometer, ArrowLeft, Check, X, AlertTriangle } from 'lucide-react';
import { Alert, AlertDescription } from '@/Components/ui/alert';

interface SolicitudMedica {
    id: number;
    email_unique_id: string;
    paciente_nombre: string;
    paciente_apellidos?: string;
    paciente_identificacion?: string;
    paciente_edad?: number;
    paciente_sexo?: string;
    paciente_telefono?: string;
    institucion_remitente: string;
    medico_remitente?: string;
    email_remitente: string;
    telefono_remitente?: string;
    diagnostico_principal: string;
    diagnosticos_secundarios?: string;
    motivo_consulta: string;
    enfermedad_actual?: string;
    antecedentes_medicos?: string;
    medicamentos_actuales?: string;
    frecuencia_cardiaca?: number;
    frecuencia_respiratoria?: number;
    temperatura?: number;
    tension_sistolica?: number;
    tension_diastolica?: number;
    saturacion_oxigeno?: number;
    escala_glasgow?: string;
    especialidad_solicitada: string;
    tipo_solicitud: string;
    motivo_remision: string;
    requerimiento_oxigeno: string;
    tipo_servicio?: string;
    observaciones_adicionales?: string;
    prioridad_ia: 'Alta' | 'Media' | 'Baja';
    score_urgencia?: number;
    criterios_priorizacion?: any;
    estado: string;
    fecha_recepcion_email: string;
    fecha_procesamiento_ia: string;
    texto_extraido?: string;
    medico_evaluador?: {
        id: number;
        name: string;
    };
}

interface EvaluarSolicitudProps {
    solicitudId: number;
}

export default function EvaluarSolicitud({ solicitudId }: EvaluarSolicitudProps) {
    const [solicitud, setSolicitud] = useState<SolicitudMedica | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [evaluacion, setEvaluacion] = useState({
        decision_medica: '',
        observaciones_medico: '',
        prioridad_medica: ''
    });

    useEffect(() => {
        fetchSolicitud();
    }, [solicitudId]);

    const fetchSolicitud = async () => {
        try {
            setLoading(true);
            const response = await fetch(`/api/solicitudes-medicas/${solicitudId}`);
            const data = await response.json();

            if (data.success) {
                setSolicitud(data.data);
                setEvaluacion(prev => ({
                    ...prev,
                    prioridad_medica: data.data.prioridad_ia
                }));
            } else {
                console.error('Error fetching solicitud:', data.message);
            }
        } catch (error) {
            console.error('Error fetching solicitud:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmitEvaluacion = async () => {
        if (!evaluacion.decision_medica || !evaluacion.observaciones_medico) {
            alert('Por favor complete todos los campos requeridos');
            return;
        }

        try {
            setSubmitting(true);
            const response = await fetch(`/api/solicitudes-medicas/${solicitudId}/evaluar`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(evaluacion)
            });

            const data = await response.json();

            if (data.success) {
                alert('Evaluación guardada exitosamente');
                router.visit('/medico/bandeja-casos');
            } else {
                alert('Error al guardar la evaluación: ' + data.message);
            }
        } catch (error) {
            console.error('Error submitting evaluation:', error);
            alert('Error al guardar la evaluación');
        } finally {
            setSubmitting(false);
        }
    };

    const getPriorityColor = (prioridad: string) => {
        switch (prioridad) {
            case 'Alta': return 'bg-red-100 text-red-800 border-red-200';
            case 'Media': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
            case 'Baja': return 'bg-green-100 text-green-800 border-green-200';
            default: return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    if (loading) {
        return (
            <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Cargando...</h2>}>
                <Head title="Evaluando Solicitud" />
                <div className="py-6">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div className="flex justify-center items-center py-8">
                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    if (!solicitud) {
        return (
            <AuthenticatedLayout header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Solicitud no encontrada</h2>}>
                <Head title="Solicitud no encontrada" />
                <div className="py-6">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <Alert>
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>
                                No se pudo encontrar la solicitud médica solicitada.
                            </AlertDescription>
                        </Alert>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" onClick={() => router.visit('/medico/bandeja-casos')}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Button>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Evaluar Solicitud Médica
                        </h2>
                    </div>
                    <Badge className={getPriorityColor(solicitud.prioridad_ia)}>
                        {solicitud.prioridad_ia === 'Alta' && <AlertCircle className="h-3 w-3 mr-1" />}
                        Prioridad {solicitud.prioridad_ia}
                    </Badge>
                </div>
            }
        >
            <Head title={`Evaluar Solicitud - ${solicitud.paciente_nombre}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Patient Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <User className="h-5 w-5" />
                                Información del Paciente
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Nombre Completo</label>
                                    <p className="mt-1 text-sm text-gray-900">
                                        {solicitud.paciente_nombre} {solicitud.paciente_apellidos}
                                    </p>
                                </div>
                                {solicitud.paciente_identificacion && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Identificación</label>
                                        <p className="mt-1 text-sm text-gray-900">{solicitud.paciente_identificacion}</p>
                                    </div>
                                )}
                                {solicitud.paciente_edad && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Edad</label>
                                        <p className="mt-1 text-sm text-gray-900">{solicitud.paciente_edad} años</p>
                                    </div>
                                )}
                                {solicitud.paciente_sexo && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Sexo</label>
                                        <p className="mt-1 text-sm text-gray-900">{solicitud.paciente_sexo}</p>
                                    </div>
                                )}
                                {solicitud.paciente_telefono && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Teléfono</label>
                                        <p className="mt-1 text-sm text-gray-900">{solicitud.paciente_telefono}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Institution Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building className="h-5 w-5" />
                                Información de la Institución Remitente
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Institución</label>
                                    <p className="mt-1 text-sm text-gray-900">{solicitud.institucion_remitente}</p>
                                </div>
                                {solicitud.medico_remitente && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Médico Remitente</label>
                                        <p className="mt-1 text-sm text-gray-900">{solicitud.medico_remitente}</p>
                                    </div>
                                )}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Email</label>
                                    <p className="mt-1 text-sm text-gray-900">{solicitud.email_remitente}</p>
                                </div>
                                {solicitud.telefono_remitente && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Teléfono</label>
                                        <p className="mt-1 text-sm text-gray-900">{solicitud.telefono_remitente}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Clinical Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Información Clínica
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Diagnóstico Principal</label>
                                    <p className="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">{solicitud.diagnostico_principal}</p>
                                </div>
                                {solicitud.diagnosticos_secundarios && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Diagnósticos Secundarios</label>
                                        <p className="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">{solicitud.diagnosticos_secundarios}</p>
                                    </div>
                                )}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Motivo de Consulta</label>
                                    <p className="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">{solicitud.motivo_consulta}</p>
                                </div>
                                {solicitud.enfermedad_actual && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Enfermedad Actual</label>
                                        <p className="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">{solicitud.enfermedad_actual}</p>
                                    </div>
                                )}
                                {solicitud.antecedentes_medicos && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Antecedentes Médicos</label>
                                        <p className="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">{solicitud.antecedentes_medicos}</p>
                                    </div>
                                )}
                                {solicitud.medicamentos_actuales && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">Medicamentos Actuales</label>
                                        <p className="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">{solicitud.medicamentos_actuales}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Vital Signs */}
                    {(solicitud.frecuencia_cardiaca || solicitud.frecuencia_respiratoria || solicitud.temperatura || 
                      solicitud.tension_sistolica || solicitud.saturacion_oxigeno) && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Activity className="h-5 w-5" />
                                    Signos Vitales
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                    {solicitud.frecuencia_cardiaca && (
                                        <div className="text-center p-3 bg-blue-50 rounded">
                                            <Heart className="h-6 w-6 mx-auto mb-1 text-blue-600" />
                                            <div className="text-lg font-semibold">{solicitud.frecuencia_cardiaca}</div>
                                            <div className="text-xs text-gray-600">FC (lpm)</div>
                                        </div>
                                    )}
                                    {solicitud.frecuencia_respiratoria && (
                                        <div className="text-center p-3 bg-green-50 rounded">
                                            <Activity className="h-6 w-6 mx-auto mb-1 text-green-600" />
                                            <div className="text-lg font-semibold">{solicitud.frecuencia_respiratoria}</div>
                                            <div className="text-xs text-gray-600">FR (rpm)</div>
                                        </div>
                                    )}
                                    {solicitud.temperatura && (
                                        <div className="text-center p-3 bg-red-50 rounded">
                                            <Thermometer className="h-6 w-6 mx-auto mb-1 text-red-600" />
                                            <div className="text-lg font-semibold">{solicitud.temperatura}°C</div>
                                            <div className="text-xs text-gray-600">Temperatura</div>
                                        </div>
                                    )}
                                    {(solicitud.tension_sistolica && solicitud.tension_diastolica) && (
                                        <div className="text-center p-3 bg-purple-50 rounded">
                                            <div className="text-lg font-semibold">{solicitud.tension_sistolica}/{solicitud.tension_diastolica}</div>
                                            <div className="text-xs text-gray-600">TA (mmHg)</div>
                                        </div>
                                    )}
                                    {solicitud.saturacion_oxigeno && (
                                        <div className="text-center p-3 bg-cyan-50 rounded">
                                            <div className="text-lg font-semibold">{solicitud.saturacion_oxigeno}%</div>
                                            <div className="text-xs text-gray-600">SatO2</div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Request Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de la Solicitud</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Especialidad Solicitada</label>
                                    <p className="mt-1 text-sm text-gray-900">{solicitud.especialidad_solicitada}</p>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Tipo de Solicitud</label>
                                    <p className="mt-1 text-sm text-gray-900">{solicitud.tipo_solicitud}</p>
                                </div>
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700">Motivo de Remisión</label>
                                    <p className="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">{solicitud.motivo_remision}</p>
                                </div>
                                {solicitud.observaciones_adicionales && (
                                    <div className="md:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700">Observaciones Adicionales</label>
                                        <p className="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">{solicitud.observaciones_adicionales}</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Evaluation Form */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <AlertTriangle className="h-5 w-5" />
                                Evaluación Médica
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Decisión Médica *</label>
                                    <Select value={evaluacion.decision_medica} onValueChange={(value) => setEvaluacion(prev => ({ ...prev, decision_medica: value }))}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione una decisión" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="aceptar">
                                                <div className="flex items-center gap-2">
                                                    <Check className="h-4 w-4 text-green-600" />
                                                    Aceptar Solicitud
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="rechazar">
                                                <div className="flex items-center gap-2">
                                                    <X className="h-4 w-4 text-red-600" />
                                                    Rechazar Solicitud
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="solicitar_info">
                                                <div className="flex items-center gap-2">
                                                    <AlertTriangle className="h-4 w-4 text-yellow-600" />
                                                    Solicitar Información Adicional
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Prioridad Médica</label>
                                    <Select value={evaluacion.prioridad_medica} onValueChange={(value) => setEvaluacion(prev => ({ ...prev, prioridad_medica: value }))}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione prioridad" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Alta">Alta</SelectItem>
                                            <SelectItem value="Media">Media</SelectItem>
                                            <SelectItem value="Baja">Baja</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">Observaciones del Médico Evaluador *</label>
                                    <Textarea
                                        placeholder="Ingrese sus observaciones sobre la evaluación de esta solicitud..."
                                        value={evaluacion.observaciones_medico}
                                        onChange={(e) => setEvaluacion(prev => ({ ...prev, observaciones_medico: e.target.value }))}
                                        rows={4}
                                    />
                                </div>

                                <div className="flex justify-end gap-4 pt-4">
                                    <Button variant="outline" onClick={() => router.visit('/medico/bandeja-casos')}>
                                        Cancelar
                                    </Button>
                                    <Button 
                                        onClick={handleSubmitEvaluacion}
                                        disabled={submitting || !evaluacion.decision_medica || !evaluacion.observaciones_medico}
                                    >
                                        {submitting ? 'Guardando...' : 'Guardar Evaluación'}
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Processing Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-5 w-5" />
                                Información de Procesamiento
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label className="block font-medium text-gray-700">Fecha de Recepción</label>
                                    <p className="text-gray-900">{formatDate(solicitud.fecha_recepcion_email)}</p>
                                </div>
                                <div>
                                    <label className="block font-medium text-gray-700">Procesado por IA</label>
                                    <p className="text-gray-900">{formatDate(solicitud.fecha_procesamiento_ia)}</p>
                                </div>
                                <div>
                                    <label className="block font-medium text-gray-700">ID Único</label>
                                    <p className="text-gray-900 font-mono text-xs">{solicitud.email_unique_id}</p>
                                </div>
                                {solicitud.score_urgencia && (
                                    <div>
                                        <label className="block font-medium text-gray-700">Score de Urgencia (IA)</label>
                                        <p className="text-gray-900">{solicitud.score_urgencia}/100</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
