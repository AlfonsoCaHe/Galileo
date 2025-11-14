<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;

class ProyectoController extends Controller
{
    public function index()
    {
        $proyectos = Proyecto::all();
        return view('proyectos.listado', compact('proyectos'));
    }
}
