<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\Setting;

/**
 * Featured images for the menu, shipped with the code in public/media/menu/ (served as /media/menu/…,
 * so they never depend on uploaded files). Free-to-use photos from Unsplash (https://unsplash.com/license,
 * credit not required; the source of each is listed below). Replace any of them under Website → Menu.
 *
 * Items without their own image use their parent's: Khazana → Lender → Banks/NBFC shows the Lender photo.
 */
class MenuImages
{
    public const DIR = '/media/menu/';

    public const MARKER = 'menu_images';

    /** Menu path (labels from the top, joined by "/") => [file, source]. */
    public const IMAGES = [
        'About Us' => ['about-us.webp', 'https://unsplash.com/photos/a-group-of-people-in-a-conference-room-XYuLj_jai0Y'],
        'About Us/About IIP' => ['about-us-about-iip.webp', 'https://unsplash.com/photos/three-men-sitting-while-using-laptops-and-watching-man-beside-whiteboard-wD1LRb9OeEo'],
        'About Us/Credibility' => ['about-us-credibility.webp', 'https://unsplash.com/photos/person-writing-on-white-paper-GJao3ZTX9gU'],
        'About Us/Management' => ['about-us-management.webp', 'https://unsplash.com/photos/group-of-people-sitting-beside-rectangular-wooden-table-with-laptops-faEfWCdOKIg'],
        'About Us/Testimonials' => ['about-us-testimonials.webp', 'https://unsplash.com/photos/a-woman-wearing-a-blue-shirt-and-a-braid-fu0GeOIHlEQ'],
        'Bookings' => ['bookings.webp', 'https://unsplash.com/photos/hotel-room-with-two-beds-3qMjrK4HIpo'],
        'Community' => ['community.webp', 'https://unsplash.com/photos/a-group-of-women-in-colorful-saris-standing-next-to-each-other-U7WId7mavCs'],
        'Consultancy' => ['consultancy.webp', 'https://unsplash.com/photos/man-standing-in-front-of-people-sitting-beside-table-with-laptop-computers-gMsnXqILjp4'],
        'Consultancy/Consultant' => ['consultancy-consultant.webp', 'https://unsplash.com/photos/woman-presenting-a-graph-to-an-audience-LzBMjkvZfh8'],
        'Consultancy/Request Consultancy' => ['consultancy-request-consultancy.webp', 'https://unsplash.com/photos/two-businessmen-shaking-hands-across-a-table-lZXX-QVdyXY'],
        'DuniyaBazar' => ['duniyabazar.webp', 'https://unsplash.com/photos/black-auto-rickshaw-on-road-during-daytime-qiZNQ6gWRwY'],
        'Edu10' => ['edu10.webp', 'https://unsplash.com/photos/boy-in-gray-shirt-using-black-laptop-computer-6MePtA9EVDA'],
        'Health' => ['health.webp', 'https://unsplash.com/photos/man-in-green-long-sleeve-shirt-sitting-beside-man-in-white-long-sleeve-shirt-YSbvqo9YLHA'],
        'Health/Blood Bank' => ['health-blood-bank.webp', 'https://unsplash.com/photos/blood-bag-in-clinical-setting-0ZZo5o00o80'],
        'Health/Diagnostic' => ['health-diagnostic.webp', 'https://unsplash.com/photos/blood-collection-tubes-in-laboratory-rack-0jE8ynV4mis'],
        'Health/Doctor' => ['health-doctor.webp', 'https://unsplash.com/photos/doctor-holding-red-stethoscope-hIgeoQjS_iE'],
        'Health/Health Worker' => ['health-health-worker.webp', 'https://unsplash.com/photos/woman-in-white-button-up-shirt-and-blue-stethoscope-l0j0DHVWcIE'],
        'Health/Hospitals' => ['health-hospitals.webp', 'https://unsplash.com/photos/hospital-bed-near-couch-KF-h9HMxRKg'],
        'Health/Medical Equipments' => ['health-medical-equipments.webp', 'https://unsplash.com/photos/operating-room-with-surgical-table-LPurJnihmQI'],
        'Health/Medicine' => ['health-medicine.webp', 'https://unsplash.com/photos/pharmacist-reaching-for-medication-on-shelf-byGTytEGjBo'],
        'Health/Patient' => ['health-patient.webp', 'https://unsplash.com/photos/nurse-smiling-with-elderly-patient-in-room-dMhB7w99ju8'],
        'Home' => ['home.webp', 'https://unsplash.com/photos/men-and-women-having-a-meeting-cW4lLTavU80'],
        'Hunar' => ['hunar.webp', 'https://unsplash.com/photos/a-classroom-full-of-students-sitting-at-desks-I4lLt9g8i3k'],
        'Hunar/Book My Seat' => ['hunar-book-my-seat.webp', 'https://unsplash.com/photos/people-throwing-hats-on-air-_kd5cxwZOK4'],
        'Hunar/World-Edu' => ['hunar-world-edu.webp', 'https://unsplash.com/photos/grand-brick-building-with-manicured-gardens-and-trees-JJSsYBO1aEs'],
        'Jeevika' => ['jeevika.webp', 'https://unsplash.com/photos/a-woman-shaking-hands-with-another-woman-sitting-at-a-table-IESB4iFVuzA'],
        'Jeevika/Entrepreneur' => ['jeevika-entrepreneur.webp', 'https://unsplash.com/photos/man-standing-in-front-of-group-of-men-rxpThOwuVgE'],
        'Jeevika/Jobs' => ['jeevika-jobs.webp', 'https://unsplash.com/photos/four-coworkers-smiling-around-laptop-at-table-Of_m3hMsoAA'],
        'Khazana' => ['khazana.webp', 'https://unsplash.com/photos/a-calculator-pen-and-money-on-a-table-X4VwfPzy_2o'],
        'Khazana/Borrower' => ['khazana-borrower.webp', 'https://unsplash.com/photos/a-man-sells-snacks-and-drinks-at-a-small-shop-BYQrA_uhX2Y'],
        'Khazana/Depositor' => ['khazana-depositor.webp', 'https://unsplash.com/photos/a-man-holding-a-jar-with-a-savings-label-on-it-0ITvgXAU5Oo'],
        'Khazana/Fund Manager' => ['khazana-fund-manager.webp', 'https://unsplash.com/photos/smartphone-calculator-on-desk-with-financial-charts-behind-ip7GFn5JqX8'],
        'Khazana/Lender' => ['khazana-lender.webp', 'https://unsplash.com/photos/grey-concrete-building-2_K82gx9Uk8'],
        'More' => ['more.webp', 'https://unsplash.com/photos/man-speaking-in-front-of-crowd-RfiBK6Y_upQ'],
        'More/Campaigns' => ['more-campaigns.webp', 'https://unsplash.com/photos/three-men-wearing-yellow-volunteers-shirts-qKVSEuBT5EY'],
        'More/Contact Us' => ['more-contact-us.webp', 'https://unsplash.com/photos/man-in-white-button-up-shirt-smiling-WaGMKmJ2F0Y'],
        'More/Events' => ['more-events.webp', 'https://unsplash.com/photos/people-inside-conference-cuKJre3nyYc'],
        'More/Gallery' => ['more-gallery.webp', 'https://unsplash.com/photos/canon-dslr-camera-on-brown-wooden-table-during-daytime-gGgoDJRD2WM'],
        'More/R&D' => ['more-randd.webp', 'https://unsplash.com/photos/man-sight-on-white-microscope-9vnACvX2748'],
        'Philanthropy' => ['philanthropy.webp', 'https://unsplash.com/photos/man-in-purple-shirt-and-gray-pants-sitting-on-floor-m9rw_cTRVyI'],
        'Philanthropy/Donee' => ['philanthropy-donee.webp', 'https://unsplash.com/photos/two-young-girls-sitting-at-a-desk-in-a-classroom-TcKmus54ij0'],
        'Philanthropy/Donor' => ['philanthropy-donor.webp', 'https://unsplash.com/photos/a-close-up-of-a-typewriter-with-a-donation-sign-on-it-wLBVAF-kMR0'],
        'Philanthropy/Profile Creation' => ['philanthropy-profile-creation.webp', 'https://unsplash.com/photos/person-using-laptop-computer-Hcfwew744z4'],
        'Talent-Register' => ['talent-register.webp', 'https://unsplash.com/photos/a-woman-is-reading-a-resume-at-a-table-9si2noVCVH8'],
    ];

