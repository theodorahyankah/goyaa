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
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\DataSetting;
use Modules\BusinessSettingsModule\Entities\LandingPageFeature;
use Modules\BusinessSettingsModule\Entities\LandingPageSpeciality;
use Modules\BusinessSettingsModule\Entities\LandingPageTestimonial;
use Modules\ZoneManagement\Entities\Zone;

class GoyaaGhanaSeeder extends Seeder
{
    public function run()
    {
        // 1. Update Business Settings
        $this->updateBusinessSettings();

        // 2. Fetch/Create the active zone
        $zone = Zone::first();
        if (!$zone) {
            $zone = Zone::create([
                'id' => Str::uuid(),
                'name' => 'Accra Greater Region',
                'coordinates' => null, 
                'is_active' => 1,
            ]);
        }
        $zoneId = $zone->id;

        // 3. Clear existing data
        DB::table('categories')->truncate();
        DB::table('services')->truncate();
        DB::table('data_settings')->whereIn('type', ['landing_text_setup', 'landing_web_app'])->delete();
        DB::table('landing_page_features')->truncate();
        DB::table('landing_page_specialities')->truncate();
        DB::table('landing_page_testimonials')->truncate();
        
        $userIds = User::where('user_type', '!=', 'super-admin')->pluck('id');
        Provider::whereIn('user_id', $userIds)->delete();
        User::where('user_type', '!=', 'super-admin')->delete();

        // 4. Landing Page Text Setup (DataSettings)
        $landingTexts = [
            ['key' => 'top_title', 'value' => 'Professional Artisans at Your Fingertips', 'type' => 'landing_text_setup'],
            ['key' => 'top_description', 'value' => 'Find the Best Handymen in Ghana for Any Job', 'type' => 'landing_text_setup'],
            ['key' => 'top_sub_title', 'value' => 'Goyaa connects you with verified plumbers, electricians, carpenters, and more. Reliable service, transparent pricing, guaranteed satisfaction.', 'type' => 'landing_text_setup'],
            ['key' => 'mid_title', 'value' => 'Explore Our Professional Services', 'type' => 'landing_text_setup'],
            ['key' => 'feature_title', 'value' => 'Why Choose Goyaa?', 'type' => 'landing_text_setup'],
            ['key' => 'speciality_title', 'value' => 'Our Specialities', 'type' => 'landing_text_setup'],
            ['key' => 'testimonial_title', 'value' => 'What Our Customers Say', 'type' => 'landing_text_setup'],
            ['key' => 'app_section_title', 'value' => 'Get the Goyaa App Today', 'type' => 'landing_text_setup'],
            ['key' => 'app_section_description', 'value' => 'Book artisans on the go. Available on Play Store and App Store.', 'type' => 'landing_text_setup'],
        ];

        foreach ($landingTexts as $text) {
            DataSetting::updateOrCreate(
                ['key' => $text['key'], 'type' => $text['type']],
                ['id' => Str::uuid(), 'value' => $text['value'], 'is_active' => 1]
            );
        }

        // 5. Landing Page Features
        $features = [
            ['title' => 'Verified Artisans', 'sub_title' => 'Every professional on our platform undergoes a rigorous background check.'],
            ['title' => 'Transparent Pricing', 'sub_title' => 'No hidden costs. Get upfront quotes and pay securely through the app.'],
            ['title' => 'Quality Guaranteed', 'sub_title' => 'We stand by the quality of work. Satisfaction or we make it right.'],
        ];

        foreach ($features as $f) {
            LandingPageFeature::create([
                'id' => Str::uuid(),
                'title' => $f['title'],
                'sub_title' => $f['sub_title'],
                'image_1' => 'feature.png',
            ]);
        }

        // 6. Landing Page Specialities
        $specialities = [
            ['title' => 'Fast Response', 'description' => 'Get connected with an artisan within minutes.'],
            ['title' => '24/7 Support', 'description' => 'Our customer support team is always here to help.'],
            ['title' => 'Flexible Scheduling', 'description' => 'Book services at a time that works best for you.'],
        ];

        foreach ($specialities as $s) {
            LandingPageSpeciality::create([
                'id' => Str::uuid(),
                'title' => $s['title'],
                'description' => $s['description'],
                'image' => 'speciality.png',
            ]);
        }

        // 7. Testimonials
        $testimonials = [
            ['name' => 'Michael Boateng', 'designation' => 'Homeowner in East Legon', 'review' => 'Goyaa saved my day when I had a major pipe burst. The plumber arrived in 30 mins and fixed it perfectly.'],
            ['name' => 'Sandra Owusu', 'designation' => 'Store Manager', 'review' => 'Finding a reliable electrician was always a headache until I started using Goyaa. Highly recommended!'],
        ];

        foreach ($testimonials as $t) {
            LandingPageTestimonial::create([
                'id' => Str::uuid(),
                'name' => $t['name'],
                'designation' => $t['designation'],
                'review' => $t['review'],
                'image' => 'customer.png',
            ]);
        }

        // 8. Create Categories
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
            $imageName = 'cat_' . strtolower(str_replace(' ', '_', $name)) . '.png';

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
            
            // Attach to zone
            DB::table('category_zone')->insert([
                'category_id' => $cat->id,
                'zone_id' => $zoneId
            ]);
        }

