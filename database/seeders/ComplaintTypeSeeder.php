<?php
// database/seeders/ComplaintTypeSeeder.php
namespace Database\Seeders;

use App\Models\ComplaintType;
use Illuminate\Database\Seeder;

class ComplaintTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            'Mühərrik səsi',
            'Şin partlaması',
            'Əyləc problemi',
            'İşıqlandırma nasazlığı',
            'Transmissiya problemi',
            'Süspansiyon problemi',
            'Elektrik problemi',
            'Kondisioner nasazlığı',
            'Yağ sızması',
            'Digər'
        ];

        foreach ($types as $type) {
            ComplaintType::updateOrCreate(['name' => $type]);
        }
    }
}