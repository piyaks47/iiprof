<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\UserType;
use Illuminate\Database\Seeder;

/**
 * Starter modules (the iiprof.com service areas) and who gets them by default.
 * Runs only while the table is empty — afterwards assignments are managed in Admin → Modules.
 */
class ModuleSeeder extends Seeder
{
    public const MODULES = [
        // key, name, icon, description, roles, user types
        ['community', 'Community', 'user-group', 'Connect with members across the institute.', ['admin', 'editor', 'user'], []],
        ['hunar', 'Hunar', 'academic-cap', 'Education and skills: Book My Seat and World-Edu (universities, colleges, schools, tutorials, coaches).', [], ['Educators', 'Job Seekers', 'Management', 'Consultants']],
        ['jeevika', 'Jeevika', 'briefcase', 'Jobs and entrepreneurship: experienced, fresher, freelance, work-from-home, skilled and un-skilled.', [], ['Recruiters', 'Job Seekers', 'Management']],
        ['khazana', 'Khazana', 'banknotes', 'Finance: lenders, borrowers, depositors and fund managers.', [], ['Lenders', 'Borrowers', 'Depositors', 'Fund Managers', 'Management']],
        ['duniyabazar', 'DuniyaBazar', 'shopping-bag', 'Marketplace for products and services.', [], ['Medicine', 'Medical Instrument Dealers', 'Management']],
        ['bookings', 'Bookings', 'calendar', 'Book seats, appointments and services.', [], ['Patient', 'Doctor', 'Hospital', 'Diagnostics', 'Educators', 'Job Seekers']],
        ['consultancy', 'Consultancy', 'light-bulb', 'Expert consultation services.', [], ['Consultants', 'Borrowers', 'Management']],
        ['health', 'Health', 'heart', 'Patients, doctors, health workers, hospitals, diagnostics, blood banks and medicine.', [], ['Patient', 'Doctor', 'Health Worker', 'Hospital', 'Diagnostics', 'Blood Bank', 'Medicine', 'Medical Instrument Dealers']],
        ['talent_register', 'Talent Register', 'identification', 'Register and discover talent.', [], ['Job Seekers', 'Recruiters', 'Educators']],
        ['philanthropy', 'Philanthropy', 'gift', 'Giving, CSR and grants.', [], ['Philanthropist', 'Lenders', 'Management']],
        ['edu10', 'Edu10', 'book-open', 'Edu10 learning programmes.', [], ['Educators', 'Job Seekers']],
    ];

    public function run(): void
    {
        if (Module::query()->exists()) {
            return;
        }

        $types = UserType::query()->pluck('id', 'name');

        foreach (self::MODULES as $i => [$key, $name, $icon, $description, $roles, $typeNames]) {
            $module = Module::query()->create([
                'key' => $key,
                'name' => $name,
                'icon' => $icon,
                'description' => $description,
                'roles' => $roles,
                'sort_order' => ($i + 1) * 10,
            ]);

            $module->userTypes()->sync($types->only($typeNames)->values()->all());
        }
    }
}
