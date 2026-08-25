<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * There is no dashboard. Fortify and every "my account" link land here, so
     * this sends each account to the first page its role actually works from.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->user()?->can(PermissionsEnum::MANAGE_AUCTIONS->value)) {
            return to_route('admin.auctions.index');
        }

        return to_route('won-items.index');
    }
}
