<?php

namespace Database\Seeders;

use App\Models\UserType;
use Illuminate\Database\Seeder;

/**
 * Starter user types and subtypes (subtypes taken from the iiprof.com menus).
 * Runs only while the table is empty — afterwards everything is managed in Admin → User types.
 */
class UserTypeSeeder extends Seeder
{
    public const TYPES = [
        ['Management', 'building', 'Director Council, Governing Body, committees, Board of Studies and Advisory Council.', ['Director Council', 'Governing Body', 'Managing Committee', 'Board of Studies', 'Advisory Council']],
        ['Educators', 'academic-cap', 'Universities, colleges, schools, institutions, tutorials, coaches and education consultants.', ['Universities', 'Colleges', 'Schools', 'Institutions', 'Tutorials', 'Primary School', 'Pre-Primary School', 'Coach-Mentor', 'Education Consultant']],
        ['Recruiters', 'briefcase', 'Employers and talent-acquisition partners.', []],
        ['Job Seekers', 'identification', 'Experienced, fresher, freelance, work-from-home, professional, skilled and un-skilled candidates.', ['Experience', 'Fresher', 'Freelance', 'Work From Home', 'Professional', 'Skilled', 'Un-skilled']],
        ['Lenders', 'banknotes', 'Banks, NBFCs, microfinance, private equity, venture capital, FDI, CSR and grant providers.', ['Banks/NBFC', 'Microfinance', 'Pvt Equity', 'Venture Capital', 'FDI', 'CSR', 'Grants']],
        ['Borrowers', 'banknotes', 'Corporates, MSMEs, individuals, SHGs, JLGs, co-operatives and NGOs looking for finance.', ['Corporate', 'MSME', 'Personal', 'SHG', 'JLG', 'Co-operative', 'NGO']],
        ['Depositors', 'chart', 'Investors in IPOs, shares, bonds, debentures, insurance, fixed deposits and mutual funds.', ['IPO', 'Equity Share', 'Bond', 'Debenture', 'Insurance', 'Fixed Deposit', 'Mutual Fund']],
        ['Fund Managers', 'chart', 'Portfolio managers, DSAs and insurance advisors.', ['Portfolio Manager', 'DSA', 'Insurance Advisor']],
        ['Consultants', 'light-bulb', 'Consultancy service providers.', []],
        ['Patient', 'heart', 'Health service users.', []],
        ['Doctor', 'heart', 'Medical practitioners.', []],
        ['Health Worker', 'heart', 'Nurses, ASHA workers and care staff.', []],
        ['Hospital', 'building', 'Hospitals, nursing homes and clinics.', []],
        ['Diagnostics', 'beaker', 'Diagnostic centres and laboratories.', []],
        ['Blood Bank', 'heart', 'Blood banks and donor services.', []],
        ['Medicine', 'beaker', 'Pharmacies and medicine suppliers.', []],
        ['Medical Instrument Dealers', 'shopping-bag', 'Suppliers of medical equipment.', []],
        ['Philanthropist', 'gift', 'Donors, foundations and CSR partners.', []],
    ];

    public function run(): void
    {
        if (UserType::query()->exists()) {
            return;
        }

        foreach (self::TYPES as $i => [$name, $icon, $description, $subtypes]) {
            $type = UserType::query()->create([
                'name' => $name,
                'icon' => $icon,
                'description' => $description,
                'sort_order' => ($i + 1) * 10,
            ]);

            foreach ($subtypes as $j => $subtype) {
                $type->subtypes()->create(['name' => $subtype, 'sort_order' => ($j + 1) * 10]);
            }
        }
    }
}
