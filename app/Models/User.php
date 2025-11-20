<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder; // Importar Builder de consultas (Extensor de coletillas)

/**
 * Agregamos este DocBlock para que el IDE (Intelephense) reconozca 
 * los métodos personalizados que hemos añadido.
 *
 * @property string $rol // Nombre del campo en español
 * @method static \Illuminate\Database\Eloquent\Builder|User admin()
 * @method static \Illuminate\Database\Eloquent\Builder|User alumno()
 *
 * @method bool isAdmin()
 * @method bool isAlumno()
 * @method bool isProfesor()
 * @method bool isTutorLaboral()
 */
class User extends Authenticatable
{
    // Habilitamos el uso de UUIDs como clave primaria
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $connection = 'mysql';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',           // Campo de rol
        'rolable_id',    // Clave polimórfica
        'rolable_type',  // Tipo polimórfico
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'rolable_id' => 'string', // Aseguramos el casting a string para UUID
        'password' => 'hashed'
    ];

    // --- Relación Polimórfica ---

    /**
     * Obtiene el modelo padre (TutorLaboral, Profesor, Alumno) al que pertenece el usuario.
     */
    public function rolable(): MorphTo
    {
        return $this->morphTo();
    }

    // --- Métodos de Ayuda para Roles (en Inglés) ---

    public function isAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function isAlumno(): bool
    {
        return $this->rol === 'alumno';
    }

    public function isProfesor(): bool
    {
        return $this->rol === 'profesor';
    }
    
    public function isTutorLaboral(): bool
    {
        return $this->rol === 'tutor_laboral';
    }

    // --- Scopes para filtrar ---

    /**
     * Scope para obtener solo usuarios que son administradores.
     */
    public function scopeAdmin(Builder $query): void
    {
        $query->where('rol', 'admin');
    }

    /**
     * Scope para obtener solo usuarios que son alumnos.
     */
    public function scopeAlumno(Builder $query): void
    {
        $query->where('rol', 'alumno');
    }
}