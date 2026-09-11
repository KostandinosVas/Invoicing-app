<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Series;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Κωνσταντίνος Βασίλη',
            'email' => 'test@test.gr',
            'password' => bcrypt('password'),
        ]);

        $companies = [
            [
                'name' => 'Τεχνομέταλ ΑΕ',
                'vat_number' => '094512367',
                'tax_office' => 'ΦΑΕ Αθηνών',
                'address' => 'Λεωφ. Κηφισίας 124',
                'city' => 'Αθήνα',
                'postal_code' => '11526',
            ],
            [
                'name' => 'Κρητικά Προϊόντα ΙΚΕ',
                'vat_number' => '801234567',
                'tax_office' => 'ΔΟΥ Ηρακλείου',
                'address' => 'Λεωφ. Ικάρου 45',
                'city' => 'Ηράκλειο',
                'postal_code' => '71306',
            ],
            [
                'name' => 'Αιγαίο Μεταφορική ΕΠΕ',
                'vat_number' => '099887766',
                'tax_office' => 'ΔΟΥ Πειραιά',
                'address' => 'Ακτή Μιαούλη 17',
                'city' => 'Πειραιάς',
                'postal_code' => '18535',
            ],
        ];

        foreach ($companies as $data) {
            $company = Company::create($data);

            $this->seedSeries($company);
            $this->seedCustomers($company);
            $this->seedItems($company);
        }
    }

    private function seedSeries(Company $company): void
    {
        foreach ([
            ['code' => 'ΤΙΜ', 'document_type' => 'invoice'],
            ['code' => 'ΠΙΣ', 'document_type' => 'credit_note'],
            ['code' => 'ΑΚΥ', 'document_type' => 'cancellation'],
        ] as $series) {
            Series::create([...$series, 'company_id' => $company->id]);
        }
    }

    private function seedCustomers(Company $company): void
    {
        $customers = [
            ['name' => 'Παπαδόπουλος & ΣΙΑ ΟΕ', 'vat_number' => '045678912', 'tax_office' => 'ΔΟΥ Χαλανδρίου', 'address' => 'Ελ. Βενιζέλου 8', 'city' => 'Χαλάνδρι', 'postal_code' => '15232'],
            ['name' => 'Μεσογειακή Εμπορική ΑΕ', 'vat_number' => '099112233', 'tax_office' => 'ΦΑΕ Θεσσαλονίκης', 'address' => 'Τσιμισκή 67', 'city' => 'Θεσσαλονίκη', 'postal_code' => '54622'],
            ['name' => 'Ξενοδοχεία Ακτή ΑΕ', 'vat_number' => '088776655', 'tax_office' => 'ΔΟΥ Ρόδου', 'address' => 'Ακτή Καναρη 3', 'city' => 'Ρόδος', 'postal_code' => '85100'],
            ['name' => 'Γεωργίου Ηλεκτρολογικά', 'vat_number' => '077665544', 'tax_office' => 'ΔΟΥ Λάρισας', 'address' => 'Κύπρου 22', 'city' => 'Λάρισα', 'postal_code' => '41222'],
            ['name' => 'Ιωάννα Δημητρίου', 'vat_number' => null, 'tax_office' => null, 'address' => 'Σόλωνος 12', 'city' => 'Αθήνα', 'postal_code' => '10672'],
        ];

        foreach ($customers as $data) {
            Customer::create([...$data, 'company_id' => $company->id, 'country' => 'GR']);
        }
    }

    private function seedItems(Company $company): void
    {
        $items = [
            ['code' => 'SRV-001', 'name' => 'Συμβουλευτικές υπηρεσίες', 'unit' => 'ώρα', 'unit_price_cents' => 5000, 'vat_rate' => 24],
            ['code' => 'SRV-002', 'name' => 'Τεχνική υποστήριξη', 'unit' => 'ώρα', 'unit_price_cents' => 3500, 'vat_rate' => 24],
            ['code' => 'PRD-001', 'name' => 'Μεταλλικό πλαίσιο 60x40', 'unit' => 'τεμ', 'unit_price_cents' => 12750, 'vat_rate' => 24],
            ['code' => 'PRD-002', 'name' => 'Ελαιόλαδο εξαιρετικό παρθένο 5L', 'unit' => 'τεμ', 'unit_price_cents' => 4200, 'vat_rate' => 13],
            ['code' => 'PRD-003', 'name' => 'Έντυπο ενημερωτικό υλικό', 'unit' => 'τεμ', 'unit_price_cents' => 250, 'vat_rate' => 6],
            ['code' => 'SRV-003', 'name' => 'Μεταφορικά εντός Αττικής', 'unit' => 'δρομ', 'unit_price_cents' => 2500, 'vat_rate' => 24],
        ];

        foreach ($items as $data) {
            Item::create([...$data, 'company_id' => $company->id, 'is_active' => true]);
        }
    }
}
