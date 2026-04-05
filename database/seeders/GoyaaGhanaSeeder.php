<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\ZoneManagement\Entities\Zone;

class GoyaaGhanaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Update Business Settings
        $this->updateBusinessSettings();

        // 2. Fetch the active zone (All over the World)
        $zone = Zone::first();
        $zoneId = $zone ? $zone->id : null;

        // 3. Clear existing data
        DB::table('categories')->truncate();
        DB::table('services')->truncate();
        
        // Clean non-admin users and their providers
        $userIds = User::where('user_type', '!=', 'super-admin')->pluck('id');
        Provider::whereIn('user_id', $userIds)->delete();
        User::where('user_type', '!=', 'super-admin')->delete();

        // 4. Create Categories
        $categories = [
            'Plumbing' => 'Plumbing services including pipe leaks, installations.',
            'Electrical' => 'Electrical wiring, appliance repairs, and installations.',
            'Carpentry' => 'Furniture making, repairs, and woodwork.',
            'Cleaning' => 'House, office, and deep cleaning services.',
            'Masonry' => 'Block laying, plastering, and concrete works.',
            'Painting' => 'Interior and exterior house painting.',
            'AC Repair' => 'Air conditioning installation and maintenance.'
        ];

        $categoryMap = [];
        $catPos = 1;
        foreach ($categories as $name => $desc) {
            // Create a dummy image for category
            $imageName = 'cat_' . Str::slug($name) . '.png';
            $this->createDummyImage('category', $imageName, $name);

            $cat = Category::create([
                'id' => Str::uuid(),
                'parent_id' => null,
                'name' => $name,
                'image' => $imageName,
                'position' => $catPos++,
                'description' => $desc,
                'is_active' => 1,
                'is_featured' => 1,
            ]);
            $categoryMap[$name] = $cat->id;
        }

        // 5. Create Services
        $services = [
            'Plumbing' => [
                'Fix Pipe Leakage' => 150,
                'Toilet Installation' => 300,
                'Water Pump Repair' => 200,
            ],
            'Electrical' => [
                'House Wiring' => 500,
                'Ceiling Fan Installation' => 100,
                'Socket/Switch Repair' => 50,
            ],
            'Carpentry' => [
                'Door/Window Repair' => 120,
                'Wardrobe Making' => 800,
                'Furniture Polishing' => 250,
            ],
            'Cleaning' => [
                'Deep House Cleaning' => 350,
                'Sofa/Carpet Cleaning' => 200,
                'Post-Construction Cleaning' => 600,
            ],
            'Masonry' => [
                'Wall Plastering' => 400,
                'Tile Laying' => 300,
                'Paving Blocks Installation' => 500,
            ],
            'Painting' => [
                'Room Painting' => 250,
                'Exterior Painting' => 700,
            ],
            'AC Repair' => [
                'AC Servicing/Cleaning' => 150,
                'Gas Refill' => 200,
                'New AC Installation' => 350,
            ]
        ];

        foreach ($services as $catName => $catServices) {
            $catId = $categoryMap[$catName];
            foreach ($catServices as $serviceName => $price) {
                $imageName = 'srv_' . Str::slug($serviceName) . '.png';
                $this->createDummyImage('service', $imageName, $serviceName);

                Service::create([
                    'id' => Str::uuid(),
                    'name' => $serviceName,
                    'short_description' => "Professional $serviceName services by expert artisans.",
                    'description' => "Get the best $serviceName in town. Our artisans are verified and highly skilled. Book now for a hassle-free experience.",
                    'cover_image' => $imageName,
                    'thumbnail' => $imageName,
                    'category_id' => $catId,
                    'sub_category_id' => null,
                    'tax' => 0,
                    'order_count' => rand(5, 50),
                    'is_active' => 1,
                    'rating_count' => rand(1, 20),
                    'avg_rating' => rand(35, 50) / 10,
                    'min_bidding_price' => $price,
                ]);
            }
        }

        // 6. Create Dummy Providers (Artisans)
        $artisans = [
            ['name' => 'Kwame Mensah', 'company' => 'Mensah Plumbing Works', 'phone' => '+233240000001', 'email' => 'kwame.plumber@goyaa.com'],
            ['name' => 'Kofi Osei', 'company' => 'Osei Electricals', 'phone' => '+233240000002', 'email' => 'kofi.electric@goyaa.com'],
            ['name' => 'Ama Serwaa', 'company' => 'Serwaa Cleaning Services', 'phone' => '+233240000003', 'email' => 'ama.clean@goyaa.com'],
            ['name' => 'Yaw Asare', 'company' => 'Asare Woodworks', 'phone' => '+233240000004', 'email' => 'yaw.carpenter@goyaa.com'],
            ['name' => 'Esi Owusu', 'company' => 'Esi Paints', 'phone' => '+233240000005', 'email' => 'esi.painter@goyaa.com'],
        ];

        foreach ($artisans as $artisan) {
            $names = explode(' ', $artisan['name']);
            
            $user = User::create([
                'id' => Str::uuid(),
                'first_name' => $names[0],
                'last_name' => $names[1] ?? '',
                'email' => $artisan['email'],
                'phone' => $artisan['phone'],
                'password' => Hash::make('12345678'),
                'user_type' => 'provider-admin',
                'is_active' => 1,
                'is_email_verified' => 1,
                'is_phone_verified' => 1,
            ]);

            $logoName = 'prov_' . Str::slug($artisan['company']) . '.png';
            $this->createDummyImage('provider/logo', $logoName, 'Logo');

            Provider::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'company_name' => $artisan['company'],
                'company_phone' => $artisan['phone'],
                'company_address' => 'Accra, Ghana',
                'company_email' => $artisan['email'],
                'logo' => $logoName,
                'contact_person_name' => $artisan['name'],
                'contact_person_phone' => $artisan['phone'],
                'contact_person_email' => $artisan['email'],
                'is_approved' => 1,
                'is_active' => 1,
                'zone_id' => $zoneId,
                'service_availability' => 1,
                'commission_status' => 1,
                'commission_percentage' => 10,
            ]);
        }
        
        // 7. Create Dummy Customers
        $customers = [
            ['name' => 'Abena Appiah', 'phone' => '+233200000001', 'email' => 'abena@customer.com'],
            ['name' => 'Kojo Manu', 'phone' => '+233200000002', 'email' => 'kojo@customer.com'],
        ];

        foreach ($customers as $customer) {
            $names = explode(' ', $customer['name']);
            User::create([
                'id' => Str::uuid(),
                'first_name' => $names[0],
                'last_name' => $names[1] ?? '',
                'email' => $customer['email'],
                'phone' => $customer['phone'],
                'password' => Hash::make('12345678'),
                'user_type' => 'customer',
                'is_active' => 1,
                'is_email_verified' => 1,
                'is_phone_verified' => 1,
            ]);
        }
    }

    private function updateBusinessSettings()
    {
        $settings = [
            'business_name' => 'Goyaa',
            'business_phone' => '+233550000000',
            'business_email' => 'info@goyaa.com.gh',
            'business_address' => 'Accra, Ghana',
            'country' => 'GH',
            'currency_code' => 'GHS',
            'currency_symbol_position' => 'left',
        ];

        foreach ($settings as $key => $val) {
            BusinessSettings::updateOrCreate(
                ['key_name' => $key, 'settings_type' => 'business_information'],
                ['live_values' => $val, 'test_values' => $val, 'mode' => 'live', 'is_active' => 1]
            );
        }
    }

    private function createDummyImage($path, $filename, $text)
    {
        $dir = storage_path('app/public/' . $path);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $width = 600;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        
        // Random background color
        $bg = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
        imagefill($image, 0, 0, $bg);
        
        // Text color
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        // Add text (centered)
        $fontSize = 5; // Built-in font
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);
        
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $fontSize, $x, $y, $text, $textColor);
        
        imagepng($image, $dir . '/' . $filename);
        imagedestroy($image);
    }
}
