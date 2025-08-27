@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            Gestión de Usuarios
                        </h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" onclick="createUser()">
                                <i class="fas fa-plus me-1"></i>
                                Nuevo Usuario
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="exportUsers()">
                                <i class="fas fa-download me-1"></i>
                                Exportar
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="showFilters()">
                                <i class="fas fa-filter me-1"></i>
                                Filtros
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistics Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1">{{ $users->total() }}</h3>
                                    <small>Total Usuarios</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1">{{ $users->where('is_active', true)->count() }}</h3>
                                    <small>Usuarios Activos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1">{{ $users->where('role', 'medico')->count() }}</h3>
                                    <small>Médicos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-1">{{ $users->where('role', 'administrador')->count() }}</h3>
                                    <small>Administradores</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters Panel -->
                    <div class="card mb-3" id="filters-panel" style="display: none;">
                        <div class="card-body">
                            <form method="GET" action="{{ route('admin.users.index') }}">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label">Rol</label>
                                        <select class="form-select" name="role">
                                            <option value="">Todos</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                                    {{ ucfirst($role) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Departamento</label>
                                        <select class="form-select" name="department">
                                            <option value="">Todos</option>
                                            @foreach($departments as $department)
                                                <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>
                                                    {{ $department }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="status">
                                            <option value="">Todos</option>
                                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activos</option>
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivos</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Buscar</label>
                                        <input type="text" class="form-control" name="search" placeholder="Nombre, email..." value="{{ request('search') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-1"></i>Filtrar
                                            </button>
                                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-1"></i>Limpiar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Departamento</th>
                                    <th>Especialidades</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <strong>{{ $user->name }}</strong><br>
                                                <small class="text-muted">{{ $user->email }}</small>
                                                @if($user->phone)
                                                    <br><small class="text-muted"><i class="fas fa-phone me-1"></i>{{ $user->phone }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $user->role === 'administrador' ? 'danger' : 'primary' }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                        @if($user->medical_license)
                                            <br><small class="text-muted">Lic: {{ $user->medical_license }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $user->department ?? 'No asignado' }}
                                    </td>
                                    <td>
                                        @if($user->specialties && count($user->specialties) > 0)
                                            @foreach(array_slice($user->specialties, 0, 2) as $specialty)
                                                <span class="badge bg-light text-dark me-1">{{ $specialty }}</span>
                                            @endforeach
                                            @if(count($user->specialties) > 2)
                                                <small class="text-muted">+{{ count($user->specialties) - 2 }} más</small>
                                            @endif
                                        @else
                                            <span class="text-muted">No asignadas</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="status-indicator bg-{{ $user->status_color }} me-2"></span>
                                            <span class="badge bg-{{ $user->is_active ? 'success' : 'secondary' }}">
                                                {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </div>
                                        @if($user->isOnline())
                                            <small class="text-success"><i class="fas fa-circle me-1"></i>En línea</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->last_login_at)
                                            <small>{{ $user->last_login_at->format('d/m/Y') }}</small><br>
                                            <small class="text-muted">{{ $user->last_login_at->format('H:i') }}</small>
                                        @else
                                            <span class="text-muted">Nunca</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewUser({{ $user->id }})" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editUser({{ $user->id }})" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleUserStatus({{ $user->id }}, {{ $user->is_active ? 'false' : 'true' }})" title="{{ $user->is_active ? 'Desactivar' : 'Activar' }}">
                                                <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info" onclick="resetPassword({{ $user->id }})" title="Restablecer Contraseña">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            @if($user->id !== Auth::id())
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser({{ $user->id }})" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <small class="text-muted">
                                Mostrando {{ $users->firstItem() ?? 0 }} a {{ $users->lastItem() ?? 0 }} de {{ $users->total() }} usuarios
                            </small>
                        </div>
                        <div>
                            {{ $users->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userModalContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="saveUserBtn" style="display: none;">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Password Reset Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restablecer Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="passwordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" name="new_password" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" name="new_password_confirmation" required minlength="8">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Restablecer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
</style>
@endsection

@push('scripts')
<script>
let currentUserId = null;

function showFilters() {
    const panel = document.getElementById('filters-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function createUser() {
    currentUserId = null;
    document.getElementById('userModalTitle').textContent = 'Crear Usuario';
    document.getElementById('saveUserBtn').style.display = 'block';
    
    const content = `
        <form id="userForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" class="form-control" name="password" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar Contraseña *</label>
                        <input type="password" class="form-control" name="password_confirmation" required minlength="8">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Rol *</label>
                        <select class="form-select" name="role" required>
                            <option value="">Seleccionar rol</option>
                            <option value="medico">Médico</option>
                            <option value="administrador">Administrador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Departamento</label>
                        <select class="form-select" name="department">
                            <option value="">Seleccionar departamento</option>
                            <option value="Urgencias">Urgencias</option>
                            <option value="Medicina Interna">Medicina Interna</option>
                            <option value="Cardiología">Cardiología</option>
                            <option value="Neurología">Neurología</option>
                            <option value="Pediatría">Pediatría</option>
                            <option value="Ginecología">Ginecología</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Licencia Médica</label>
                        <input type="text" class="form-control" name="medical_license">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Especialidades</label>
                <select class="form-select" name="specialties[]" multiple>
                    <option value="Medicina General">Medicina General</option>
                    <option value="Cardiología">Cardiología</option>
                    <option value="Neurología">Neurología</option>
                    <option value="Ortopedia">Ortopedia</option>
                    <option value="Pediatría">Pediatría</option>
                    <option value="Ginecología">Ginecología</option>
                    <option value="Urología">Urología</option>
                    <option value="Oftalmología">Oftalmología</option>
                    <option value="Dermatología">Dermatología</option>
                    <option value="Psiquiatría">Psiquiatría</option>
                </select>
                <small class="form-text text-muted">Mantén presionado Ctrl para seleccionar múltiples especialidades</small>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                <label class="form-check-label">Usuario activo</label>
            </div>
        </form>
    `;
    
    document.getElementById('userModalContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function viewUser(userId) {
    fetch(`/api/users/${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showUserDetails(data.data);
            } else {
                alert('Error al cargar usuario');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar usuario');
        });
}

function editUser(userId) {
    currentUserId = userId;
    document.getElementById('userModalTitle').textContent = 'Editar Usuario';
    document.getElementById('saveUserBtn').style.display = 'block';
    
    fetch(`/api/users/${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showUserEditForm(data.data);
            } else {
                alert('Error al cargar usuario');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar usuario');
        });
}

function showUserDetails(user) {
    document.getElementById('userModalTitle').textContent = 'Detalles del Usuario';
    document.getElementById('saveUserBtn').style.display = 'none';
    
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6>Información Personal</h6>
                <table class="table table-sm">
                    <tr><td><strong>Nombre:</strong></td><td>${user.name}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>${user.email}</td></tr>
                    <tr><td><strong>Teléfono:</strong></td><td>${user.phone || 'No especificado'}</td></tr>
                    <tr><td><strong>Licencia:</strong></td><td>${user.medical_license || 'No especificada'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Información Profesional</h6>
                <table class="table table-sm">
                    <tr><td><strong>Rol:</strong></td><td><span class="badge bg-primary">${user.role}</span></td></tr>
                    <tr><td><strong>Departamento:</strong></td><td>${user.department || 'No asignado'}</td></tr>
                    <tr><td><strong>Estado:</strong></td><td><span class="badge bg-${user.is_active ? 'success' : 'secondary'}">${user.is_active ? 'Activo' : 'Inactivo'}</span></td></tr>
                    <tr><td><strong>Último acceso:</strong></td><td>${user.last_login_at ? new Date(user.last_login_at).toLocaleString('es-ES') : 'Nunca'}</td></tr>
                </table>
            </div>
        </div>
        ${user.specialties && user.specialties.length > 0 ? `
        <div class="row">
            <div class="col-12">
                <h6>Especialidades</h6>
                <div>
                    ${user.specialties.map(specialty => `<span class="badge bg-light text-dark me-1">${specialty}</span>`).join('')}
                </div>
            </div>
        </div>
        ` : ''}
    `;
    
    document.getElementById('userModalContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function showUserEditForm(user) {
    const content = `
        <form id="userForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" name="name" value="${user.name}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" value="${user.email}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" name="password" minlength="8">
                        <small class="form-text text-muted">Dejar en blanco para mantener la contraseña actual</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" name="password_confirmation" minlength="8">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Rol *</label>
                        <select class="form-select" name="role" required>
                            <option value="medico" ${user.role === 'medico' ? 'selected' : ''}>Médico</option>
                            <option value="administrador" ${user.role === 'administrador' ? 'selected' : ''}>Administrador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Departamento</label>
                        <select class="form-select" name="department">
                            <option value="">Seleccionar departamento</option>
                            <option value="Urgencias" ${user.department === 'Urgencias' ? 'selected' : ''}>Urgencias</option>
                            <option value="Medicina Interna" ${user.department === 'Medicina Interna' ? 'selected' : ''}>Medicina Interna</option>
                            <option value="Cardiología" ${user.department === 'Cardiología' ? 'selected' : ''}>Cardiología</option>
                            <option value="Neurología" ${user.department === 'Neurología' ? 'selected' : ''}>Neurología</option>
                            <option value="Pediatría" ${user.department === 'Pediatría' ? 'selected' : ''}>Pediatría</option>
                            <option value="Ginecología" ${user.department === 'Ginecología' ? 'selected' : ''}>Ginecología</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="phone" value="${user.phone || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Licencia Médica</label>
                        <input type="text" class="form-control" name="medical_license" value="${user.medical_license || ''}">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Especialidades</label>
                <select class="form-select" name="specialties[]" multiple>
                    <option value="Medicina General" ${user.specialties && user.specialties.includes('Medicina General') ? 'selected' : ''}>Medicina General</option>
                    <option value="Cardiología" ${user.specialties && user.specialties.includes('Cardiología') ? 'selected' : ''}>Cardiología</option>
                    <option value="Neurología" ${user.specialties && user.specialties.includes('Neurología') ? 'selected' : ''}>Neurología</option>
                    <option value="Ortopedia" ${user.specialties && user.specialties.includes('Ortopedia') ? 'selected' : ''}>Ortopedia</option>
                    <option value="Pediatría" ${user.specialties && user.specialties.includes('Pediatría') ? 'selected' : ''}>Pediatría</option>
                    <option value="Ginecología" ${user.specialties && user.specialties.includes('Ginecología') ? 'selected' : ''}>Ginecología</option>
                    <option value="Urología" ${user.specialties && user.specialties.includes('Urología') ? 'selected' : ''}>Urología</option>
                    <option value="Oftalmología" ${user.specialties && user.specialties.includes('Oftalmología') ? 'selected' : ''}>Oftalmología</option>
                    <option value="Dermatología" ${user.specialties && user.specialties.includes('Dermatología') ? 'selected' : ''}>Dermatología</option>
                    <option value="Psiquiatría" ${user.specialties && user.specialties.includes('Psiquiatría') ? 'selected' : ''}>Psiquiatría</option>
                </select>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" ${user.is_active ? 'checked' : ''}>
                <label class="form-check-label">Usuario activo</label>
            </div>
        </form>
    `;
    
    document.getElementById('userModalContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function toggleUserStatus(userId, newStatus) {
    const action = newStatus === 'true' ? 'activar' : 'desactivar';
    
    if (confirm(`¿Está seguro de que desea ${action} este usuario?`)) {
        fetch(`/api/users/${userId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al cambiar estado del usuario', 'error');
        });
    }
}

function resetPassword(userId) {
    currentUserId = userId;
    new bootstrap.Modal(document.getElementById('passwordModal')).show();
}

function deleteUser(userId) {
    if (confirm('¿Está seguro de que desea eliminar este usuario? Esta acción no se puede deshacer.')) {
        fetch(`/api/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al eliminar usuario', 'error');
        });
    }
}

function exportUsers() {
    const params = new URLSearchParams(window.location.search);
    params.append('format', 'csv');
    
    window.location.href = '{{ route("admin.users.index") }}?' + params.toString();
}

// Event listeners
document.getElementById('saveUserBtn').addEventListener('click', function() {
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    
    // Convert FormData to JSON
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (key.endsWith('[]')) {
            const arrayKey = key.slice(0, -2);
            if (!data[arrayKey]) data[arrayKey] = [];
            data[arrayKey].push(value);
        } else {
            data[key] = value;
        }
    }
    
    const url = currentUserId ? `/api/users/${currentUserId}` : '/api/users';
    const method = currentUserId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            if (data.errors) {
                let errorMessage = 'Errores de validación:\n';
                for (let field in data.errors) {
                    errorMessage += `- ${data.errors[field].join(', ')}\n`;
                }
                alert(errorMessage);
            } else {
                showToast(data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al guardar usuario', 'error');
    });
});

document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    fetch(`/api/users/${currentUserId}/reset-password`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('passwordModal')).hide();
            this.reset();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al restablecer contraseña', 'error');
    });
});

// Initialize DataTable
$(document).ready(function() {
    $('#usersTable').DataTable({
        "pageLength": 15,
        "order": [[ 0, "asc" ]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": [6] }
        ]
    });
});
</script>
@endpush
