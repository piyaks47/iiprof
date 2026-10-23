<?php

namespace App\Admin\Resources\Membership;

use App\Models\MemberOrganisation;

class InstitutionalMemberResource extends OrganisationResource
{
    public function kind(): string { return 'institutional'; }

    public function key(): string { return 'institutional-members'; }

    public function label(): string { return 'Institutional members'; }

    public function singular(): string { return 'institution'; }

    public function area(): string { return 'institutional-members'; }

    public function icon(): string { return 'building'; }

    public function description(): ?string { return 'Universities, colleges, schools and institutes that are members or partners, shown with their logos on the homepage.'; }

    protected function categoryLabel(): string { return 'Type of institution'; }

    protected function categoryHint(): string { return 'e.g. '.implode(', ', MemberOrganisation::INSTITUTION_TYPES).'.'; }

    public function emptyText(): string { return 'No institutions yet — the block is hidden until you add one.'; }
}
