// Variables globales
let contacts = [];
let currentPage = 1;
const itemsPerPage = 15;
let isAdmin = false;

// Función principal para cargar contactos
function loadContacts(searchTerm = "") {
    $.ajax({
        url: 'php/getContacts.php',
        type: 'GET',
        data: { search: searchTerm },
        dataType: 'json',
        success: function(data) {
            contacts = data;
            renderTable();
            updatePagination();
            updateTotalContacts();
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar contactos:", error);
            showAlert('Error al cargar contactos', 'danger');
        }
    });
}

// Función para actualizar la UI según el estado de autenticación
function updateAuthUI(isAuthenticated) {
    if (isAuthenticated) {
        $('.admin-control').show();
        $('.admin-actions').show();
        $('#logoutBtn').show();
        $('.logo-header').css('cursor', 'default'); // Quita el efecto clickable
    } else {
        $('.admin-control').hide();
        $('.admin-actions').hide();
        $('#logoutBtn').hide();
        $('.logo-header').css('cursor', 'pointer'); // Vuelve clickable
    }
}



// Modifica la función logout:
function logout() {
    $.ajax({
        url: 'php/logout.php',
        type: 'POST',
        dataType: 'json',
        success: function() {
            isAdmin = false;
            updateAuthUI(false);  // <-- Usa la nueva función
            showAlert('Sesión cerrada correctamente', 'info');
            loadContacts();
        }
    });
}

// Agrega el evento para el botón de logout
$(document).on('click', '#logoutBtn', function() {
    logout();
});

// Modifica checkAuthStatus:
function checkAuthStatus() {
    $.ajax({
        url: 'php/checkAuth.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.authenticated) {
                isAdmin = true;
                updateAuthUI(true);  // <-- Usa la nueva función
            }
        }
    });
}

// Función para buscar contactos
function searchContacts() {
    const searchTerm = $('#searchInput').val().trim();
    currentPage = 1;
    loadContacts(searchTerm);
}

