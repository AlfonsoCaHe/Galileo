@extends('layouts.default')
@include('alumnos.layouts.header')

@push('scripts')

    <script>
        $(document).ready(function() {
            $('#tablaTareas').DataTable({
                responsive: true,
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
                },
                // Deshabilitar ordenamiento en la columna del checkbox (índice 4)
                columnDefs: [
                    { orderable: false, targets: 4 }
                ]
            });
        });
    </script>
@endpush

@section('content')
<div class="container mx-auto p-6">
    <div class="p-4 border-b bg-gray-50">
        <h2 class="fw-bold texto">Tareas Finalizadas</h2>
    </div>

    {{-- Menú de opciones --}}
    <div class="d-flex justify-content-end mb-3">
        <div class="d-flex">
            <a href="{{ route('alumnos.panel') }}"class="btn btn-danger fw-bold m-2">
                Volver
            </a>
        </div>
    </div>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        

        <div class="card shadow-lg p-4">
            <table id="tablaTareas" class="table table-striped table-hover w-100" style="width:100%">
                <thead>
                    <tr>
                        <th class="py-3 px-6 text-left">Tarea</th>
                        <th class="py-3 px-6 text-left">Módulo</th>
                        <th class="py-3 px-6 text-center">Fecha</th>
                        <th class="py-3 px-6 text-center">Duración</th>
                        <th class="py-3 px-6 text-center">Calificación</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    @foreach($tareasRealizadas as $tarea)
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        
                        {{-- Nombre de la Tarea --}}
                        <td class="py-3 px-6 text-left whitespace-nowrap">
                            <span class="font-medium">{{ $tarea->nombre_tarea }}</span>
                        </td>

                        {{-- Módulo --}}
                        <td class="py-3 px-6 text-left">
                            <span>{{ $tarea->nombre_modulo }}</span>
                        </td>

                        {{-- Fecha (Formateada) --}}
                        <td class="py-3 px-6 text-center">
                            {{ \Carbon\Carbon::parse($tarea->fecha)->format('d/m/Y') }}
                        </td>

                        {{-- Duración --}}
                        <td class="py-3 px-6 text-center">
                            {{ $tarea->duracion ?? 'N/A' }}
                        </td>

                        {{-- Checkbox Apto/No Apto --}}
                        <td class="py-3 px-6 text-center">
                            <div class="flex items-center justify-center space-x-2">
                                {{-- 
                                   Lógica: Asumo que si calificacion >= 5 (o true) es APTO.
                                   Ajusta la condición del if según cómo guardes la nota.
                                --}}
                                <input type="checkbox" 
                                       class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-0 cursor-not-allowed disabled:opacity-75" 
                                       disabled 
                                       {{-- Ejemplo: Si es mayor o igual a 5, checkeado --}}
                                       {{ $tarea->apto >= 5 ? 'checked' : '' }}>
                                
                                <span class="font-bold {{ $tarea->apto >= 5 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $tarea->apto >= 5 ? 'Apto' : 'No Apto' }}
                                </span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection