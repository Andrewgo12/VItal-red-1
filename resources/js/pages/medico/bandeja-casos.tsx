import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { AlertCircle, Clock, User, Building, Calendar, Search, Filter, Eye } from 'lucide-react';
import { router } from '@inertiajs/react';

interface SolicitudMedica {
    id: number;
    email_unique_id: string;
    paciente_nombre: string;
    paciente_apellidos?: string;
    paciente_identificacion?: string;
    institucion_remitente: string;
    email_remitente: string;
    diagnostico_principal: string;
    motivo_consulta: string;
    especialidad_solicitada: string;
    tipo_solicitud: string;
    motivo_remision: string;
    prioridad_ia: 'Alta' | 'Media' | 'Baja';
    score_urgencia?: number;
    estado: 'recibida' | 'en_revision' | 'aceptada' | 'rechazada' | 'pendiente_info' | 'completada';
    fecha_recepcion_email: string;
    fecha_procesamiento_ia: string;
    tiempo_transcurrido?: string;
    medico_evaluador?: {
        id: number;
        name: string;
    };
}

interface PaginatedResponse {
    data: SolicitudMedica[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export default function BandejaCasos() {
    const [solicitudes, setSolicitudes] = useState<PaginatedResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        estado: '',
        prioridad: '',
        especialidad: '',
        institucion: '',
        buscar: '',
        fecha_desde: '',
        fecha_hasta: ''
    });
    const [currentPage, setCurrentPage] = useState(1);

    useEffect(() => {
        fetchSolicitudes();
    }, [filters, currentPage]);

