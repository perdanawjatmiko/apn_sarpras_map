<?php

namespace App\Http\Controllers;

use App\Services\PublicMapService;
use Inertia\Inertia;
use Inertia\Response;

class PublicMapController extends Controller
{
    public function __construct(private readonly PublicMapService $maps) {}

    public function index(): Response
    {
        return Inertia::render('welcome', $this->maps->data());
    }
}