// Función para renderizar la tabla
function renderTable() {
    const startIndex = (currentPage - 1) * itemsPerPage;
    const paginatedContacts = contacts.slice(startIndex, startIndex + itemsPerPage);
    
    let html = '';
    
    if (paginatedContacts.length === 0) {
        html = `<tr><td colspan="${isAdmin ? 4 : 3}" class="text-center py-4">No se encontraron contactos</td></tr>`;
    } else {
        paginatedContacts.forEach(contact => {
            html += `
                <tr data-id="${contact.id}">
                    <td>${contact.gerencia}</td>
                    <td>${contact.nombre}</td>
                    <td>${contact.extension}</td>
                    ${isAdmin ? `
                        <td class="admin-actions">
                            <button class="btn btn-sm btn-outline-primary edit-btn" data-id="${contact.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${contact.id}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    ` : '<td></td>'}
                </tr>
            `;
        });
    }
    
    $('#directoryBody').html(html);
    
    // Asignar eventos a los botones
    $('.edit-btn').click(function() {
        const id = $(this).data('id');
        editContact(id);
    });
    
    $('.delete-btn').click(function() {
        const id = $(this).data('id');
        deleteContact(id);
    });
}

// Función para actualizar paginación
function updatePagination() {
    const totalPages = Math.ceil(contacts.length / itemsPerPage);
    let paginationHtml = '';
    
    if (totalPages > 1) {
        // Botón Anterior
        paginationHtml += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Anterior</a>
            </li>`;
        
        // Números de página
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
        }
        
        // Botón Siguiente
        paginationHtml += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Siguiente</a>
            </li>`;
    }
    
    $('#pagination').html(paginationHtml);
    
    // Evento para cambiar de página
    $('.page-link').click(function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            renderTable();
            updateTotalContacts();
        }
    });
}

// Función para actualizar el contador de contactos
function updateTotalContacts() {
    const start = (currentPage - 1) * itemsPerPage + 1;
    const end = Math.min(currentPage * itemsPerPage, contacts.length);
    const total = contacts.length;
    $('#totalContacts').text(`Mostrando ${start}-${end} de ${total} contactos`);
}

// Función para editar contacto
function editContact(id) {
    const contact = contacts.find(c => c.id == id);
    if (contact) {
        $('#contactId').val(contact.id);
        $('#gerencia').val(contact.gerencia);
        $('#nombre').val(contact.nombre);
        $('#extension').val(contact.extension);
        $('#modalTitle').text('Editar Contacto');
        $('#contactModal').modal('show');
    }
}

// Función para mostrar modal de agregar contacto
function showAddModal() {
    $('#contactId').val('');
    $('#contactForm')[0].reset();
    $('#modalTitle').text('Agregar Nuevo Contacto');
    $('#contactModal').modal('show');
}

// Función para guardar contacto (crear/actualizar)
function saveContact() {
    const id = $('#contactId').val();
    const contact = {
        gerencia: $('#gerencia').val().trim(),
        nombre: $('#nombre').val().trim(),
        extension: $('#extension').val().trim()
    };

    // Validación
    if (!contact.gerencia || !contact.nombre || !contact.extension) {
        showAlert('Por favor complete todos los campos', 'warning');
        return;
    }

    const url = id ? 'php/updateContact.php' : 'php/addContact.php';
    const method = id ? 'PUT' : 'POST';

    $('#saveContactBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

    $.ajax({
        url: url,
        type: method,
        contentType: 'application/json',
        data: JSON.stringify(id ? { id, ...contact } : contact),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#contactModal').modal('hide');
                loadContacts($('#searchInput').val().trim());
                showAlert(`Contacto ${id ? 'actualizado' : 'agregado'} correctamente`, 'success');
            } else {
                showAlert(response.message || 'Error al guardar el contacto', 'danger');
            }
        },
        error: function(xhr) {
            let errorMsg = 'Error al guardar el contacto';
            try {
                const res = JSON.parse(xhr.responseText);
                errorMsg = res.message || errorMsg;
            } catch (e) {
                errorMsg += `: ${xhr.statusText}`;
            }
            showAlert(errorMsg, 'danger');
        },
        complete: function() {
            $('#saveContactBtn').prop('disabled', false).html('Guardar');
        }
    });
}

// Función para eliminar contacto

function deleteContact(id) {
    if (!confirm('¿Está seguro que desea eliminar este contacto?')) return;

    const deleteBtn = $(`button.delete-btn[data-id="${id}"]`);
    deleteBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.ajax({
        url: 'php/deleteContact.php',
        type: 'POST', // Cambiado a POST para mejor compatibilidad
        contentType: 'application/json',
        data: JSON.stringify({ id: id }), // Asegurar formato correcto
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('✅ Contacto eliminado correctamente', 'success');
                loadContacts($('#searchInput').val().trim());
            } else {
                showAlert(`❌ ${response.message || 'Error al eliminar'}`, 'danger');
                console.error('Error del servidor:', response);
            }
        },
        error: function(xhr) {
            let errorMsg = '❌ Error al conectar con el servidor';
            try {
                const errResponse = JSON.parse(xhr.responseText);
                errorMsg = `❌ ${errResponse.message || errorMsg}`;
            } catch (e) {
                errorMsg += `. Detalles: ${xhr.statusText || 'Sin información'}`;
            }
            showAlert(errorMsg, 'danger');
        },
        complete: function() {
            deleteBtn.prop('disabled', false).html('<i class="fas fa-trash-alt"></i>');
        }
    });
}


// Función para verificar autenticación
function checkAuthStatus() {
    $.ajax({
        url: 'php/checkAuth.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.authenticated) {
                isAdmin = true;
                toggleAdminControls(true);
            }
        },
        error: function() {
            console.log("No se pudo verificar la autenticación");
        }
    });
}

// Función para mostrar/ocultar controles administrativos
function toggleAdminControls(show) {
    isAdmin = show;
    if (show) {
        $('.admin-control').show();
        $('.admin-actions').show();
    } else {
        $('.admin-control').hide();
        $('.admin-actions').hide();
    }
}

// Función para manejar login
function handleLogin() {
    const btn = $('#loginBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Ingresando...');

    const username = $('#username').val().trim();
    const password = $('#password').val().trim();

    // Validación básica de campos
    if (!username || !password) {
        showAlert('Por favor complete ambos campos', 'warning');
        btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i> Ingresar');
        return;
    }

    $.ajax({
        url: 'php/login.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ 
            username: username,
            password: password
        }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                isAdmin = true;
                
                // 1. Actualizar la interfaz
                updateAuthUI(true);
                
                // 2. Ocultar modal y limpiar campos
                $('#loginModal').modal('hide');
                $('#username').val('');
                $('#password').val('');
                
                // 3. Mostrar mensaje de bienvenida
                showAlert('Bienvenido ' + (response.user?.username || ''), 'success');
                
                // 4. Recargar los contactos
                loadContacts();
            } else {
                showAlert(response.message || 'Credenciales incorrectas', 'danger');
            }
        },
        error: function(xhr) {
            let errorMsg = 'Error al conectar con el servidor';
            try {
                const errResponse = JSON.parse(xhr.responseText);
                errorMsg = errResponse.message || errorMsg;
            } catch (e) {
                errorMsg += ` (${xhr.statusText})`;
            }
            showAlert(errorMsg, 'danger');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i> Ingresar');
        }
    });
}

// Función para cerrar sesión
function logout() {
    $.ajax({
        url: 'php/logout.php',
        type: 'POST',
        dataType: 'json',
        success: function() {
            isAdmin = false;
            toggleAdminControls(false);
            showAlert('Sesión cerrada correctamente', 'info');
            loadContacts();
        },
        error: function() {
            showAlert('Error al cerrar sesión', 'danger');
        }
    });
}

// Función para mostrar alertas
function showAlert(message, type) {
    // Eliminar alertas anteriores
    $('.alert-dismissible').alert('close');
    
    const alert = `
        <div class="alert alert-${type} alert-dismissible fade show fixed-top mx-auto mt-3" style="max-width: 500px; z-index: 1100;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('body').append(alert);
    setTimeout(() => $('.alert').alert('close'), 3000);
}

// Inicialización de la aplicación
$(document).ready(function() {
    // Verificar autenticación al cargar
    checkAuthStatus();
    
    // Cargar contactos iniciales
    loadContacts();
    
    // Configurar eventos
    $('#searchBtn').click(searchContacts);
    $('#searchInput').keypress(function(e) {
        if (e.key === 'Enter') searchContacts();
    });
    
	// Cambia el evento del logo para que solo muestre login si no está autenticado
	$('.logo-header').click(function() {
		if (!isAdmin) {
			$('#loginModal').modal('show');
		}
		// Si está autenticado, no hace nada al hacer clic en el logo
	});
    
    $('#loginForm').submit(function(e) {
        e.preventDefault();
        handleLogin();
    });
    
    $('#addContactBtn').click(showAddModal);
    $('#saveContactBtn').click(saveContact);
});