<?php

declare(strict_types=1);

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

/**
 * @extends Factory<StaffUser>
 */
final class StaffUsersFactory extends Factory
{
    /**
     * El nombre del modelo correspondiente de la factoría.
     *
     * @var class-string<StaffUser>
     */
    protected $model = StaffUser::class;

    /**
     * La contraseña actual que está usando la factoría.
     */
    private static string $password = '';

    /**
     * Define el estado predeterminado del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password !== ''
                ? self::$password
                : (self::$password = Hash::make('password')),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indica que la dirección de correo electrónico del modelo debe estar sin verificar.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Inicializa atributos post-creación que no están en $fillable.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (StaffUser $user): void {
            $user->forceFill([
                'password_changed_at' => now(),
                'last_activity' => now(),
            ])->save();
        });
    }
}
