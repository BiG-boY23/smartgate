<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VehicleCategory;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\College;
use App\Models\Course;

class FleetAssetsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categories
        $categoriesData = [
            'Motorcycle' => ['icon' => 'bicycle'],
            'Car / Sedan' => ['icon' => 'car'],
            'SUV / Van' => ['icon' => 'jeep'],
            'Pickup' => ['icon' => 'truck'],
            'Truck' => ['icon' => 'car-profile'],
        ];

        $categoryModels = [];
        foreach ($categoriesData as $name => $data) {
            $categoryModels[$name] = VehicleCategory::updateOrCreate(['name' => $name], $data);
        }

        // 2. Brands, Categories & Models
        // Grouped by commonly associated categories
        $brandsData = [
            'Toyota' => [
                'categories' => ['Car / Sedan', 'SUV / Van', 'Pickup'],
                'models' => ['Vios', 'Hilux', 'Fortuner', 'Wigo', 'Innova', 'Camry', 'Raize']
            ],
            'Honda' => [
                'categories' => ['Car / Sedan', 'SUV / Van', 'Motorcycle'],
                'models' => ['Civic', 'CR-V', 'City', 'BR-V', 'ADV 160', 'Click 125i', 'PCX 160']
            ],
            'Mitsubishi' => [
                'categories' => ['Car / Sedan', 'SUV / Van', 'Pickup'],
                'models' => ['Montero Sport', 'Mirage', 'L300', 'Xpander', 'Strada']
            ],
            'Nissan' => [
                'categories' => ['Car / Sedan', 'SUV / Van', 'Pickup'],
                'models' => ['Navara', 'Terra', 'Almera', 'Urvan']
            ],
            'Suzuki' => [
                'categories' => ['Car / Sedan', 'SUV / Van', 'Motorcycle'],
                'models' => ['Ertiga', 'Jimny', 'Swift', 'S-Presso', 'Burgman Street', 'Raider R150']
            ],
            'Yamaha' => [
                'categories' => ['Motorcycle'],
                'models' => ['NMAX', 'Aerox', 'Mio i 125', 'YZF-R15', 'Sniper 155']
            ],
            'Isuzu' => [
                'categories' => ['SUV / Van', 'Pickup', 'Truck'],
                'models' => ['D-MAX', 'mu-X', 'Elf', 'Forward']
            ],
            'Ford' => [
                'categories' => ['SUV / Van', 'Pickup'],
                'models' => ['Ranger', 'Everest', 'Territory', 'Explorer']
            ],
            'Hyundai' => [
                'categories' => ['Car / Sedan', 'SUV / Van'],
                'models' => ['Staria', 'Accent', 'Verna', 'Tucson', 'Creta']
            ],
        ];

        foreach ($brandsData as $brandName => $data) {
            $brand = VehicleBrand::updateOrCreate(['name' => $brandName]);
            
            // Sync categories via pivot
            $catIds = [];
            foreach ($data['categories'] as $catName) {
                if (isset($categoryModels[$catName])) {
                    $catIds[] = $categoryModels[$catName]->id;
                }
            }
            $brand->categories()->sync($catIds);

            // Create models
            foreach ($data['models'] as $modelName) {
                VehicleModel::updateOrCreate([
                    'vehicle_brand_id' => $brand->id,
                    'name' => $modelName
                ]);
            }
        }

        // 3. Academic Data (Unchanged)
        $colleges = [
            'College of Computing' => ['BS Information Technology', 'BS Computer Science', 'BS Information Systems'],
            'College of Engineering' => ['BS Civil Engineering', 'BS Mechanical Engineering', 'BS Electrical Engineering'],
            'College of Education' => ['Bachelor of Elementary Education', 'Bachelor of Secondary Education'],
            'College of Business & Management' => ['BS Business Administration', 'BS Hospitality Management'],
            'College of Arts & Sciences' => ['AB Communications', 'BS Psychology', 'BS Biology'],
        ];

        foreach ($colleges as $collegeName => $courses) {
            $college = College::updateOrCreate(['name' => $collegeName]);
            foreach ($courses as $courseName) {
                Course::updateOrCreate([
                    'college_id' => $college->id,
                    'name' => $courseName
                ]);
            }
        }
    }
}