    /** Bundled image for an item, by its labels from the top (["Khazana", "Lender"]). */
    public static function imageFor(array $labels): ?string
    {
        $file = self::IMAGES[implode('/', array_map('trim', $labels))][0] ?? null;

        return $file && is_file(public_path(ltrim(self::DIR, '/').$file)) ? self::DIR.$file : null;
    }

    public static function applied(): bool
    {
        return filled(Setting::query()->where('key', self::MARKER)->value('value'));
    }

    /**
     * Gives the bundled images to menu items that have none — once per installation (after that, removing
     * an image in the admin is permanent). Waits until the menu exists.
     */
    public static function applyOnce(): int
    {
        if (self::applied() || ! MenuItem::query()->exists()) {
            return 0;
        }

        $count = self::apply();
        Setting::query()->updateOrCreate(['key' => self::MARKER], ['value' => now()->toIso8601String()]);

        return $count;
    }

    public static function apply(): int
    {
        $count = 0;
        $labels = [];
        foreach (MenuItem::flatTree() as $item) {
            $labels = array_slice($labels, 0, $item->depth - 1);
            $labels[] = $item->label;
            if (blank($item->image) && ($image = self::imageFor($labels))) {
                MenuItem::query()->whereKey($item->id)->update(['image' => $image]); // (flatTree rows carry a computed depth)
                $count++;
            }
        }

        MenuItem::flushBannerMap();

        return $count;
    }
}
