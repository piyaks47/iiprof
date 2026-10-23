<?php

namespace App\Admin\Resources\Membership;

class CorporateMemberResource extends OrganisationResource
{
    public function kind(): string { return 'corporate'; }

    public function key(): string { return 'corporate-members'; }

    public function label(): string { return 'Corporate members'; }

    public function singular(): string { return 'corporate member'; }

    public function area(): string { return 'corporate-members'; }

    public function icon(): string { return 'briefcase'; }

    public function description(): ?string { return 'Companies and firms that are members of IIP, shown as a logo wall on the homepage.'; }

    protected function categoryLabel(): string { return 'Industry'; }

    protected function categoryHint(): string { return 'e.g. IT & ITeS, Finance, Manufacturing, Healthcare.'; }

    public function emptyText(): string { return 'No corporate members yet — the block is hidden until you add one.'; }
}
