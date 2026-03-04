<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Models\Proyecto;

class AdminController extends Controller
{
    public function crearProyecto(Request $request)
    {
        // Obtenemos el año si se pasa desde el formulario, si no, por defecto es el año actual
        $yearStart = $request->input('year_start'); 
        
        $arguments = $yearStart ? ['year_start' => $yearStart] : [];
        
        try {
            // EJECUCIÓN SÍNCRONA: Espera el resultado del comando para continuar
            $salida = Artisan::call('db:crear-proyecto', $arguments);

            if($salida === 1){
                // Si el comando devolvió 1, significa que hubo un error (ej. duplicidad).
                // Recuperamos el mensaje que el comando Artisan escribió en la salida.
                $errorMessage = Artisan::output();
                return redirect()->route('gestion.proyectos.index')->with('error', trim($errorMessage));
            }
            
            // Si no hay duplicidad creamos la base de datos y volvemos a la vista
            return redirect()->route('gestion.proyectos.index')->with('success', 'La nueva base de datos del proyecto ha sido creada y migrada correctamente.');

        } catch (\Exception $e) {
            $errorMessage = "Error al ejecutar el comando: " . $e->getMessage();
            
            return redirect()->route('gestion.proyectos.index')->with('error', $errorMessage);
        }

        return redirect()->route('gestion.proyectos.index')->with('success', 'La creación del proyecto ha sido iniciada en segundo plano...');
    }

    /**
     * Método para mostrar al administrador el listado de proyectos
     */
    public function listadoProyectos()
    {
        $proyectos = Proyecto::orderBy('proyecto', 'asc')->get();
        return view('gestion.proyectos.index', compact('proyectos'));
    }
}
