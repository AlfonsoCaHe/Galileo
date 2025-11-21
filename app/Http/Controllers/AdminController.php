<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Models\Proyecto;
//use App\Jobs\RunArtisanCommand; // Para creación de bases de datos mediante asincronía #DESACTIVADO

class AdminController extends Controller
{
    public function crearProyecto(Request $request)
    {
        // Obtenemos el año si se pasa desde el formulario, si no, por defecto es el año actual
        $yearStart = $request->input('year_start'); 
        
        $arguments = $yearStart ? ['year_start' => $yearStart] : [];
        
        try {
            //EJECUCIÓN ASÍNCRONA (Recomendado para comandos largos o de inserción en bases de datos)
            //No se utiliza por un problema en la retroalimentación, y dado que supuestamente solo se crearán las bases de datos en local, no debe haber problema
            //Encola la ejecución del comando. Esto requiere tener configurada una cola (Queue).
            /**
             * RunArtisanCommand::dispatch('db:crear-proyecto', $arguments);
             */

            // EJECUCIÓN SÍNCRONA: Espera el resultado del comando
            $salida = Artisan::call('db:crear-proyecto', $arguments);

            if($salida === 1){
                // Si el comando devolvió 1, significa que hubo un error lógico (ej. duplicidad).
                // Recuperamos el mensaje que el comando Artisan escribió en la salida.
                $errorMessage = Artisan::output();
                // Limpiamos la salida para no contaminar futuras llamadas.

                return redirect()->route('admin.proyectos')->with('error', trim($errorMessage));
            }
            
            // Si no hay duplicidad creamos la base de datos y volvemos a la vista:
            return redirect()->route('admin.proyectos')->with('success', 'La nueva base de datos del proyecto ha sido creada y migrada correctamente.');

        } catch (\Exception $e) {
            // Error:
            $errorMessage = "Error al ejecutar el comando: " . $e->getMessage();
            
            return redirect()->route('admin.proyectos')->with('error', $errorMessage);
        }

        return redirect()->route('admin.proyectos')->with('success', 'La creación del proyecto ha sido iniciada en segundo plano...');
    }

    public function listadoProyectos()
    {
        $proyectos = Proyecto::orderBy('proyecto', 'asc')->get();
        return view('admin.proyectos', compact('proyectos'));
    }
}