    const fetchSolicitudes = async () => {
        try {
            setLoading(true);
            const params = new URLSearchParams({
                page: currentPage.toString(),
                per_page: '15',
                ...Object.fromEntries(Object.entries(filters).filter(([_, v]) => v !== ''))
            });

            const response = await fetch(`/api/solicitudes-medicas?${params}`);
            const data = await response.json();

            if (data.success) {
                setSolicitudes(data.data);
            } else {
                console.error('Error fetching solicitudes:', data.message);
            }
        } catch (error) {
            console.error('Error fetching solicitudes:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilterChange = (key: string, value: string) => {
        setFilters(prev => ({ ...prev, [key]: value }));
        setCurrentPage(1);
    };

    const clearFilters = () => {
        setFilters({
            estado: '',
            prioridad: '',
            especialidad: '',
            institucion: '',
            buscar: '',
            fecha_desde: '',
            fecha_hasta: ''
        });
        setCurrentPage(1);
    };

    const getPriorityColor = (prioridad: string) => {
        switch (prioridad) {
            case 'Alta': return 'bg-red-100 text-red-800 border-red-200';
            case 'Media': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
            case 'Baja': return 'bg-green-100 text-green-800 border-green-200';
            default: return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    const getStatusColor = (estado: string) => {
        switch (estado) {
            case 'recibida': return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'en_revision': return 'bg-orange-100 text-orange-800 border-orange-200';
            case 'aceptada': return 'bg-green-100 text-green-800 border-green-200';
            case 'rechazada': return 'bg-red-100 text-red-800 border-red-200';
            case 'pendiente_info': return 'bg-purple-100 text-purple-800 border-purple-200';
            case 'completada': return 'bg-gray-100 text-gray-800 border-gray-200';
            default: return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    const getStatusText = (estado: string) => {
        switch (estado) {
            case 'recibida': return 'Recibida';
            case 'en_revision': return 'En Revisión';
            case 'aceptada': return 'Aceptada';
            case 'rechazada': return 'Rechazada';
            case 'pendiente_info': return 'Pendiente Info';
            case 'completada': return 'Completada';
            default: return estado;
        }
    };

    const handleEvaluarSolicitud = (id: number) => {
        router.visit(`/medico/evaluar-solicitud/${id}`);
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

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Bandeja de Casos Médicos
                    </h2>
                    <div className="flex items-center space-x-2">
                        <Badge variant="outline" className="bg-blue-50">
                            Total: {solicitudes?.total || 0}
                        </Badge>
                        <Badge variant="outline" className="bg-red-50">
                            Urgentes: {solicitudes?.data?.filter(s => s.prioridad_ia === 'Alta').length || 0}
                        </Badge>
                    </div>
                </div>
            }
        >
            <Head title="Bandeja de Casos Médicos" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Filters */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Filter className="h-5 w-5" />
                                Filtros de Búsqueda
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-sm font-medium mb-1">Buscar Paciente</label>
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <Input
                                            placeholder="Nombre, apellido o ID..."
                                            value={filters.buscar}
                                            onChange={(e) => handleFilterChange('buscar', e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium mb-1">Estado</label>
                                    <Select value={filters.estado} onValueChange={(value) => handleFilterChange('estado', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todos los estados" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Todos los estados</SelectItem>
                                            <SelectItem value="recibida">Recibida</SelectItem>
                                            <SelectItem value="en_revision">En Revisión</SelectItem>
                                            <SelectItem value="aceptada">Aceptada</SelectItem>
                                            <SelectItem value="rechazada">Rechazada</SelectItem>
                                            <SelectItem value="pendiente_info">Pendiente Info</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium mb-1">Prioridad</label>
                                    <Select value={filters.prioridad} onValueChange={(value) => handleFilterChange('prioridad', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todas las prioridades" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Todas las prioridades</SelectItem>
                                            <SelectItem value="Alta">Alta</SelectItem>
                                            <SelectItem value="Media">Media</SelectItem>
                                            <SelectItem value="Baja">Baja</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium mb-1">Especialidad</label>
                                    <Input
                                        placeholder="Especialidad..."
                                        value={filters.especialidad}
                                        onChange={(e) => handleFilterChange('especialidad', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="flex justify-between items-center mt-4">
                                <Button variant="outline" onClick={clearFilters}>
                                    Limpiar Filtros
                                </Button>
                                <Button onClick={fetchSolicitudes} disabled={loading}>
                                    {loading ? 'Cargando...' : 'Actualizar'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Results Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Solicitudes Médicas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {loading ? (
                                <div className="flex justify-center items-center py-8">
                                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                </div>
                            ) : solicitudes?.data?.length ? (
                                <>
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Paciente</TableHead>
                                                    <TableHead>Institución</TableHead>
                                                    <TableHead>Especialidad</TableHead>
                                                    <TableHead>Prioridad</TableHead>
                                                    <TableHead>Estado</TableHead>
                                                    <TableHead>Fecha Recepción</TableHead>
                                                    <TableHead>Acciones</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {solicitudes.data.map((solicitud) => (
                                                    <TableRow key={solicitud.id} className={solicitud.prioridad_ia === 'Alta' ? 'bg-red-50' : ''}>
                                                        <TableCell>
                                                            <div>
                                                                <div className="font-medium">
                                                                    {solicitud.paciente_nombre} {solicitud.paciente_apellidos}
                                                                </div>
                                                                {solicitud.paciente_identificacion && (
                                                                    <div className="text-sm text-gray-500">
                                                                        ID: {solicitud.paciente_identificacion}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex items-center gap-1">
                                                                <Building className="h-4 w-4 text-gray-400" />
                                                                <span className="text-sm">{solicitud.institucion_remitente}</span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge variant="outline">
                                                                {solicitud.especialidad_solicitada}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge className={getPriorityColor(solicitud.prioridad_ia)}>
                                                                {solicitud.prioridad_ia === 'Alta' && <AlertCircle className="h-3 w-3 mr-1" />}
                                                                {solicitud.prioridad_ia}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge className={getStatusColor(solicitud.estado)}>
                                                                {getStatusText(solicitud.estado)}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex items-center gap-1">
                                                                <Calendar className="h-4 w-4 text-gray-400" />
                                                                <span className="text-sm">{formatDate(solicitud.fecha_recepcion_email)}</span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => handleEvaluarSolicitud(solicitud.id)}
                                                                className="flex items-center gap-1"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                                Evaluar
                                                            </Button>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>

                                    {/* Pagination */}
                                    {solicitudes.last_page > 1 && (
                                        <div className="flex justify-between items-center mt-4">
                                            <div className="text-sm text-gray-500">
                                                Mostrando {((solicitudes.current_page - 1) * solicitudes.per_page) + 1} a{' '}
                                                {Math.min(solicitudes.current_page * solicitudes.per_page, solicitudes.total)} de{' '}
                                                {solicitudes.total} resultados
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={solicitudes.current_page === 1}
                                                    onClick={() => setCurrentPage(prev => prev - 1)}
                                                >
                                                    Anterior
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    disabled={solicitudes.current_page === solicitudes.last_page}
                                                    onClick={() => setCurrentPage(prev => prev + 1)}
                                                >
                                                    Siguiente
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </>
                            ) : (
                                <div className="text-center py-8">
                                    <div className="text-gray-500">No se encontraron solicitudes médicas</div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
