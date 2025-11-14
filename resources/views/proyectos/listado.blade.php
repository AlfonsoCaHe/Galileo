<!DOCTYPE html>
<html>
<head>
    <title>Listado de Bases de Datos</title>
</head>
<body>
    <h1>Listado de Bases de Datos de Proyectos</h1>

    @if ($proyectos->isEmpty())
        <p>No hay bases de datos de proyectos almacenadas.</p>
    @else
        <ul>
            @foreach ($proyectos as $proyecto)
                <li>
                    <strong>Proyecto:</strong> {{ $proyecto->proyecto }} 
                    | <strong>Conexión:</strong> {{ $proyecto->conexion }}
                </li>
            @endforeach
        </ul>
    @endif
    <x-database-desplegable/>
</body>
</html>