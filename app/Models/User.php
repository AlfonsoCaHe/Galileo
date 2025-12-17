<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder; // Importar Builder de consultas (Extensor de coletillas)

/**
 * Agregamos este DocBlock para que el IDE (Intelephense) reconozca los métodos personalizados que hemos añadido y no encuentra de otra forma.
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
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

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
        'rol', // Campo de rol
        'rolable_id', // Clave polimórfica
        'rolable_type', // Tipo polimórfico
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
        'rolable_id' => 'string',
        'password' => 'hashed'
    ];

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

    /**
     * Obtiene el modelo de perfil/rol asociado al usuario (TutorLaboral, Profesor, etc.).
     * Coincide con las columnas 'rolable_id' y 'rolable_type' en la tabla users.
     */
    public function rolable(): MorphTo
    {
        // Llama al método morphTo sin argumentos si las columnas son rolable_id/type
        return $this->morphTo(); 
    }

    /**
    * Crea un registro de Usuario y lo enlaza polimórficamente a un modelo de perfil/rol.
    * @param \Illuminate\Database\Eloquent\Model $rolableModel Instancia del perfil (TutorLaboral, Profesor, etc.)
    * @param array $userData Datos del usuario (name, email, password)
    * @return \App\Models\User
    */
    public static function createRolableUser(Model $rolableModel, array $userData): User
    {
        // Laravel se encarga de:
        // 1. Llamar al método user() en $rolableModel.
        // 2. Setear los campos rolable_id y rolable_type en la tabla users.
        // 3. Hashear la contraseña usando el mutator del modelo User.
        $user = $rolableModel->user()->create($userData);
        
        return $user;
    }
}