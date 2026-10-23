<?php

namespace App\Admin;

use App\Admin\Resources;

/** Every generic admin CRUD screen, in sidebar order. */
class Registry
{
    /** @var list<class-string<Resource>> */
    public const RESOURCES = [
        Resources\MenuItemResource::class,
        Resources\AnnouncementResource::class,
        Resources\FooterColumnResource::class,
        Resources\FooterLinkResource::class,
        Resources\TeamMemberResource::class,
        Resources\TestimonialResource::class,
        Resources\HomeSectionResource::class,
        Resources\HeroSlideResource::class,
        Resources\StatResource::class,
        Resources\Home\FeatureTileResource::class,
        Resources\HighlightResource::class,
        Resources\Home\ImpactFigureResource::class,
        Resources\EventResource::class,
        Resources\GalleryItemResource::class,
        Resources\Home\PartnerResource::class,
        Resources\Home\FaqResource::class,
        // Membership (sidebar group "Membership", access area membership)
        Resources\Membership\MembershipSectionsResource::class,
        Resources\Home\MembershipCategoryResource::class,
        Resources\Home\MemberBenefitResource::class,
        Resources\Membership\MembershipObjectiveResource::class,
        Resources\Membership\MembershipStepResource::class,
        Resources\Membership\MembershipFeeResource::class,
        Resources\Membership\MemberHighlightResource::class,
        Resources\Membership\FeaturedMemberResource::class,
        Resources\Membership\CorporateMemberResource::class,
        Resources\Membership\InstitutionalMemberResource::class,
        // Training (sidebar group "Training", access area training)
        Resources\Training\TrainingSectionsResource::class,
        Resources\Training\TrainingProgrammeResource::class,
        Resources\Training\TrainingBatchResource::class,
        Resources\Training\TrainingFormatResource::class,
        Resources\Training\TrainingBenefitResource::class,
        Resources\Training\FacultyResource::class,
        Resources\Home\ProgrammeResource::class,
        // News & seminars
        Resources\News\PostResource::class,
        Resources\News\SeminarResource::class,
        Resources\EnquiryResource::class,
    ];

    /** @var array<string, Resource>|null */
    private static ?array $instances = null;

    /** @return array<string, Resource> key => resource */
    public static function all(): array
    {
        if (self::$instances === null) {
            self::$instances = [];
            foreach (self::RESOURCES as $class) {
                $resource = new $class;
                self::$instances[$resource->key()] = $resource;
            }
        }

        return self::$instances;
    }

    public static function find(string $key): ?Resource
    {
        return self::all()[$key] ?? null;
    }

    public static function get(string $key): Resource
    {
        return self::find($key) ?? abort(404);
    }

    /** Regex for the {resource} route parameter. */
    public static function routePattern(): string
    {
        return implode('|', array_map('preg_quote', array_keys(self::all())));
    }
}
