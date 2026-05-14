<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class AdminController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('admin.koperasis.index');
    }
}