        // 9. Create Services
        $servicesData = [
            'Plumbing' => ['Fix Pipe Leakage' => 150, 'Toilet Installation' => 300],
            'Electrical' => ['House Wiring' => 500, 'Socket/Switch Repair' => 50],
            'Carpentry' => ['Door/Window Repair' => 120, 'Wardrobe Making' => 800],
            'Cleaning' => ['Deep House Cleaning' => 350, 'Sofa Cleaning' => 200],
            'Masonry' => ['Wall Plastering' => 400, 'Tile Laying' => 300],
            'Painting' => ['Room Painting' => 250, 'Exterior Painting' => 700],
            'AC Repair' => ['AC Servicing' => 150, 'New AC Installation' => 350]
        ];

        foreach ($servicesData as $catName => $catServices) {
            $catId = $categoryMap[$catName];
            foreach ($catServices as $serviceName => $price) {
                $imageName = 'srv_' . strtolower(str_replace(' ', '_', $serviceName)) . '.png';

                Service::create([
                    'id' => Str::uuid(),
                    'name' => $serviceName,
                    'short_description' => "Professional $serviceName services by expert artisans.",
                    'description' => "Get the best $serviceName in town. Our artisans are verified and highly skilled.",
                    'cover_image' => $imageName,
                    'thumbnail' => $imageName,
                    'category_id' => $catId,
                    'sub_category_id' => null,
                    'tax' => 0,
                    'is_active' => 1,
                    'min_bidding_price' => $price,
                ]);
            }
        }

        // 10. Artisans
        $artisans = [
            ['name' => 'Kwame Mensah', 'company' => 'Mensah Plumbing', 'phone' => '+233240000001', 'email' => 'kwame@goyaa.com'],
            ['name' => 'Kofi Osei', 'company' => 'Osei Electricals', 'phone' => '+233240000002', 'email' => 'kofi@goyaa.com'],
        ];

        foreach ($artisans as $artisan) {
            $user = User::create([
                'id' => Str::uuid(),
                'first_name' => explode(' ', $artisan['name'])[0],
                'last_name' => explode(' ', $artisan['name'])[1] ?? '',
                'email' => $artisan['email'],
                'phone' => $artisan['phone'],
                'password' => Hash::make('12345678'),
                'user_type' => 'provider-admin',
                'is_active' => 1,
                'is_email_verified' => 1,
                'is_phone_verified' => 1,
            ]);

            Provider::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'company_name' => $artisan['company'],
                'company_phone' => $artisan['phone'],
                'company_address' => 'Accra, Ghana',
                'company_email' => $artisan['email'],
                'logo' => 'prov.png',
                'is_approved' => 1,
                'is_active' => 1,
                'zone_id' => $zoneId,
            ]);
        }
    }

    private function updateBusinessSettings()
    {
        $settings = [
            // business_information type
            ['key' => 'business_name', 'val' => 'Goyaa', 'type' => 'business_information'],
            ['key' => 'business_phone', 'val' => '+233550000000', 'type' => 'business_information'],
            ['key' => 'business_email', 'val' => 'info@goyaa.com.gh', 'type' => 'business_information'],
            ['key' => 'business_address', 'val' => 'Accra, Ghana', 'type' => 'business_information'],
            ['key' => 'country', 'val' => 'GH', 'type' => 'business_information'],
            ['key' => 'currency_code', 'val' => 'GHS', 'type' => 'business_information'],
            ['key' => 'currency_symbol', 'val' => 'GH₵', 'type' => 'business_information'],
            ['key' => 'currency_decimal_point', 'val' => '2', 'type' => 'business_information'],
            ['key' => 'currency_symbol_position', 'val' => 'left', 'type' => 'business_information'],
            
            // landing_button_and_links type
            ['key' => 'app_url_playstore', 'val' => 'https://play.google.com/store/apps/details?id=com.goyaa.customer', 'type' => 'landing_button_and_links'],
            ['key' => 'app_url_appstore', 'val' => 'https://apps.apple.com/gh/app/goyaa/id12345', 'type' => 'landing_button_and_links'],
            ['key' => 'web_url', 'val' => 'https://goyaa.com.gh', 'type' => 'landing_button_and_links'],
        ];

        foreach ($settings as $setting) {
            BusinessSettings::updateOrCreate(
                ['key_name' => $setting['key']],
                ['live_values' => $setting['val'], 'test_values' => $setting['val'], 'is_active' => 1, 'settings_type' => $setting['type']]
            );
        }
    }
}
