@extends('layouts.default')

@section('title','Listado Usuarios')

@section('scripts')
<script>
    $(document).ready(function() {
        
        // Inicialización de DataTables
        var table = $('#usuarios').DataTable({
            "paging": true,
            "pagingType": "numbers",
            "lengthChange": true,
            "lengthMenu": [
                [5, 10, 100, -1],
                [5, 10, 100, 'Todos']
            ],
            "order": [
                [1, "asc"]
            ],
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('usuarios.showDataTable') }}",
                "type": "POST",
                "data": {
                    "_token": "{{ csrf_token() }}"
                }
            },
            "columns": [
                { data: 'id', name: 'id', orderable: false, searchable: false, visible: false },
                { data: 'nombre', name: 'nombre' },
                { data: 'email', name: 'email' },
                { data: 'rol', name: 'rol' },
                { data: 'acciones', name: 'acciones', orderable: false, searchable: false }
            ],
            "language": {
                "decimal": ",",
                "emptyTable": "No hay datos en la tabla",
                "info": "Monstrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "infoEmpty": "",
                "infoFiltered": "",
                "infoPostFix": "",
                "thousands": ".",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No han encontrado registros",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "aria": {
                    "sortAscending": ": Click/return para ordenar ascendentemente",
                    "sortDescending": ": Click/return para ordenar descendentemente"
                }
            }
        });

        // DELEGACIÓN DE EVENTOS para el botón Eliminar
        $('#usuarios').on('click', '.eliminar-usuario', function() {
            
            var userId = $(this).data('id');
            var userName = $(this).data('nombre');
            
            // Usa el nombre en el mensaje de confirmación
            if (!confirm("¿Está seguro de que desea eliminar al usuario: " + userName + "?")) {
                return; // Cancelar si el usuario dice que no
            }

            // Realiza la petición AJAX de eliminación
            $.ajax({
                url: "{{ route('usuarios.eliminar') }}",
                type: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    id: userId // Enviar el ID del usuario al controlador
                },
                success: function(response) {
                    // Muestra el mensaje de éxito del controlador
                    alert(response.success || 'Usuario eliminado correctamente.'); 
                    // Vuelve a cargar la tabla para reflejar el cambio
                    table.ajax.reload(null, false); 
                },
                error: function(response) {
                    console.error("Error", response);
                    // Captura el mensaje de error del backend (ej: si intentas borrar un admin)
                    var errorMessage = response.responseJSON && response.responseJSON.error 
                                       ? response.responseJSON.error 
                                       : "Ha ocurrido un problema al intentar eliminar el usuario.";
                    alert("Error: " + errorMessage);
                }
            });
        });

    });
</script>
@endsection

@section('content')
<div class="container">
    <h2>Listado de usuarios</h2>
     
    <div>
        {{--Funciones pasadas a los gestores de profesores y alumnos--}}
        {{-- <a href="{{route('gestion.profesor.crear')}}" type="submit" class="btn btn-success mb-3">
            Añadir profesor
        </a>
        <a href="{{route('gestion.alumno.crear')}}" type="submit" class="btn btn-success mb-3">
            Añadir alumno
        </a> --}}
        <a href="{{route('admin.panel')}}" type="submit" class="btn btn-secondary mb-3">
            Volver
        </a> 
    </div>
    
    <table id="usuarios" class="display table table-striped table-hover">
        <thead>
            <tr>
                <th>Id</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            
        </tbody>
    </table>
</div>
@endsection