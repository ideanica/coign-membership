<?php

namespace App\Http\Controllers;

use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;

class WebDevelopmentController extends Controller
{
    public function __invoke(MembershipSettings $settings): View
    {
        $values = $settings->all();

        return view('web-development', [
            'settings' => $values,
            'packages' => config('web_development.packages'),
            'comparison' => config('web_development.comparison'),
            'addons' => config('web_development.addons'),
        ]);
    }
}
