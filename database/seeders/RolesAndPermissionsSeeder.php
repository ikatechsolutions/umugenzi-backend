<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions pour les groups (existantes)
        Permission::firstOrCreate(['name' => 'view groups']);
        Permission::firstOrCreate(['name' => 'create groups']);
        Permission::firstOrCreate(['name' => 'edit groups']);
        Permission::firstOrCreate(['name' => 'delete groups']);

        // Permissions pour les games (existantes)
        Permission::firstOrCreate(['name' => 'view games']);
        Permission::firstOrCreate(['name' => 'create games']);
        Permission::firstOrCreate(['name' => 'edit games']);
        Permission::firstOrCreate(['name' => 'delete games']);

        // Permissions pour les users (existantes)
        Permission::firstOrCreate(['name' => 'view users']);
        Permission::firstOrCreate(['name' => 'create users']);
        Permission::firstOrCreate(['name' => 'edit users']);
        Permission::firstOrCreate(['name' => 'delete users']);

        // Permissions pour les evenements (existantes)
        Permission::firstOrCreate(['name' => 'view evenements']);
        Permission::firstOrCreate(['name' => 'create evenements']);
        Permission::firstOrCreate(['name' => 'edit evenements']);
        Permission::firstOrCreate(['name' => 'delete evenements']);

        // Permissions pour les reservations (existantes)
        Permission::firstOrCreate(['name' => 'view reservations']);
        Permission::firstOrCreate(['name' => 'create reservations']);
        Permission::firstOrCreate(['name' => 'edit reservations']);
        Permission::firstOrCreate(['name' => 'delete reservations']);

        // Permissions pour les tickets (existantes)
        Permission::firstOrCreate(['name' => 'view tickets']);
        Permission::firstOrCreate(['name' => 'create tickets']);
        Permission::firstOrCreate(['name' => 'edit tickets']);
        Permission::firstOrCreate(['name' => 'delete tickets']);

        // Permissions pour les typetickets (existantes)
        Permission::firstOrCreate(['name' => 'view typetickets']);
        Permission::firstOrCreate(['name' => 'create typetickets']);
        Permission::firstOrCreate(['name' => 'edit typetickets']);
        Permission::firstOrCreate(['name' => 'delete typetickets']);

        // Permissions pour les categories (existantes)
        Permission::firstOrCreate(['name' => 'view categories']);
        Permission::firstOrCreate(['name' => 'create categories']);
        Permission::firstOrCreate(['name' => 'edit categories']);
        Permission::firstOrCreate(['name' => 'delete categories']);

        // Permissions pour les historiques (existantes)
        Permission::firstOrCreate(['name' => 'view historiques']);
        Permission::firstOrCreate(['name' => 'create historiques']);
        Permission::firstOrCreate(['name' => 'edit historiques']);
        Permission::firstOrCreate(['name' => 'delete historiques']);

        // Nouvelles permissions pour les rôles et permissions
        Permission::firstOrCreate(['name' => 'view roles']);
        Permission::firstOrCreate(['name' => 'create roles']);
        Permission::firstOrCreate(['name' => 'edit roles']);
        Permission::firstOrCreate(['name' => 'delete roles']);

        Permission::firstOrCreate(['name' => 'view permissions']);
        Permission::firstOrCreate(['name' => 'create permissions']);
        Permission::firstOrCreate(['name' => 'edit permissions']);
        Permission::firstOrCreate(['name' => 'delete permissions']);

        // Créer ou obtenir le rôle admin
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $clientRole = Role::firstOrCreate(['name' => 'client']);
        $agentRole = Role::firstOrCreate(['name' => 'agent']);
        // Assigner toutes les permissions à l'admin
        $adminRole->givePermissionTo(Permission::all());

        // Assurez-vous qu'un utilisateur a le rôle admin pour tester
        $user = User::find(1);
        if ($user && !$user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        // Créer un utilisateur de test avec le rôle client si nécessaire
        $clientUser = User::where('email', 'client@example.com')->first();
        if (!$clientUser) {
            $clientUser = User::create([
                'name' => 'Client User',
                'email' => 'client@example.com',
                'phone' => '77890076',
                'adresse' => 'Q. Gikizi',
                'password' => Hash::make('Client1234'), // Mot de passe par défaut pour le test
            ]);
            $clientUser->assignRole('client');
        }
    }
}
